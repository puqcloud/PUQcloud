<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

/*
 * WEB Routes:
 *   GET  tickets                                -> tickets()                   [admin.web.tickets]
 *   GET  ticket_create                          -> ticketCreate()              [admin.web.ticket_create]
 *   GET  ticket/{uuid}                          -> ticket()                    [admin.web.ticket]
 *
 * API Routes:
 *   GET    tickets                              -> getTickets()                [admin.api.tickets.get]
 *   POST   ticket                               -> postTicket()                [admin.api.ticket.post]
 *   GET    ticket/{uuid}                        -> getTicket()                 [admin.api.ticket.get]
 *   PUT    ticket/{uuid}                        -> putTicket()                 [admin.api.ticket.put]
 *   DELETE ticket/{uuid}                        -> deleteTicket()              [admin.api.ticket.delete]
 *   POST   ticket/{uuid}/reply                  -> postTicketReply()           [admin.api.ticket.reply.post]
 *   POST   ticket/{uuid}/note                   -> postTicketNote()            [admin.api.ticket.note.post]
 *   POST   ticket/{uuid}/merge                  -> postTicketMerge()           [admin.api.ticket.merge.post]
 *   POST   ticket/{uuid}/publish                -> publishTicket()             [admin.api.ticket.publish.post]
 *   GET    ticket/{uuid}/replies                -> getTicketReplies()          [admin.api.ticket.replies.get]
 *   PUT    ticket_reply/{uuid}                  -> putTicketReply()            [admin.api.ticket_reply.put]
 *   POST   ticket_reply/attachment              -> postTicketReplyAttachment() [admin.api.ticket_reply.attachment.post]
 *   DELETE ticket_reply/attachment/{uuid}        -> deleteTicketReplyAttachment() [admin.api.ticket_reply.attachment.delete]
 *   POST   ticket/attachment                    -> postTicketAttachment()      [admin.api.ticket.attachment.post]
 *   DELETE ticket/attachment/{uuid}             -> deleteTicketAttachment()    [admin.api.ticket.attachment.delete]
 *   GET    ticket/attachment/{uuid}             -> getTicketReplyAttachmentDownload() [admin.api.ticket.attachment.get]
 *   GET    ticket/{uuid}/activity              -> getTicketActivity()          [admin.api.ticket.activity.get]
 *   POST   tickets/bulk_action                 -> postBulkAction()            [admin.api.tickets.bulk_action.post]
 *   GET    client/{uuid}/tickets               -> getClientTickets()          [admin.api.client.tickets.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\TicketReply;
use App\Models\TicketStatus;
use App\Services\HookService;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminTicketsController extends Controller
{
    public function tickets(): View
    {
        $title = __('main.Tickets');

        return view_admin('tickets.tickets', compact('title'));
    }

    public function ticketCreate(): View
    {
        $title = __('main.Create Ticket');

        return view_admin('tickets.ticket_create', compact('title'));
    }

    public function ticket(Request $request, $uuid): View
    {
        $title = __('main.Ticket');

        return view_admin('tickets.ticket', compact('title', 'uuid'));
    }

    #[OA\Get(
        path: 'admin.api.tickets.get',
        description: 'Returns a DataTable list of tickets with filters.',
        summary: 'Get tickets list',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'department_uuid', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'status_uuid', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'priority', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high', 'urgent'])),
            new OA\Parameter(name: 'assigned_admin_uuid', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tickets list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getTickets(Request $request): JsonResponse
    {
        $query = Ticket::query()
            ->with(['department', 'status', 'client', 'assignedAdmin'])
            ->orderBy('last_reply_at', 'desc');

        // Exclude drafts from default listing unless explicitly filtered
        if (!$request->filled('status_uuid')) {
            $draftStatus = TicketStatus::where('title', 'Draft')->first();
            if ($draftStatus) {
                $query->where('status_uuid', '!=', $draftStatus->uuid);
            }
        }

        if ($request->filled('department_uuid')) {
            $query->where('department_uuid', $request->input('department_uuid'));
        }
        if ($request->filled('status_uuid')) {
            $statusUuids = $request->input('status_uuid');
            if (is_string($statusUuids) && str_contains($statusUuids, ',')) {
                $statusUuids = array_filter(explode(',', $statusUuids));
            }
            if (is_array($statusUuids)) {
                $query->whereIn('status_uuid', $statusUuids);
            } else {
                $query->where('status_uuid', $statusUuids);
            }
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('assigned_admin_uuid')) {
            $query->where('assigned_admin_uuid', $request->input('assigned_admin_uuid'));
        }

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('subject', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('number', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($ticket) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('tickets-view')) {
                        $urls['edit'] = route('admin.web.ticket', $ticket->uuid);
                    }
                    if ($admin_online && $admin_online->hasPermission('tickets-delete')) {
                        $urls['delete'] = route('admin.api.ticket.delete', $ticket->uuid);
                    }

                    return $urls;
                })
                ->addColumn('department_name', function ($ticket) {
                    return $ticket->department?->name;
                })
                ->addColumn('status_data', function ($ticket) {
                    return [
                        'title' => $ticket->status?->title,
                        'color' => $ticket->status?->color,
                    ];
                })
                ->addColumn('client_name', function ($ticket) {
                    return $ticket->client
                        ? $ticket->client->firstname.' '.$ticket->client->lastname
                        : $ticket->name;
                })
                ->addColumn('assigned_admin_name', function ($ticket) {
                    return $ticket->assignedAdmin
                        ? $ticket->assignedAdmin->firstname.' '.$ticket->assignedAdmin->lastname
                        : null;
                })
                ->addColumn('reply_count', function ($ticket) {
                    return $ticket->replies()->count();
                })
                ->addColumn('sla_breach', function ($ticket) {
                    $slaHours = $ticket->department?->sla_response_hours;
                    if (! $slaHours || ! $ticket->status?->include_in_awaiting_reply || ! $ticket->last_reply_at) {
                        return null;
                    }

                    $deadline = $ticket->last_reply_at->copy()->addHours($slaHours);
                    $diffMinutes = (int) now()->diffInMinutes($deadline, false);

                    return [
                        'breached' => $diffMinutes < 0,
                        'minutes' => $diffMinutes,
                        'deadline' => $deadline->toIso8601String(),
                    ];
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.ticket.post',
        description: 'Creates a new ticket as Draft with the first reply from admin. Use publish endpoint to set status to Open and fire notifications.',
        summary: 'Create a new draft ticket',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['subject', 'message', 'department_uuid', 'priority'],
                    properties: [
                        new OA\Property(property: 'subject', type: 'string', example: 'Need help with service'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'department_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent']),
                        new OA\Property(property: 'client_uuid', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'service_uuid', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                        new OA\Property(property: 'name', type: 'string', nullable: true),
                        new OA\Property(property: 'cc_emails', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicket(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:500',
            'message' => 'required|string',
            'department_uuid' => 'required|exists:ticket_departments,uuid',
            'priority' => 'required|in:low,medium,high,urgent',
            'client_uuid' => 'nullable|exists:clients,uuid',
            'service_uuid' => 'nullable|exists:services,uuid',
            'email' => 'nullable|email',
            'name' => 'nullable|string',
            'cc_emails' => 'nullable|array',
            'tags' => 'nullable|array',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $admin = app('admin');
        $draftStatus = TicketStatus::where('title', 'Draft')->first();

        if (!$draftStatus) {
            return response()->json(['errors' => [__('error.Default Draft status not found. Run TicketStatusSeeder.')]], 500);
        }

        $ticket = new Ticket;
        $ticket->subject = strip_tags($request->input('subject'));
        $ticket->department_uuid = $request->input('department_uuid');
        $ticket->status_uuid = $draftStatus->uuid;
        $ticket->priority = $request->input('priority');
        $ticket->client_uuid = $request->input('client_uuid');
        $ticket->service_uuid = $request->input('service_uuid');
        $ticket->email = $request->input('email');
        $ticket->name = $request->input('name');
        $ticket->cc_emails = $request->input('cc_emails');
        $ticket->tags = $request->input('tags');
        $ticket->admin_notes = $request->input('admin_notes');
        $ticket->assigned_admin_uuid = $admin->uuid;
        $ticket->save();

        $allowedTags = '<p><br><strong><em><ul><ol><li><a><code><pre><blockquote><h1><h2><h3><h4><h5><h6>';

        TicketReply::create([
            'ticket_uuid' => $ticket->uuid,
            'admin_uuid' => $admin->uuid,
            'name' => $admin->firstname.' '.$admin->lastname,
            'email' => $admin->email,
            'message' => strip_tags($request->input('message'), $allowedTags),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [
                'uuid' => $ticket->uuid,
                'redirect' => route('admin.web.ticket', $ticket->uuid),
            ],
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ticket.get',
        description: 'Returns detailed information about a ticket by UUID.',
        summary: 'Get ticket by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket not found'),
        ]
    )]
    public function getTicket(Request $request, $uuid): JsonResponse
    {
        $ticket = Ticket::with(['department', 'status', 'client', 'user', 'assignedAdmin', 'service'])
            ->find($uuid);

        if (empty($ticket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $ticket->toArray();
        $responseData['total_time_spent'] = $ticket->getTotalTimeSpent();
        $responseData['replies_count'] = $ticket->replies()->count();

        // Format for Select2 pre-selection
        if ($ticket->status) {
            $responseData['status_data'] = ['id' => $ticket->status->uuid, 'text' => $ticket->status->title];
        }
        if ($ticket->department) {
            $responseData['department_data'] = ['id' => $ticket->department->uuid, 'text' => $ticket->department->name];
        }
        if ($ticket->assignedAdmin) {
            $responseData['assigned_admin_data'] = ['id' => $ticket->assignedAdmin->uuid, 'text' => $ticket->assignedAdmin->firstname.' '.$ticket->assignedAdmin->lastname];
        }
        if ($ticket->service) {
            $responseData['service_data'] = ['id' => $ticket->service->uuid, 'text' => $ticket->service->admin_label ?? $ticket->service->uuid];
        }
        if ($ticket->client) {
            $responseData['client_name'] = $ticket->client->company_name ?: ($ticket->client->firstname.' '.$ticket->client->lastname);
            $responseData['client_email'] = $ticket->client->owner()?->email ?? '';
        }

        $responseData['is_draft'] = $ticket->isDraft();
        $responseData['attachments'] = $ticket->getAttachments();

        $slaHours = $ticket->department?->sla_response_hours;
        if ($slaHours && $ticket->status?->include_in_awaiting_reply && $ticket->last_reply_at) {
            $deadline = $ticket->last_reply_at->copy()->addHours($slaHours);
            $diffMinutes = (int) now()->diffInMinutes($deadline, false);
            $responseData['sla'] = [
                'hours' => $slaHours,
                'deadline' => $deadline->toIso8601String(),
                'breached' => $diffMinutes < 0,
                'minutes' => $diffMinutes,
            ];
        }

        $feedback = $ticket->feedback;
        $responseData['feedback'] = $feedback ? [
            'rating' => $feedback->rating,
            'comment' => $feedback->comment,
            'created_at' => $feedback->created_at,
        ] : null;

        if ($ticket->client_uuid) {
            $responseData['related_tickets'] = Ticket::where('client_uuid', $ticket->client_uuid)
                ->where('uuid', '!=', $ticket->uuid)
                ->with('status')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['uuid', 'number', 'subject', 'status_uuid', 'created_at'])
                ->map(fn ($t) => [
                    'number' => $t->number,
                    'subject' => $t->subject,
                    'status' => $t->status ? ['title' => $t->status->title, 'color' => $t->status->color] : null,
                    'url' => route('admin.web.ticket', $t->uuid),
                ]);
        }

        return response()->json([
            'data' => $responseData,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.ticket.put',
        description: 'Updates a ticket by UUID. Fires hooks on status/assignment/department changes.',
        summary: 'Update ticket by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'subject', type: 'string'),
                        new OA\Property(property: 'department_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'status_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent']),
                        new OA\Property(property: 'assigned_admin_uuid', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'client_uuid', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'service_uuid', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'cc_emails', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
                        new OA\Property(property: 'time_spent_minutes', type: 'integer', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putTicket(Request $request, $uuid): JsonResponse
    {
        $ticket = Ticket::with(['status'])->find($uuid);

        if (empty($ticket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'nullable|string|max:500',
            'department_uuid' => 'nullable|exists:ticket_departments,uuid',
            'status_uuid' => 'nullable|exists:ticket_statuses,uuid',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_admin_uuid' => 'nullable|exists:admins,uuid',
            'client_uuid' => 'nullable|exists:clients,uuid',
            'service_uuid' => 'nullable|exists:services,uuid',
            'cc_emails' => 'nullable|array',
            'tags' => 'nullable|array',
            'admin_notes' => 'nullable|string',
            'time_spent_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $oldStatusUuid = $ticket->status_uuid;
        $oldAssignedAdminUuid = $ticket->assigned_admin_uuid;
        $oldDepartmentUuid = $ticket->department_uuid;
        $wasClosed = $ticket->isClosed();

        // Prevent reverting to Draft status
        if ($request->has('status_uuid') && $request->input('status_uuid') !== $oldStatusUuid) {
            $newStatus = TicketStatus::find($request->input('status_uuid'));
            if ($newStatus && strtolower($newStatus->title) === 'draft' && !$ticket->isDraft()) {
                return response()->json([
                    'message' => ['status_uuid' => [__('error.Cannot revert to Draft status')]],
                ], 422);
            }
        }

        $fields = [
            'subject', 'department_uuid', 'status_uuid', 'priority',
            'assigned_admin_uuid', 'client_uuid', 'service_uuid', 'cc_emails', 'tags',
            'admin_notes', 'time_spent_minutes',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                if ($field === 'subject' && $value !== null) {
                    $value = strip_tags($value);
                }
                $ticket->$field = $value;
            }
        }

        // Check status transitions
        if ($request->has('status_uuid') && $request->input('status_uuid') !== $oldStatusUuid) {
            $newStatus = TicketStatus::find($request->input('status_uuid'));

            if ($newStatus && $newStatus->title === 'Closed') {
                $ticket->closed_at = now();
            }

            if ($wasClosed && $newStatus && $newStatus->title !== 'Closed') {
                $ticket->closed_at = null;
                $ticket->save();
                app(HookService::class)->callHooks('TicketReopened', ['ticket' => $ticket]);
            }
        }

        // Check assignment change
        if ($request->has('assigned_admin_uuid') && $request->input('assigned_admin_uuid') !== $oldAssignedAdminUuid) {
            $ticket->save();
            app(HookService::class)->callHooks('TicketAssigned', ['ticket' => $ticket]);
        }

        // Check department change
        if ($request->has('department_uuid') && $request->input('department_uuid') !== $oldDepartmentUuid) {
            $ticket->save();
            app(HookService::class)->callHooks('TicketEscalated', ['ticket' => $ticket]);
        }

        $ticket->save();
        $ticket->load(['status', 'department', 'assignedAdmin']);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $ticket,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.ticket.delete',
        description: 'Deletes a ticket by UUID. Cascade removes all replies.',
        summary: 'Delete ticket by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket not found'),
        ]
    )]
    public function deleteTicket(Request $request, $uuid): JsonResponse
    {
        $ticket = Ticket::find($uuid);

        if (empty($ticket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $ticket->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('error.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.ticket.reply.post',
        description: 'Adds a reply to a ticket from admin. Updates ticket status to Answered.',
        summary: 'Post a reply to a ticket',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'time_spent_minutes', type: 'integer', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reply posted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicketReply(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'time_spent_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::with(['status'])->find($uuid);

        if (empty($ticket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $admin = app('admin');

        if (!$admin) {
            return response()->json(['errors' => [__('error.Not authenticated')]], 401);
        }

        $allowedTags = '<p><br><strong><em><ul><ol><li><a><code><pre><blockquote><h1><h2><h3><h4><h5><h6>';

        $reply = TicketReply::create([
            'ticket_uuid' => $ticket->uuid,
            'admin_uuid' => $admin->uuid,
            'name' => $admin->firstname.' '.$admin->lastname,
            'email' => $admin->email,
            'message' => strip_tags($request->input('message'), $allowedTags),
            'time_spent_minutes' => $request->input('time_spent_minutes', 0),
        ]);

        // If ticket was closed, reopen it
        if ($ticket->isClosed()) {
            $openStatus = TicketStatus::where('title', 'Open')->first();
            if ($openStatus) {
                $ticket->status_uuid = $openStatus->uuid;
                $ticket->closed_at = null;
            }
            $ticket->last_reply_at = now();
            $ticket->sla_reminder_sent = false;
            $ticket->save();

            $ticket->load(['status', 'department']);
            app(HookService::class)->callHooks('TicketReopened', ['ticket' => $ticket]);
        } else {
            // Set status to Answered
            $answeredStatus = TicketStatus::where('title', 'Answered')->first();
            if ($answeredStatus) {
                $ticket->status_uuid = $answeredStatus->uuid;
            }
            $ticket->last_reply_at = now();
            $ticket->sla_reminder_sent = false;
            $ticket->save();
        }

        $ticket->load(['status', 'department']);
        app(HookService::class)->callHooks('TicketRepliedByStaff', ['ticket' => $ticket, 'reply' => $reply]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $reply->toArray(),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.ticket.note.post',
        description: 'Adds an internal admin note to a ticket. No client notification is sent.',
        summary: 'Post an internal note to a ticket',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'time_spent_minutes', type: 'integer', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Note added successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicketNote(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'time_spent_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::find($uuid);

        if (empty($ticket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $admin = app('admin');

        if (!$admin) {
            return response()->json(['errors' => [__('error.Not authenticated')]], 401);
        }

        $allowedTags = '<p><br><strong><em><ul><ol><li><a><code><pre><blockquote><h1><h2><h3><h4><h5><h6>';

        $note = TicketReply::create([
            'ticket_uuid' => $ticket->uuid,
            'admin_uuid' => $admin->uuid,
            'name' => $admin->firstname.' '.$admin->lastname,
            'email' => $admin->email,
            'message' => strip_tags($request->input('message'), $allowedTags),
            'is_admin_note' => true,
            'time_spent_minutes' => $request->input('time_spent_minutes', 0),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $note->toArray(),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.ticket.merge.post',
        description: 'Merges another ticket into this one. Moves all replies and closes the source ticket.',
        summary: 'Merge a ticket into another',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['merge_ticket_uuid'],
                    properties: [
                        new OA\Property(property: 'merge_ticket_uuid', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the target ticket',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tickets merged successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Merged successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicketMerge(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'merge_ticket_uuid' => 'required|exists:tickets,uuid|different:'.$uuid,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $targetTicket = Ticket::find($uuid);

        if (empty($targetTicket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $sourceTicket = Ticket::find($request->input('merge_ticket_uuid'));

        if (empty($sourceTicket)) {
            return response()->json(['errors' => [__('error.Source ticket not found')]], 404);
        }

        // Move all replies from source to target
        TicketReply::where('ticket_uuid', $sourceTicket->uuid)
            ->update(['ticket_uuid' => $uuid]);

        // Close the source ticket with a note
        $closedStatus = TicketStatus::where('title', 'Closed')->first();
        if ($closedStatus) {
            $sourceTicket->status_uuid = $closedStatus->uuid;
            $sourceTicket->closed_at = now();
        }
        $sourceTicket->save();

        $admin = app('admin');

        TicketReply::create([
            'ticket_uuid' => $sourceTicket->uuid,
            'admin_uuid' => $admin->uuid,
            'name' => $admin->firstname.' '.$admin->lastname,
            'email' => $admin->email,
            'message' => 'Merged into ticket #'.$targetTicket->number,
            'is_admin_note' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Merged successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ticket.replies.get',
        description: 'Returns all replies for a ticket ordered by creation date.',
        summary: 'Get ticket replies',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket replies returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket not found'),
        ]
    )]
    public function getTicketReplies(Request $request, $uuid): JsonResponse
    {
        $ticket = Ticket::find($uuid);

        if (empty($ticket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $replies = $ticket->replies()
            ->with(['admin', 'user'])
            ->orderBy('created_at', 'asc')
            ->get();

        $repliesData = $replies->map(function ($reply) {
            $data = $reply->toArray();
            $data['attachments'] = $reply->getAttachments();

            return $data;
        });

        return response()->json([
            'data' => $repliesData,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.ticket_reply.put',
        description: 'Updates a ticket reply message. Only allowed for draft tickets.',
        summary: 'Update ticket reply',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket reply',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reply updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Reply not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putTicketReply(Request $request, $uuid): JsonResponse
    {
        $reply = TicketReply::find($uuid);

        if (empty($reply)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::with('status')->find($reply->ticket_uuid);

        if (!$ticket || !$ticket->isDraft()) {
            return response()->json([
                'message' => ['status' => [__('error.Reply can only be edited on draft tickets')]],
            ], 422);
        }

        $allowedTags = '<p><br><strong><em><ul><ol><li><a><code><pre><blockquote><h1><h2><h3><h4><h5><h6>';
        $reply->message = strip_tags($request->input('message'), $allowedTags);
        $reply->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.ticket_reply.attachment.post',
        description: 'Uploads a file attachment to a ticket reply.',
        summary: 'Upload ticket reply attachment',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['reply_uuid', 'file'],
                    properties: [
                        new OA\Property(property: 'reply_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attachment uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicketReplyAttachment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reply_uuid' => 'required|exists:ticket_replies,uuid',
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $uploadedFile = $request->file('file');
        $reply = TicketReply::find($request->input('reply_uuid'));
        $ticket = Ticket::with('department')->find($reply->ticket_uuid);

        // Per-department settings with global fallback
        $allowedExtensions = $ticket->department?->allowed_file_extensions
            ?? SettingService::get('support.ticket_allowed_file_extensions');
        if (!empty($allowedExtensions)) {
            $allowed = array_map('trim', explode(',', $allowedExtensions));
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            if (!in_array($extension, $allowed)) {
                return response()->json([
                    'message' => ['file' => [__('error.File extension not allowed')]],
                ], 422);
            }
        }

        $maxSizeMb = $ticket->department?->max_file_size_mb
            ?? SettingService::get('support.ticket_max_file_size_mb');
        if (!empty($maxSizeMb) && $uploadedFile->getSize() > (int) $maxSizeMb * 1024 * 1024) {
            return response()->json([
                'message' => ['file' => [__('error.File size exceeds maximum allowed')]],
            ], 422);
        }
        $ticketUuid = $reply->ticket_uuid;

        $filename = Str::uuid().'.'.$uploadedFile->getClientOriginalExtension();
        $directory = 'tickets/'.$ticketUuid;
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

    #[OA\Delete(
        path: 'admin.api.ticket_reply.attachment.delete',
        description: 'Deletes a ticket reply attachment by UUID.',
        summary: 'Delete ticket reply attachment',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the file attachment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attachment deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Attachment not found'),
        ]
    )]
    public function deleteTicketReplyAttachment(Request $request, $uuid): JsonResponse
    {
        $file = File::find($uuid);

        if (empty($file)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $file->deleteFile();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.ticket.publish.post',
        description: 'Publishes a draft ticket. Sets status to Open and fires TicketCreated hooks.',
        summary: 'Publish a draft ticket',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket published successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Published successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket not found'),
            new OA\Response(response: 422, description: 'Ticket is not a draft'),
        ]
    )]
    public function publishTicket(Request $request, $uuid): JsonResponse
    {
        $ticket = Ticket::with(['status', 'department'])->find($uuid);

        if (empty($ticket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (!$ticket->isDraft()) {
            return response()->json([
                'message' => ['status' => [__('error.Ticket is not a draft')]],
            ], 422);
        }

        $openStatus = TicketStatus::where('title', 'Open')->first();

        if (!$openStatus) {
            return response()->json(['errors' => [__('error.Default Open status not found. Run TicketStatusSeeder.')]], 500);
        }

        $ticket->status_uuid = $openStatus->uuid;
        $ticket->last_reply_at = now();
        $ticket->save();

        $ticket->load(['status', 'department', 'client']);
        app(HookService::class)->callHooks('TicketCreated', ['ticket' => $ticket]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Published successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.ticket.attachment.post',
        description: 'Uploads a file attachment to a ticket.',
        summary: 'Upload ticket attachment',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['ticket_uuid', 'file'],
                    properties: [
                        new OA\Property(property: 'ticket_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attachment uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicketAttachment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ticket_uuid' => 'required|exists:tickets,uuid',
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::with('department')->find($request->input('ticket_uuid'));
        $uploadedFile = $request->file('file');

        // Per-department settings with global fallback
        $allowedExtensions = $ticket->department?->allowed_file_extensions
            ?? SettingService::get('support.ticket_allowed_file_extensions');
        if (!empty($allowedExtensions)) {
            $allowed = array_map('trim', explode(',', $allowedExtensions));
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            if (!in_array($extension, $allowed)) {
                return response()->json([
                    'message' => ['file' => [__('error.File extension not allowed')]],
                ], 422);
            }
        }

        $maxSizeMb = $ticket->department?->max_file_size_mb
            ?? SettingService::get('support.ticket_max_file_size_mb');
        if (!empty($maxSizeMb) && $uploadedFile->getSize() > (int) $maxSizeMb * 1024 * 1024) {
            return response()->json([
                'message' => ['file' => [__('error.File size exceeds maximum allowed')]],
            ], 422);
        }

        $filename = Str::uuid().'.'.$uploadedFile->getClientOriginalExtension();
        $directory = 'tickets/'.$ticket->uuid;
        $path = $uploadedFile->storeAs($directory, $filename);

        $file = new File;
        $file->name = $uploadedFile->getClientOriginalName();
        $file->type = $uploadedFile->getMimeType();
        $file->size = $uploadedFile->getSize();
        $file->path = $path;
        $file->directory = $directory;
        $file->is_public = false;
        $file->model_type = Ticket::class;
        $file->model_uuid = $ticket->uuid;
        $file->model_field = 'attachment';
        $file->save();

        return response()->json([
            'status' => 'success',
            'data' => $file,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.ticket.attachment.delete',
        description: 'Deletes a ticket attachment by UUID.',
        summary: 'Delete ticket attachment',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the file attachment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attachment deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Attachment not found'),
        ]
    )]
    public function deleteTicketAttachment(Request $request, $uuid): JsonResponse
    {
        $file = File::where('uuid', $uuid)
            ->where('model_type', Ticket::class)
            ->first();

        if (empty($file)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $file->deleteFile();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.tickets.bulk_action.post',
        description: 'Performs a bulk action (close, assign, change_status) on multiple tickets.',
        summary: 'Bulk action on tickets',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['action', 'ticket_uuids'],
                    properties: [
                        new OA\Property(property: 'action', type: 'string', enum: ['close', 'assign', 'change_status']),
                        new OA\Property(property: 'ticket_uuids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
                        new OA\Property(property: 'status_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'assigned_admin_uuid', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(response: 200, description: 'Bulk action completed'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postBulkAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:close,assign,change_status',
            'ticket_uuids' => 'required|array|min:1',
            'ticket_uuids.*' => 'exists:tickets,uuid',
            'status_uuid' => 'required_if:action,change_status|nullable|exists:ticket_statuses,uuid',
            'assigned_admin_uuid' => 'required_if:action,assign|nullable|exists:admins,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $tickets = Ticket::whereIn('uuid', $request->input('ticket_uuids'))->get();
        $count = 0;

        foreach ($tickets as $ticket) {
            switch ($request->input('action')) {
                case 'close':
                    $closedStatus = TicketStatus::where('title', 'Closed')->first();
                    if ($closedStatus && ! $ticket->isClosed()) {
                        $ticket->status_uuid = $closedStatus->uuid;
                        $ticket->closed_at = now();
                        $ticket->save();
                        app(HookService::class)->callHooks('TicketClosed', ['ticket' => $ticket]);
                        $count++;
                    }
                    break;
                case 'assign':
                    $ticket->assigned_admin_uuid = $request->input('assigned_admin_uuid');
                    $ticket->save();
                    app(HookService::class)->callHooks('TicketAssigned', ['ticket' => $ticket]);
                    $count++;
                    break;
                case 'change_status':
                    $ticket->status_uuid = $request->input('status_uuid');
                    $newStatus = TicketStatus::find($request->input('status_uuid'));
                    if ($newStatus && strtolower($newStatus->title) === 'closed') {
                        $ticket->closed_at = now();
                    }
                    $ticket->save();
                    $count++;
                    break;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Bulk action completed on :count ticket(s)', ['count' => $count]),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ticket.activity.get',
        description: 'Returns the activity timeline for a ticket, including status changes, replies, and notes.',
        summary: 'Get ticket activity timeline',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Activity timeline'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getTicketActivity(Request $request, $uuid): JsonResponse
    {
        $ticket = Ticket::find($uuid);

        if (empty($ticket)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        // Pre-load lookup maps for resolving UUIDs
        $statuses = DB::table('ticket_statuses')->pluck('title', 'uuid');
        $departments = DB::table('ticket_departments')->pluck('name', 'uuid');
        $admins = DB::table('admins')
            ->select('uuid', DB::raw("CONCAT(firstname, ' ', lastname) as name"))
            ->pluck('name', 'uuid');

        $resolveFields = [
            'status_uuid' => $statuses,
            'department_uuid' => $departments,
            'assigned_admin_uuid' => $admins,
        ];

        $skipFields = ['updated_at', 'last_reply_at', 'time_spent_minutes', 'sla_reminder_sent'];

        $logs = DB::table('activity_logs')
            ->leftJoin('admins', 'activity_logs.admin_uuid', '=', 'admins.uuid')
            ->leftJoin('users', 'activity_logs.user_uuid', '=', 'users.uuid')
            ->leftJoin('clients', 'activity_logs.client_uuid', '=', 'clients.uuid')
            ->where('activity_logs.model_type', Ticket::class)
            ->where('activity_logs.model_uuid', $uuid)
            ->orderBy('activity_logs.created_at', 'desc')
            ->limit(50)
            ->select(
                'activity_logs.action',
                'activity_logs.model_old_data',
                'activity_logs.model_new_data',
                'activity_logs.created_at',
                DB::raw("CONCAT(admins.firstname, ' ', admins.lastname) as admin_name"),
                'users.email as user_email',
                DB::raw("CONCAT(clients.firstname, ' ', clients.lastname) as client_name")
            )
            ->get()
            ->map(function ($log) use ($resolveFields, $skipFields) {
                $oldData = json_decode($log->model_old_data, true) ?? [];
                $newData = json_decode($log->model_new_data, true) ?? [];

                // Filter out skip fields
                foreach ($skipFields as $field) {
                    unset($oldData[$field], $newData[$field]);
                }

                // Resolve UUIDs to names
                foreach ($resolveFields as $field => $map) {
                    if (isset($oldData[$field])) {
                        $oldData[$field] = $map[$oldData[$field]] ?? $oldData[$field];
                    }
                    if (isset($newData[$field])) {
                        $newData[$field] = $map[$newData[$field]] ?? $newData[$field];
                    }
                }

                // Format closed_at
                if (isset($newData['closed_at']) && $newData['closed_at']) {
                    $newData['closed_at'] = \Carbon\Carbon::parse($newData['closed_at'])->format('Y-m-d H:i');
                }

                $actorName = trim($log->admin_name ?? '');
                if (empty($actorName)) {
                    $actorName = trim($log->client_name ?? '');
                }
                if (empty($actorName)) {
                    $actorName = $log->user_email;
                }

                return [
                    'action' => $log->action,
                    'admin_name' => $actorName ?: null,
                    'old_data' => ! empty($oldData) ? $oldData : null,
                    'new_data' => ! empty($newData) ? $newData : null,
                    'created_at' => $log->created_at,
                ];
            })
            ->filter(function ($log) {
                // Remove empty update entries (nothing meaningful changed)
                if ($log['action'] === 'update' && empty($log['new_data'])) {
                    return false;
                }

                return true;
            })
            ->values();

        // Add replies as timeline events
        $replies = DB::table('ticket_replies')
            ->where('ticket_uuid', $uuid)
            ->orderBy('created_at', 'desc')
            ->select('name', 'is_admin_note', 'admin_uuid', 'created_at')
            ->get()
            ->map(function ($reply) {
                $type = $reply->is_admin_note ? 'note' : ($reply->admin_uuid ? 'reply_staff' : 'reply_client');

                return [
                    'action' => $type,
                    'admin_name' => $reply->name,
                    'old_data' => null,
                    'new_data' => null,
                    'created_at' => $reply->created_at,
                ];
            });

        $merged = $logs->concat($replies)
            ->sortByDesc('created_at')
            ->values()
            ->take(50);

        return response()->json(['data' => $merged]);
    }

    #[OA\Get(
        path: 'admin.api.ticket.attachment.get',
        description: 'Downloads or previews a ticket attachment file.',
        summary: 'Download ticket attachment',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'view', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: ['1'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File stream'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getTicketReplyAttachmentDownload(Request $request, $uuid): \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
    {
        $file = File::where('uuid', $uuid)
            ->whereIn('model_type', [TicketReply::class, Ticket::class])
            ->where('model_field', 'attachment')
            ->first();

        if (empty($file)) {
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

    #[OA\Get(
        path: 'admin.api.client.tickets.get',
        description: 'Returns a DataTable list of tickets for a specific client.',
        summary: 'Get client tickets',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Client tickets list'),
        ]
    )]
    public function getClientTickets(Request $request, $client_uuid): JsonResponse
    {
        $query = Ticket::query()
            ->where('client_uuid', $client_uuid)
            ->with(['department', 'status'])
            ->orderBy('last_reply_at', 'desc');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('subject', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('number', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($ticket) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('tickets-view')) {
                        $urls['edit'] = route('admin.web.ticket', $ticket->uuid);
                    }
                    if ($admin_online && $admin_online->hasPermission('tickets-delete')) {
                        $urls['delete'] = route('admin.api.ticket.delete', $ticket->uuid);
                    }

                    return $urls;
                })
                ->addColumn('department_name', function ($ticket) {
                    return $ticket->department?->name;
                })
                ->addColumn('status_data', function ($ticket) {
                    return [
                        'title' => $ticket->status?->title,
                        'color' => $ticket->status?->color,
                    ];
                })
                ->addColumn('reply_count', function ($ticket) {
                    return $ticket->replies()->count();
                })
                ->make(true),
        ], 200);
    }
}

