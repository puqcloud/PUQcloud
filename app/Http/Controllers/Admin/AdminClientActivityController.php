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
 * API Routes:
 *   GET    client/{uuid}/notifications                      -> getClientNotifications()    [admin.api.client.notifications.get]
 *   GET    client/{uuid}/activity-logs                      -> getClientActivityLogs()     [admin.api.client.activity_logs.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminClientActivityController extends Controller
{
    #[OA\Get(
        path: 'admin.api.client.notifications.get',
        description: 'Returns notification history for a specific client.',
        summary: 'Get client notifications',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the client', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Client notifications returned successfully'),
            new OA\Response(response: 404, description: 'Client not found'),
        ]
    )]
    public function getClientNotifications(Request $request, $uuid): JsonResponse
    {
        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $userUuids = DB::table('client_x_user')
            ->where('client_uuid', $uuid)
            ->pluck('user_uuid');

        $query = Notification::query()
            ->select([
                'notifications.uuid',
                'notifications.subject',
                'notifications.text_mini',
                'notifications.created_at',
                'notifications.model_type',
                'notifications.model_uuid',
            ])
            ->where('notifications.model_type', 'App\\Models\\User')
            ->whereIn('notifications.model_uuid', $userUuids);

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->start_date);
            $endDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->end_date);
            $query->whereBetween('notifications.created_at', [$startDate, $endDate]);
        }

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('notifications.subject', 'like', "%{$search}%")
                                ->orWhere('notifications.text_mini', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($notification) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('notification-history-view')) {
                        $urls['get'] = route('admin.api.notification_history.get', $notification->uuid);
                    }

                    return $urls;
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->order[0]['column'];
                        $dir = $request->order[0]['dir'];
                        if ($order == 0) {
                            $query->orderBy('notifications.created_at', $dir);
                        }
                    }
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.client.activity_logs.get',
        description: 'Returns activity logs for a specific client including related services and invoices.',
        summary: 'Get client activity logs',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the client', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Client activity logs returned successfully'),
            new OA\Response(response: 404, description: 'Client not found'),
        ]
    )]
    public function getClientActivityLogs(Request $request, $uuid): JsonResponse
    {
        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $userUuids = DB::table('client_x_user')
            ->where('client_uuid', $uuid)
            ->pluck('user_uuid');

        $serviceUuids = DB::table('services')
            ->where('client_uuid', $uuid)
            ->pluck('uuid');

        $invoiceUuids = DB::table('invoices')
            ->where('client_uuid', $uuid)
            ->pluck('uuid');

        $query = ActivityLog::query()
            ->select(['activity_logs.*'])
            ->where(function ($q) use ($uuid, $userUuids, $serviceUuids, $invoiceUuids) {
                $q->where('activity_logs.client_uuid', $uuid)
                    ->orWhereIn('activity_logs.user_uuid', $userUuids)
                    ->orWhere(function ($q2) use ($serviceUuids, $invoiceUuids) {
                        $q2->whereIn('activity_logs.model_uuid', $serviceUuids->merge($invoiceUuids));
                    });
            });

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->start_date);
            $endDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->end_date);
            $query->whereBetween('activity_logs.created_at', [$startDate, $endDate]);
        }

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('activity_logs.action', 'like', "%{$search}%")
                                ->orWhere('activity_logs.level', 'like', "%{$search}%")
                                ->orWhere('activity_logs.description', 'like', "%{$search}%")
                                ->orWhere('activity_logs.ip_address', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($activity_log) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('activity-log-view') && ! empty($activity_log->model_uuid)) {
                        $urls['get'] = route('admin.api.activity_log.get', $activity_log->uuid);
                    }

                    return $urls;
                })
                ->addColumn('initializer', function ($activity_log) {
                    $initializer = [
                        'name' => 'System',
                        'edit' => null,
                    ];
                    if ($activity_log->admin) {
                        $initializer['firstname'] = $activity_log->admin->firstname;
                        $initializer['lastname'] = $activity_log->admin->lastname;
                        $initializer['email'] = $activity_log->admin->email;
                        $initializer['gravatar'] = get_gravatar($activity_log->admin->email, 100);
                    }

                    return $initializer;
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->order[0]['column'];
                        $dir = $request->order[0]['dir'];
                        if ($order == 0) {
                            $query->orderBy('activity_logs.created_at', $dir);
                        }
                    }
                })
                ->make(true),
        ], 200);
    }
}
