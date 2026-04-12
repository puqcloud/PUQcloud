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
 *   GET  ticket_email_blacklist                -> ticketEmailBlacklist()           [admin.web.ticket_email_blacklist]
 *
 * API Routes:
 *   GET    ticket_email_blacklist              -> getTicketEmailBlacklist()        [admin.api.ticket_email_blacklist.get]
 *   POST   ticket_email_blacklist              -> postTicketEmailBlacklist()       [admin.api.ticket_email_blacklist.post]
 *   DELETE ticket_email_blacklist/{uuid}       -> deleteTicketEmailBlacklist()     [admin.api.ticket_email_blacklist.delete]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketEmailBlacklist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminTicketEmailBlacklistController extends Controller
{
    public function ticketEmailBlacklist(): View
    {
        $title = __('main.Email Blacklist');

        return view_admin('tickets.ticket_email_blacklist', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.ticket_email_blacklist.get',
        description: 'Returns a paginated list of blacklisted emails for DataTable.',
        summary: 'Get blacklisted emails',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        responses: [
            new OA\Response(response: 200, description: 'Blacklisted emails list'),
        ]
    )]
    public function getTicketEmailBlacklist(Request $request): JsonResponse
    {
        $query = DB::table('ticket_email_blacklist')
            ->leftJoin('admins', 'ticket_email_blacklist.admin_uuid', '=', 'admins.uuid')
            ->select(
                'ticket_email_blacklist.uuid',
                'ticket_email_blacklist.email',
                'ticket_email_blacklist.reason',
                'ticket_email_blacklist.created_at',
                DB::raw("CONCAT(admins.firstname, ' ', admins.lastname) as admin_name")
            );

        return response()->json([
            'data' => DataTables::of($query)
                ->addColumn('urls', function ($row) {
                    return [
                        'delete' => route('admin.api.ticket_email_blacklist.delete', $row->uuid),
                    ];
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.ticket_email_blacklist.post',
        description: 'Adds an email to the blacklist.',
        summary: 'Add email to blacklist',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'reason', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(response: 200, description: 'Email added to blacklist'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTicketEmailBlacklist(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:ticket_email_blacklist,email',
            'reason' => 'nullable|string|max:255',
        ], [
            'email.required' => __('error.The email field is required'),
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already in the blacklist'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $admin = app('admin');

        $entry = TicketEmailBlacklist::create([
            'email' => strtolower($request->input('email')),
            'reason' => $request->input('reason'),
            'admin_uuid' => $admin?->uuid,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $entry,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.ticket_email_blacklist.delete',
        description: 'Removes an email from the blacklist.',
        summary: 'Remove email from blacklist',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email removed from blacklist'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function deleteTicketEmailBlacklist(Request $request, $uuid): JsonResponse
    {
        $entry = TicketEmailBlacklist::find($uuid);

        if (empty($entry)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $entry->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }
}