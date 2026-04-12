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
 *   GET  ticket_statuses                       -> ticketStatuses()               [admin.web.ticket_statuses]
 *
 * API Routes:
 *   GET    ticket_statuses                     -> getTicketStatuses()            [admin.api.ticket_statuses.get]
 *   POST   ticket_status                       -> postTicketStatus()             [admin.api.ticket_status.post]
 *   GET    ticket_status/{uuid}                -> getTicketStatus()              [admin.api.ticket_status.get]
 *   PUT    ticket_status/{uuid}                -> putTicketStatus()              [admin.api.ticket_status.put]
 *   DELETE ticket_status/{uuid}                -> deleteTicketStatus()           [admin.api.ticket_status.delete]
 *   GET    ticket_statuses/select              -> getTicketStatusesSelect()      [admin.api.ticket_statuses.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminTicketStatusesController extends Controller
{
    public function ticketStatuses(): View
    {
        $title = __('main.Ticket Statuses');

        return view_admin('tickets.ticket_statuses', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.ticket_statuses.get',
        description: 'Returns a DataTable list of ticket statuses.',
        summary: 'Get ticket statuses list',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket statuses list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getTicketStatuses(Request $request): JsonResponse
    {
        $query = TicketStatus::query()->orderBy('sort_order', 'asc');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('title', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($status) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('tickets-statuses')) {
                        $urls['get'] = route('admin.api.ticket_status.get', $status->uuid);
                        $urls['delete'] = route('admin.api.ticket_status.delete', $status->uuid);
                    }

                    return $urls;
                })
                ->addColumn('ticket_count', function ($status) {
                    return $status->tickets()->count();
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.ticket_status.post',
        description: 'Creates a new ticket status.',
        summary: 'Create a new ticket status',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['title', 'color'],
                    properties: [
                        new OA\Property(property: 'title', description: 'Status title', type: 'string', example: 'On Hold'),
                        new OA\Property(property: 'color', description: 'Status color hex code', type: 'string', example: '#ff9800'),
                        new OA\Property(property: 'include_in_active', description: 'Include in active tickets filter', type: 'boolean'),
                        new OA\Property(property: 'include_in_awaiting_reply', description: 'Include in awaiting reply filter', type: 'boolean'),
                        new OA\Property(property: 'auto_close_days', description: 'Auto close after days (0 = disabled)', type: 'integer'),
                        new OA\Property(property: 'sort_order', description: 'Sort order', type: 'integer'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket status created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicketStatus(Request $request): JsonResponse
    {
        foreach (['include_in_active', 'include_in_awaiting_reply'] as $field) {
            $request->merge([$field => $request->get($field) === 'yes' || $request->get($field) === true || $request->get($field) === '1']);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'color' => 'required|string|max:7',
            'include_in_active' => 'boolean',
            'include_in_awaiting_reply' => 'boolean',
            'auto_close_days' => 'nullable|integer|min:0',
            'sort_order' => 'integer',
        ], [
            'title.required' => __('error.The Title field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $status = new TicketStatus;
        $status->title = $request->get('title');
        $status->color = $request->get('color');
        $status->include_in_active = $request->get('include_in_active', false);
        $status->include_in_awaiting_reply = $request->get('include_in_awaiting_reply', false);
        $status->auto_close_days = $request->get('auto_close_days');
        $status->sort_order = $request->get('sort_order', 0);
        $status->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $status,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ticket_status.get',
        description: 'Returns detailed information about a ticket status.',
        summary: 'Get ticket status by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket status',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket status data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket status not found'),
        ]
    )]
    public function getTicketStatus(Request $request, $uuid): JsonResponse
    {
        $model = TicketStatus::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'data' => $model->toArray(),
        ]);
    }

    #[OA\Put(
        path: 'admin.api.ticket_status.put',
        description: 'Updates a ticket status by UUID.',
        summary: 'Update ticket status by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['title', 'color'],
                    properties: [
                        new OA\Property(property: 'title', description: 'Status title', type: 'string', example: 'On Hold'),
                        new OA\Property(property: 'color', description: 'Status color hex code', type: 'string', example: '#ff9800'),
                        new OA\Property(property: 'include_in_active', description: 'Include in active tickets filter', type: 'boolean'),
                        new OA\Property(property: 'include_in_awaiting_reply', description: 'Include in awaiting reply filter', type: 'boolean'),
                        new OA\Property(property: 'auto_close_days', description: 'Auto close after days (0 = disabled)', type: 'integer'),
                        new OA\Property(property: 'sort_order', description: 'Sort order', type: 'integer'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket status',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket status not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putTicketStatus(Request $request, $uuid): JsonResponse
    {
        $model = TicketStatus::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        foreach (['include_in_active', 'include_in_awaiting_reply'] as $field) {
            $request->merge([$field => $request->get($field) === 'yes' || $request->get($field) === true || $request->get($field) === '1']);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'color' => 'required|string|max:7',
            'include_in_active' => 'boolean',
            'include_in_awaiting_reply' => 'boolean',
            'auto_close_days' => 'nullable|integer|min:0',
            'sort_order' => 'integer',
        ], [
            'title.required' => __('error.The Title field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $fields = [
            'title', 'color', 'include_in_active', 'include_in_awaiting_reply',
            'auto_close_days', 'sort_order',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $model->$field = $request->get($field);
            }
        }

        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.ticket_status.delete',
        description: 'Deletes a ticket status by UUID. Cannot delete default statuses or statuses with existing tickets.',
        summary: 'Delete ticket status by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket status to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket status deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 403, description: 'Cannot delete default status or status with existing tickets'),
            new OA\Response(response: 404, description: 'Ticket status not found'),
        ]
    )]
    public function deleteTicketStatus(Request $request, $uuid): JsonResponse
    {
        $model = TicketStatus::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($model->is_default) {
            return response()->json([
                'errors' => [__('error.Cannot delete default statuses')],
            ], 403);
        }

        if ($model->tickets()->count() > 0) {
            return response()->json([
                'errors' => [__('error.Cannot delete status with existing tickets')],
            ], 403);
        }

        try {
            $deleted = $model->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('error.Deletion failed:') . ' ' . $e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ticket_statuses.select.get',
        description: 'Returns ticket statuses for Select2 dropdown.',
        summary: 'Get ticket statuses for select',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false,
                schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ticket statuses select list'),
        ]
    )]
    public function getTicketStatusesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $query = TicketStatus::query()->orderBy('sort_order');

        if (! empty($search)) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        $results = $query->get()->map(function ($status) {
            return ['id' => $status->uuid, 'text' => $status->title];
        });

        return response()->json(['data' => ['results' => $results, 'pagination' => ['more' => false]]]);
    }
}
