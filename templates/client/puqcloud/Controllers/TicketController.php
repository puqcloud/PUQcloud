<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace Template\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\TicketFeedback;
use App\Models\TicketReply;
use App\Models\TicketStatus;
use App\Services\HookService;
use App\Services\SettingService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\DataTables;

class TicketController extends Controller
{
    // ---- WEB methods ----

    public function tickets(): View|Factory|Application
    {
        $title = __('main.Support Tickets');

        return view_client('support.tickets', compact('title'));
    }

    public function ticketCreate(): View|Factory|Application
    {
        $title = __('main.Open a Ticket');

        return view_client('support.ticket_create', compact('title'));
    }

    public function ticketDetail(Request $request, $uuid): View|Factory|Application
    {
        $client = app('client');
        $ticket = Ticket::where('uuid', $uuid)
            ->where('client_uuid', $client->uuid)
            ->firstOrFail();

        $title = __('main.Ticket') . ' #' . $ticket->number . ' - ' . $ticket->subject;

        return view_client('support.ticket_detail', compact('title', 'uuid'));
    }

    // ---- API methods ----

    public function getTickets(Request $request): JsonResponse
    {
        $client = app('client');

        $query = Ticket::where('client_uuid', $client->uuid)
            ->with(['department', 'status']);

        $allowedKeys = [
            'uuid',
            'number',
            'subject',
            'department_name',
            'status_data',
            'priority',
            'last_reply_at',
            'urls',
        ];

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('subject', 'like', "%{$search}%")
                                ->orWhere('number', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('department_name', function ($ticket) {
                    return $ticket->department?->name ?? '';
                })
                ->addColumn('status_data', function ($ticket) {
                    return [
                        'title' => $ticket->status?->title ?? '',
                        'color' => $ticket->status?->color ?? '#6c757d',
                    ];
                })
                ->addColumn('urls', function ($ticket) {
                    return [
                        'detail' => route('client.web.panel.support.ticket.detail', $ticket->uuid),
                    ];
                })
                ->only($allowedKeys)
                ->make(true),
        ], 200);
    }

    public function getTicket(Request $request, $uuid): JsonResponse
    {
        $client = app('client');

        $ticket = Ticket::where('uuid', $uuid)
            ->where('client_uuid', $client->uuid)
            ->with(['department', 'status', 'assignedAdmin'])
            ->first();

        if (! $ticket) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $replies = $ticket->replies()
            ->where('is_admin_note', false)
            ->orderBy('created_at', 'asc')
            ->get();

        $repliesData = $replies->map(function ($reply) {
            $data = $reply->toArray();
            $data['attachments'] = $reply->getAttachments();

            return $data;
        });

        return response()->json([
            'data' => [
                'uuid' => $ticket->uuid,
                'number' => $ticket->number,
                'subject' => $ticket->subject,
                'priority' => $ticket->priority,
                'last_reply_at' => $ticket->last_reply_at,
                'created_at' => $ticket->created_at,
                'closed_at' => $ticket->closed_at,
                'department' => $ticket->department ? [
                    'uuid' => $ticket->department->uuid,
                    'name' => $ticket->department->name,
                ] : null,
                'status' => $ticket->status ? [
                    'uuid' => $ticket->status->uuid,
                    'title' => $ticket->status->title,
                    'color' => $ticket->status->color,
                ] : null,
                'assigned_admin_name' => $ticket->assignedAdmin
                    ? $ticket->assignedAdmin->firstname . ' ' . $ticket->assignedAdmin->lastname
                    : null,
                'is_closed' => $ticket->isClosed(),
                'allow_reopen' => $ticket->department?->allow_client_reopen ?? true,
                'feedback_on_close' => $ticket->department?->feedback_on_close ?? false,
                'file_settings' => [
                    'allowed_extensions' => $ticket->department?->allowed_file_extensions
                        ?: SettingService::get('support.ticket_allowed_file_extensions'),
                    'max_file_size_mb' => $ticket->department?->max_file_size_mb
                        ?: SettingService::get('support.ticket_max_file_size_mb'),
                    'max_files_count' => $ticket->department?->max_files_count,
                ],
                'replies' => $repliesData,
            ],
        ]);
    }

    public function postTicket(Request $request): JsonResponse
    {
        $client = app('client');
        $user = app('user');

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:50000',
            'department_uuid' => [
                'required',
                function ($attribute, $value, $fail) {
                    $dept = TicketDepartment::where('uuid', $value)
                        ->where('hidden', false)
                        ->first();
                    if (! $dept) {
                        $fail(__('error.The selected department is invalid'));
                    }
                },
            ],
            'priority' => 'nullable|in:low,medium,high',
            'service_uuid' => [
                'nullable',
                function ($attribute, $value, $fail) use ($client) {
                    if ($value) {
                        $service = Service::where('uuid', $value)
                            ->where('client_uuid', $client->uuid)
                            ->first();
                        if (! $service) {
                            $fail(__('error.The selected service is invalid'));
                        }
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $openStatus = TicketStatus::where('title', 'Open')->first();

        $ticket = new Ticket;
        $ticket->subject = htmlspecialchars(strip_tags($request->input('subject')), ENT_QUOTES, 'UTF-8');
        $ticket->department_uuid = $request->input('department_uuid');
        $ticket->status_uuid = $openStatus?->uuid;
        $ticket->priority = $request->input('priority', 'medium');
        $ticket->client_uuid = $client->uuid;
        $ticket->user_uuid = $user->uuid;
        $ticket->email = $user->email;
        $ticket->name = $client->firstname . ' ' . $client->lastname;
        $ticket->service_uuid = $request->input('service_uuid');
        $ticket->last_reply_at = now();
        $ticket->save();

        TicketReply::create([
            'ticket_uuid' => $ticket->uuid,
            'user_uuid' => $user->uuid,
            'name' => $client->firstname . ' ' . $client->lastname,
            'email' => $user->email,
            'message' => htmlspecialchars(strip_tags($request->input('message')), ENT_QUOTES, 'UTF-8'),
        ]);

        app(HookService::class)->callHooks('TicketCreated', ['ticket' => $ticket]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [
                'ticket_uuid' => $ticket->uuid,
                'redirect' => route('client.web.panel.support.ticket.detail', $ticket->uuid),
            ],
        ]);
    }

    public function postTicketReply(Request $request, $uuid): JsonResponse
    {
        $client = app('client');
        $user = app('user');

        $ticket = Ticket::where('uuid', $uuid)
            ->where('client_uuid', $client->uuid)
            ->with(['status'])
            ->first();

        if (! $ticket) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($ticket->isClosed()) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Closed tickets cannot be reopened')],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:50000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $reply = TicketReply::create([
            'ticket_uuid' => $ticket->uuid,
            'user_uuid' => $user->uuid,
            'name' => $client->firstname . ' ' . $client->lastname,
            'email' => $user->email,
            'message' => htmlspecialchars(strip_tags($request->input('message')), ENT_QUOTES, 'UTF-8'),
        ]);

        $customerReplyStatus = TicketStatus::where('title', 'Customer-Reply')->first();
        if ($customerReplyStatus) {
            $ticket->status_uuid = $customerReplyStatus->uuid;
        }
        $ticket->last_reply_at = now();
        $ticket->save();

        app(HookService::class)->callHooks('TicketRepliedByClient', ['ticket' => $ticket, 'reply' => $reply]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Reply added successfully'),
        ]);
    }

    public function closeTicket(Request $request, $uuid): JsonResponse
    {
        $client = app('client');

        $ticket = Ticket::where('uuid', $uuid)
            ->where('client_uuid', $client->uuid)
            ->with(['status'])
            ->first();

        if (! $ticket) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($ticket->isClosed()) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Ticket is already closed')],
            ], 422);
        }

        $closedStatus = TicketStatus::where('title', 'Closed')->first();
        if ($closedStatus) {
            $ticket->status_uuid = $closedStatus->uuid;
            $ticket->closed_at = now();
            $ticket->save();
        }

        // Save feedback if provided
        if ($request->filled('rating')) {
            TicketFeedback::updateOrCreate(
                ['ticket_uuid' => $ticket->uuid],
                [
                    'rating' => max(1, min(5, (int) $request->input('rating'))),
                    'comment' => $request->input('comment') ? htmlspecialchars(strip_tags($request->input('comment')), ENT_QUOTES, 'UTF-8') : null,
                ]
            );
        }

        app(HookService::class)->callHooks('TicketClosed', ['ticket' => $ticket]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Ticket closed successfully'),
        ]);
    }

    public function reopenTicket(Request $request, $uuid): JsonResponse
    {
        $client = app('client');

        $ticket = Ticket::where('uuid', $uuid)
            ->where('client_uuid', $client->uuid)
            ->with(['status'])
            ->first();

        if (! $ticket) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (! $ticket->isClosed()) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Ticket is not closed')],
            ], 422);
        }

        if (! ($ticket->department?->allow_client_reopen ?? true)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Reopening is not allowed for this department')],
            ], 403);
        }

        $openStatus = TicketStatus::where('title', 'Open')->first();
        if ($openStatus) {
            $ticket->status_uuid = $openStatus->uuid;
            $ticket->closed_at = null;
            $ticket->save();
        }

        app(HookService::class)->callHooks('TicketReopened', ['ticket' => $ticket]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Ticket reopened successfully'),
        ]);
    }

    public function getTicketDepartmentsSelect(Request $request): JsonResponse
    {
        $departments = TicketDepartment::where('hidden', false)
            ->orderBy('sort_order')
            ->get();

        $results = $departments->map(function ($dept) {
            return [
                'id' => $dept->uuid,
                'text' => $dept->name,
            ];
        });

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => ['more' => false],
        ]]);
    }

    public function getTicketDepartmentFileSettings(Request $request, $uuid): JsonResponse
    {
        $department = TicketDepartment::where('uuid', $uuid)
            ->where('hidden', false)
            ->first();

        if (! $department) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'data' => [
                'allowed_extensions' => $department->allowed_file_extensions
                    ?: SettingService::get('support.ticket_allowed_file_extensions'),
                'max_file_size_mb' => $department->max_file_size_mb
                    ?: SettingService::get('support.ticket_max_file_size_mb'),
                'max_files_count' => $department->max_files_count,
            ],
        ]);
    }

    public function getTicketServicesSelect(Request $request): JsonResponse
    {
        $client = app('client');

        $services = Service::where('client_uuid', $client->uuid)
            ->whereIn('status', ['pending', 'active', 'suspended'])
            ->with('product.productGroups')
            ->get();

        $results = $services->map(function ($service) {
            $groupName = $service->product?->productGroups?->first()?->name;
            $productName = $service->product?->name;
            $label = $service->client_label ?? $service->uuid;

            $parts = array_filter([$groupName, $productName]);
            $text = $parts ? implode(' / ', $parts) . ' - ' . $label : $label;

            return [
                'id' => $service->uuid,
                'text' => $text,
            ];
        });

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => ['more' => false],
        ]]);
    }

    public function postTicketAttachment(Request $request): JsonResponse
    {
        $client = app('client');

        $validator = Validator::make($request->all(), [
            'reply_uuid' => 'required|exists:ticket_replies,uuid',
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        // Verify reply belongs to client's ticket
        $reply = TicketReply::find($request->input('reply_uuid'));
        $ticket = Ticket::where('uuid', $reply->ticket_uuid)
            ->where('client_uuid', $client->uuid)
            ->first();

        if (! $ticket) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $uploadedFile = $request->file('file');
        $department = $ticket->department;

        // Validate file extension (department setting, fallback to global)
        $allowedExtensions = $department?->allowed_file_extensions
            ?: SettingService::get('support.ticket_allowed_file_extensions');
        if (! empty($allowedExtensions)) {
            $allowed = array_map('trim', explode(',', $allowedExtensions));
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            if (! in_array($extension, $allowed)) {
                return response()->json([
                    'message' => ['file' => [__('error.File extension not allowed')]],
                ], 422);
            }
        }

        // Validate file size (department setting, fallback to global)
        $maxSizeMb = $department?->max_file_size_mb
            ?: SettingService::get('support.ticket_max_file_size_mb');
        if (! empty($maxSizeMb) && $uploadedFile->getSize() > (int) $maxSizeMb * 1024 * 1024) {
            return response()->json([
                'message' => ['file' => [__('error.File size exceeds maximum allowed')]],
            ], 422);
        }

        // Validate max files count per reply (department setting)
        $maxFilesCount = $department?->max_files_count;
        if (! empty($maxFilesCount)) {
            $currentCount = File::where('model_type', TicketReply::class)
                ->where('model_uuid', $reply->uuid)
                ->where('model_field', 'attachment')
                ->count();
            if ($currentCount >= $maxFilesCount) {
                return response()->json([
                    'message' => ['file' => [__('error.Maximum number of files reached')]],
                ], 422);
            }
        }

        $ticketUuid = $reply->ticket_uuid;
        $filename = Str::uuid() . '.' . $uploadedFile->getClientOriginalExtension();
        $directory = 'tickets/' . $ticketUuid;
        $path = $uploadedFile->storeAs($directory, $filename);

        $file = new File;
        $file->name = $uploadedFile->getClientOriginalName();
        $file->type = $uploadedFile->getMimeType();
        $file->size = $uploadedFile->getSize();
        $file->path = $path;
        $file->directory = $directory;
        $file->is_public = false;
        $file->model_type = TicketReply::class;
        $file->model_uuid = $reply->uuid;
        $file->model_field = 'attachment';
        $file->save();

        return response()->json([
            'status' => 'success',
            'data' => $file,
        ]);
    }

    public function getTicketReplyAttachment(Request $request, $uuid): StreamedResponse|JsonResponse
    {
        $client = app('client');

        $file = File::where('uuid', $uuid)
            ->where('model_type', TicketReply::class)
            ->where('model_field', 'attachment')
            ->first();

        if (! $file) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        // Verify the reply belongs to a ticket owned by this client
        $reply = TicketReply::find($file->model_uuid);
        if (! $reply) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $ticket = Ticket::where('uuid', $reply->ticket_uuid)
            ->where('client_uuid', $client->uuid)
            ->first();

        if (! $ticket) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $inline = $request->query('view') === '1';
        $response = $inline ? $file->streamResponse() : $file->downloadResponse();
        if (! $response) {
            return response()->json([
                'errors' => [__('error.File not found')],
            ], 404);
        }

        return $response;
    }
}
