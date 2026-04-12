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
 *   GET  ticket_canned_responses                  -> ticketCannedResponses()              [admin.web.ticket_canned_responses]
 *
 * API Routes:
 *   GET    ticket_canned_responses                -> getTicketCannedResponses()            [admin.api.ticket_canned_responses.get]
 *   POST   ticket_canned_response                 -> postTicketCannedResponse()            [admin.api.ticket_canned_response.post]
 *   GET    ticket_canned_response/{uuid}          -> getTicketCannedResponse()             [admin.api.ticket_canned_response.get]
 *   PUT    ticket_canned_response/{uuid}          -> putTicketCannedResponse()             [admin.api.ticket_canned_response.put]
 *   DELETE ticket_canned_response/{uuid}          -> deleteTicketCannedResponse()          [admin.api.ticket_canned_response.delete]
 *   GET    ticket_canned_responses/select         -> getTicketCannedResponsesSelect()      [admin.api.ticket_canned_responses.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketCannedResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminTicketCannedResponsesController extends Controller
{
    public function ticketCannedResponses(): View
    {
        $title = __('main.Canned Responses');

        return view_admin('tickets.ticket_canned_responses', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.ticket_canned_responses.get',
        description: 'Returns a paginated list of canned responses for DataTable.',
        summary: 'Get canned responses',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        responses: [
            new OA\Response(response: 200, description: 'Canned responses list'),
        ]
    )]
    public function getTicketCannedResponses(Request $request): JsonResponse
    {
        $query = TicketCannedResponse::query()
            ->with('department')
            ->orderBy('sort_order', 'asc');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('message', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('department_name', function ($item) {
                    return $item->department?->name ?? '';
                })
                ->addColumn('urls', function ($item) {
                    return [
                        'delete' => route('admin.api.ticket_canned_response.delete', $item->uuid),
                    ];
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.ticket_canned_response.post',
        description: 'Creates a new canned response.',
        summary: 'Create canned response',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['title', 'message'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', maxLength: 255),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'department_uuid', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'sort_order', type: 'integer'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(response: 200, description: 'Canned response created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicketCannedResponse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'department_uuid' => 'nullable|exists:ticket_departments,uuid',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new TicketCannedResponse;
        $model->title = $request->input('title');
        $model->message = $request->input('message');
        $model->department_uuid = $request->input('department_uuid') ?: null;
        $model->sort_order = $request->input('sort_order', 0);
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ticket_canned_response.get',
        description: 'Returns a single canned response by UUID.',
        summary: 'Get canned response',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Canned response data'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getTicketCannedResponse(Request $request, $uuid): JsonResponse
    {
        $model = TicketCannedResponse::with('department')->find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        if ($model->department) {
            $data['department_data'] = ['id' => $model->department->uuid, 'text' => $model->department->name];
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.ticket_canned_response.put',
        description: 'Updates a canned response.',
        summary: 'Update canned response',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['title', 'message'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', maxLength: 255),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'department_uuid', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'sort_order', type: 'integer'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Canned response updated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putTicketCannedResponse(Request $request, $uuid): JsonResponse
    {
        $model = TicketCannedResponse::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'department_uuid' => 'nullable|exists:ticket_departments,uuid',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->title = $request->input('title');
        $model->message = $request->input('message');
        $model->department_uuid = $request->input('department_uuid') ?: null;
        $model->sort_order = $request->input('sort_order', 0);
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.ticket_canned_response.delete',
        description: 'Deletes a canned response.',
        summary: 'Delete canned response',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Canned response deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function deleteTicketCannedResponse(Request $request, $uuid): JsonResponse
    {
        $model = TicketCannedResponse::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $model->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ticket_canned_responses.select.get',
        description: 'Returns canned responses for Select2 dropdown, optionally filtered by department.',
        summary: 'Get canned responses for select',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'department_uuid', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'q', in: 'query', required: false,
                schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Canned responses select list'),
        ]
    )]
    public function getTicketCannedResponsesSelect(Request $request): JsonResponse
    {
        $query = TicketCannedResponse::orderBy('sort_order', 'asc');

        if ($request->filled('department_uuid')) {
            $query->where(function ($q) use ($request) {
                $q->where('department_uuid', $request->input('department_uuid'))
                    ->orWhereNull('department_uuid');
            });
        }

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->input('q') . '%');
        }

        $items = $query->get();

        return response()->json(['data' => [
            'results' => $items->map(fn ($item) => ['id' => $item->uuid, 'text' => $item->title]),
            'pagination' => ['more' => false],
        ]]);
    }
}
