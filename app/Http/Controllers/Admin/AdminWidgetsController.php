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

/*
 * WEB Routes:
 *   GET  (empty = dashboard)                                   -> dashboard()                       [admin.web.dashboard]
 *
 * API Routes:
 *   GET  dashboard/widgets                                     -> getDashboardWidgets()             [admin.api.dashboard.widgets.get]
 *   GET  dashboard/widget                                      -> getDashboardWidget()              [admin.api.dashboard.widget.get]
 *   GET  dashboard                                             -> getDashboard()                    [admin.api.dashboard.get]
 *   PUT  dashboard                                             -> putDashboard()                    [admin.api.dashboard.put]
 *   GET  client/{uuid}/summary/widgets                         -> getClientSummaryWidgets()         [admin.api.client.summary.widgets.get]
 *   GET  client/{uuid}/summary/widget                          -> getClientSummaryWidget()          [admin.api.client.summary.widget.get]
 *   GET  client/{uuid}/summary/dashboard                       -> getClientSummaryDashboard()       [admin.api.client.summary.dashboard.get]
 *   PUT  client/{uuid}/summary/dashboard                       -> putClientSummaryDashboard()       [admin.api.client.summary.dashboard.put]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\AdminWidgetsService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Http\Controllers\MasterSupervisorController;
use Mosquitto\Exception;
use Illuminate\Contracts\Console\Kernel;
use OpenApi\Attributes as OA;

class AdminWidgetsController extends Controller
{
    public function dashboard(): View
    {
        $title = __('main.Dashboard');

        return view_admin('dashboard.dashboard', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.dashboard.widgets.get',
        description: 'Returns the list of available dashboard widgets.',
        summary: 'Get dashboard widgets list',
        security: [['apiKeyAuth' => []]],
        tags: ['Widgets'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Widgets list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getDashboardWidgets(): JsonResponse
    {
        return response()->json([
            'data' => AdminWidgetsService::getAdminDashboardWidgets(),
        ], 200);

    }

    #[OA\Get(
        path: 'admin.api.dashboard.widget.get',
        description: 'Returns rendered HTML content for a specific dashboard widget by key.',
        summary: 'Get single dashboard widget',
        security: [['apiKeyAuth' => []]],
        tags: ['Widgets'],
        parameters: [
            new OA\Parameter(
                name: 'key',
                description: 'Widget key identifier',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Widget data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getDashboardWidget(Request $request): JsonResponse
    {
        if (! $request->has('key')) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $allowedWidgets = AdminWidgetsService::getAdminDashboardWidgets();

        foreach ($allowedWidgets as $widget) {
            if (! empty($widget['key']) && $widget['key'] == $request->key) {
                try {
                    if (! isset($widget['handler'])) {
                        continue;
                    }

                    [$handlerFile, $method] = explode('@', $widget['handler']);
                    $handlerFilePath = base_path($handlerFile);

                    if (! file_exists($handlerFilePath)) {
                        throw new Exception('Handler file not found: '.$handlerFilePath);
                    }

                    require_once $handlerFilePath;

                    $class = str_replace(['/', '.php'], ['\\', ''], $handlerFile);

                    if (class_exists($class)) {
                        if (! method_exists($class, $method)) {
                            throw new Exception("Method '$method' not found in class: ".$class);
                        }

                        $handlerInstance = new $class;
                        $response = $handlerInstance->$method($request);
                    } else {
                        if (! function_exists($method)) {
                            throw new Exception("Function '$method' not found.");
                        }
                        $response = call_user_func($method, $request);
                    }
                    if ($response instanceof JsonResponse) {
                        return $response;
                    } else {
                        return response()->json([
                            'data' => $response,
                        ]);
                    }

                } catch (Exception $e) {
                    Log::error($e->getMessage());

                    continue;
                }
            }
        }

        return response()->json([
            'errors' => [__('error.Not found')],
        ], 404);
    }

    #[OA\Get(
        path: 'admin.api.dashboard.get',
        description: 'Returns the current admin dashboard layout with widget positions.',
        summary: 'Get dashboard layout',
        security: [['apiKeyAuth' => []]],
        tags: ['Widgets'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard layout returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getDashboard(): JsonResponse
    {
        $admin = app('admin');
        $widgets = [];
        $allowedWidgets = AdminWidgetsService::getAdminDashboardWidgets();
        $dashboard = json_decode($admin->dashboard, true);

        if (empty($dashboard)) {
            $dashboard = $allowedWidgets;
        }

        foreach ($dashboard as $value1) {
            foreach ($allowedWidgets as $allowedWidget) {
                if ($value1['key'] == $allowedWidget['key']) {
                    $value1['name'] = $allowedWidget['name'];
                    $value1['icon'] = $allowedWidget['icon'];
                    $widgets[] = $value1;
                }
            }
        }

        return response()->json([
            'data' => $widgets,
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.dashboard.put',
        description: 'Updates the admin dashboard layout with new widget positions.',
        summary: 'Update dashboard layout',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['widgets'],
                    properties: [
                        new OA\Property(property: 'widgets', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            )
        ),
        tags: ['Widgets'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putDashboard(Request $request): JsonResponse
    {
        $admin = app('admin');

        if (! $admin) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $widgets = [];
        foreach ($request->input('widgets') as $widget) {
            $widgets[$widget['key']] = $widget;
        }
        $admin->dashboard = json_encode(array_values($widgets));
        $admin->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => '',
        ]);
    }

    public function getAutomationStatus(): string
    {
        $lastRun = Cache::get('cron_last_run');
        if (empty($lastRun)) {
            $icon = '<i class="fas fa-times-circle" style="color: red; font-size: 30px;"></i>';
            $statusText = 'Not Running';
        } else {
            if (now()->diffInMinutes($lastRun) > -2) {
                $icon = '<i class="fas fa-check-circle" style="color: green; font-size: 30px;"></i>';
                $statusText = 'Running';
            } else {
                $icon = '<i class="fas fa-times-circle" style="color: red; font-size: 30px;"></i>';
                $statusText = 'Not Running';
            }
        }

        $artisan = app(Kernel::class);
        $exitCode = $artisan->call('generate:horizon-config');
        $output = trim($artisan->output());
        $horizonConfig = json_decode($output, true);
        $queues = $horizonConfig['queues'];

        $activeQueues = true;
        $inactiveQueueNames = [];

        foreach ($queues as $queue) {
            $isQueueActive = $this->isQueueActive($queue);
            if (! $isQueueActive) {
                $activeQueues = false;
                $inactiveQueueNames[] = $queue;
            }
        }

        $queueStatusIcon = $activeQueues
            ? '<i class="fas fa-check-circle" style="color: green; font-size: 30px;"></i>'
            : '<i class="fas fa-times-circle" style="color: red; font-size: 30px;"></i>';
        $queueStatusText = $activeQueues ? 'All Queues Running' : 'Inactive Queues: '.count($inactiveQueueNames);

        $html = view_admin('dashboard.widgets.automation_status', [
            'icon' => $icon,
            'statusText' => $statusText,
            'lastRun' => $lastRun,
            'queueStatusIcon' => $queueStatusIcon,
            'queueStatusText' => $queueStatusText,
            'queues' => $queues,
        ])->render();

        return $html;
    }

    private function isQueueActive($queueName)
    {
        $masters = app(MasterSupervisorRepository::class);
        $supervisors = app(SupervisorRepository::class);

        $master = app(MasterSupervisorController::class);
        $response = $master->index($masters, $supervisors);

        foreach ($response as $master) {
            foreach ($master->supervisors as $supervisor) {
                if ($supervisor->status === 'running' && in_array($queueName, explode(',', $supervisor->options['queue']))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getTaskQueue(): string
    {
        $statuses = [
            'queued',
            'pending',

            'completed',
            'failed',

            'processing',
            'duplicate'];

        $taskCounts = DB::table('tasks')
            ->select(DB::raw('status, COUNT(*) as count'))
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $html = view_admin('dashboard.widgets.task_status_queue', [
            'taskCounts' => $taskCounts,
            'statuses' => $statuses,
        ])->render();

        return $html;
    }

    public function getStaffOnline(): string
    {
        $now = Carbon::now();
        $oneHourAgo = $now->copy()->subHour();

        $activeAdmins = DB::table('admin_session_logs')
            ->select('admins.uuid', 'admins.email', 'admins.firstname', 'admins.lastname', DB::raw('MAX(admin_session_logs.created_at) as last_seen'))
            ->join('admins', 'admin_session_logs.admin_uuid', '=', 'admins.uuid')
            ->where('admin_session_logs.created_at', '>=', $oneHourAgo)
            ->groupBy('admins.email', 'admins.uuid', 'admins.firstname', 'admins.lastname')
            ->orderByDesc('last_seen')
            ->get();

        $result = [];

        foreach ($activeAdmins as $admin) {
            $lastSeenTime = Carbon::parse($admin->last_seen);
            $diffInSeconds = ceil($lastSeenTime->diffInSeconds($now));
            $diffInMinutes = ceil($lastSeenTime->diffInMinutes($now));

            if ($diffInSeconds < 60) {
                $lastSeenText = "$diffInSeconds seconds ago";
            } elseif ($diffInMinutes < 60) {
                $lastSeenText = "$diffInMinutes minutes ago";
            } else {
                $diffInHours = $lastSeenTime->diffInHours($now);
                $lastSeenText = "$diffInHours hours ago";
            }

            $result[] = [
                'uuid' => $admin->uuid,
                'name' => $admin->firstname.' '.$admin->lastname,
                'email' => $admin->email,
                'last_seen' => $lastSeenText,
                'gravatar' => get_gravatar($admin->email, 100),
            ];
        }

        $html = view_admin('dashboard.widgets.staff_online', [
            'admins' => $result,
        ])->render();

        return $html;
    }

    public function getPUQcloudInfo(): string
    {
        $version = file_get_contents(base_path('version'));
        $uptime = shell_exec('uptime -p');
        $phpVersion = PHP_VERSION;
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

        $laravelTimezone = now()->timezoneName;
        $laravelCurrentTime = now()->toDateTimeString();

        $phpTimezone = date_default_timezone_get();
        $phpCurrentTime = date('Y-m-d H:i:s');

        $mysqlTimezone = DB::select('SELECT @@session.time_zone as tz')[0]->tz;
        $mysqlCurrentTime = DB::select('SELECT NOW() as now')[0]->now;

        $data = [
            'version' => $version,
            'uptime' => $uptime,
            'phpVersion' => $phpVersion,
            'serverSoftware' => $serverSoftware,
            'laravelTimezone' => $laravelTimezone,
            'laravelCurrentTime' => $laravelCurrentTime,
            'phpTimezone' => $phpTimezone,
            'phpCurrentTime' => $phpCurrentTime,
            'mysqlTimezone' => $mysqlTimezone,
            'mysqlCurrentTime' => $mysqlCurrentTime,
        ];

        $html = view_admin('dashboard.widgets.puqcloud_info', $data)->render();

        return $html;
    }

    public function getServicesOverview(): string
    {
        $statuses = ['active', 'suspended', 'pending', 'terminated', 'cancelled'];

        $serviceCounts = DB::table('services')
            ->select(DB::raw('status, COUNT(*) as count'))
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalServices = DB::table('services')->count();
        $idleCount = DB::table('services')->where('idle', true)->where('status', 'active')->count();
        $terminationRequests = DB::table('services')->where('termination_request', true)->whereNotIn('status', ['terminated', 'cancelled'])->count();

        $html = view_admin('dashboard.widgets.services_overview', [
            'serviceCounts' => $serviceCounts,
            'statuses' => $statuses,
            'totalServices' => $totalServices,
            'idleCount' => $idleCount,
            'terminationRequests' => $terminationRequests,
        ])->render();

        return $html;
    }

    public function getFinancialSummary(): string
    {
        $unpaid = DB::table('invoices')->where('status', 'unpaid')->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')->first();
        $draft = DB::table('invoices')->where('status', 'draft')->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')->first();

        $paidMonth = DB::table('invoices')
            ->where('status', 'paid')
            ->whereMonth('paid_date', now()->month)
            ->whereYear('paid_date', now()->year)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        $dueTodayCount = DB::table('invoices')->where('status', 'unpaid')->whereDate('due_date', today())->count();
        $overdueCount = DB::table('invoices')->where('status', 'unpaid')->whereDate('due_date', '<', today())->count();

        $html = view_admin('dashboard.widgets.financial_summary', [
            'unpaidCount' => $unpaid->count,
            'unpaidTotal' => $unpaid->total,
            'draftCount' => $draft->count,
            'draftTotal' => $draft->total,
            'paidMonthCount' => $paidMonth->count,
            'paidMonthTotal' => $paidMonth->total,
            'dueTodayCount' => $dueTodayCount,
            'overdueCount' => $overdueCount,
        ])->render();

        return $html;
    }

    public function getClientsOverview(): string
    {
        $totalClients = DB::table('clients')->count();
        $newThisMonth = DB::table('clients')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $newToday = DB::table('clients')->whereDate('created_at', today())->count();

        $withActiveServices = DB::table('clients')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('services')
                    ->whereColumn('services.client_uuid', 'clients.uuid')
                    ->where('services.status', 'active');
            })
            ->count();

        $withUnpaidInvoices = DB::table('clients')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('invoices')
                    ->whereColumn('invoices.client_uuid', 'clients.uuid')
                    ->where('invoices.status', 'unpaid');
            })
            ->count();

        $html = view_admin('dashboard.widgets.clients_overview', [
            'totalClients' => $totalClients,
            'newThisMonth' => $newThisMonth,
            'newToday' => $newToday,
            'withActiveServices' => $withActiveServices,
            'withUnpaidInvoices' => $withUnpaidInvoices,
        ])->render();

        return $html;
    }

    public function getSslCertificatesStatus(): string
    {
        $statuses = ['active', 'expired', 'pending', 'draft', 'revoked', 'failed'];

        $certCounts = DB::table('ssl_certificates')
            ->select(DB::raw('status, COUNT(*) as count'))
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $expiringSoon = DB::table('ssl_certificates')
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->where('expires_at', '>', now())
            ->count();

        $html = view_admin('dashboard.widgets.ssl_certificates_status', [
            'certCounts' => $certCounts,
            'statuses' => $statuses,
            'expiringSoon' => $expiringSoon,
        ])->render();

        return $html;
    }

    public function getTicketsOverview(): string
    {
        $newOpen = DB::table('tickets')
            ->join('ticket_statuses', 'tickets.status_uuid', '=', 'ticket_statuses.uuid')
            ->where('ticket_statuses.title', 'Open')
            ->count();

        $customerReply = DB::table('tickets')
            ->join('ticket_statuses', 'tickets.status_uuid', '=', 'ticket_statuses.uuid')
            ->where('ticket_statuses.title', 'Customer-Reply')
            ->count();

        $answered = DB::table('tickets')
            ->join('ticket_statuses', 'tickets.status_uuid', '=', 'ticket_statuses.uuid')
            ->where('ticket_statuses.title', 'Answered')
            ->count();

        return view_admin('dashboard.widgets.tickets_overview', [
            'newOpen' => $newOpen,
            'customerReply' => $customerReply,
            'answered' => $answered,
        ])->render();
    }

    public function getRecentActivity(): string
    {
        $activities = DB::table('activity_logs')
            ->leftJoin('admins', 'activity_logs.admin_uuid', '=', 'admins.uuid')
            ->select(
                'activity_logs.created_at',
                'activity_logs.description',
                'activity_logs.model_type',
                'activity_logs.action',
                DB::raw("CONCAT(admins.firstname, ' ', admins.lastname) as admin_name")
            )
            ->orderByDesc('activity_logs.created_at')
            ->limit(15)
            ->get();

        $html = view_admin('dashboard.widgets.recent_activity', [
            'activities' => $activities,
        ])->render();

        return $html;
    }

    // Client Summary --------------------------------------------------------------------------------------------------
    #[OA\Get(
        path: 'admin.api.client.summary.widgets.get',
        description: 'Returns the list of available client summary widgets.',
        summary: 'Get client summary widgets list',
        security: [['apiKeyAuth' => []]],
        tags: ['Widgets'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client summary widgets list returned',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClientSummaryWidgets(): JsonResponse
    {
        return response()->json([
            'data' => AdminWidgetsService::getAdminClientSummaryWidgets(),
        ], 200);

    }

    #[OA\Get(
        path: 'admin.api.client.summary.widget.get',
        description: 'Returns rendered content for a specific client summary widget.',
        summary: 'Get single client summary widget',
        security: [['apiKeyAuth' => []]],
        tags: ['Widgets'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'key',
                description: 'Widget key identifier',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Widget data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClientSummaryWidget(Request $request, $uuid): JsonResponse
    {
        if (! $request->has('key')) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $allowedWidgets = AdminWidgetsService::getAdminClientSummaryWidgets();

        foreach ($allowedWidgets as $widget) {
            if (! empty($widget['key']) && $widget['key'] == $request->key) {
                try {
                    if (! isset($widget['handler'])) {
                        continue;
                    }

                    [$handlerFile, $method] = explode('@', $widget['handler']);
                    $handlerFilePath = base_path($handlerFile);

                    if (! file_exists($handlerFilePath)) {
                        throw new Exception('Handler file not found: '.$handlerFilePath);
                    }

                    require_once $handlerFilePath;

                    $class = str_replace(['/', '.php'], ['\\', ''], $handlerFile);

                    if (class_exists($class)) {
                        if (! method_exists($class, $method)) {
                            throw new Exception("Method '$method' not found in class: ".$class);
                        }

                        $handlerInstance = new $class;
                        $response = $handlerInstance->$method($request, $uuid);
                    } else {
                        if (! function_exists($method)) {
                            throw new Exception("Function '$method' not found.");
                        }
                        $response = call_user_func($method, $request, $uuid);
                    }
                    if ($response instanceof JsonResponse) {
                        return $response;
                    } else {
                        return response()->json([
                            'data' => $response,
                        ]);
                    }

                } catch (Exception $e) {
                    Log::error($e->getMessage());

                    continue;
                }
            }
        }

        return response()->json([
            'errors' => [__('error.Not found')],
        ], 404);
    }

    #[OA\Get(
        path: 'admin.api.client.summary.dashboard.get',
        description: 'Returns the client summary dashboard layout with widget positions.',
        summary: 'Get client summary dashboard layout',
        security: [['apiKeyAuth' => []]],
        tags: ['Widgets'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client summary dashboard returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClientSummaryDashboard(): JsonResponse
    {
        $admin = app('admin');
        $widgets = [];
        $allowedWidgets = AdminWidgetsService::getAdminClientSummaryWidgets();
        $dashboard = json_decode($admin->client_summary_dashboard, true);

        if (empty($dashboard)) {
            $dashboard = $allowedWidgets;
        }

        foreach ($dashboard as $value1) {
            foreach ($allowedWidgets as $allowedWidget) {
                if ($value1['key'] == $allowedWidget['key']) {
                    $value1['name'] = $allowedWidget['name'];
                    $value1['icon'] = $allowedWidget['icon'];
                    $widgets[] = $value1;
                }
            }
        }

        return response()->json([
            'data' => $widgets,
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.client.summary.dashboard.put',
        description: 'Updates the client summary dashboard layout.',
        summary: 'Update client summary dashboard layout',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['widgets'],
                    properties: [
                        new OA\Property(property: 'widgets', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            )
        ),
        tags: ['Widgets'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putClientSummaryDashboard(Request $request): JsonResponse
    {
        $admin = app('admin');

        if (! $admin) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $widgets = [];
        foreach ($request->input('widgets') as $widget) {
            $widgets[$widget['key']] = $widget;
        }
        $admin->client_summary_dashboard = json_encode(array_values($widgets));
        $admin->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => '',
        ]);
    }

    public function getClientInformation(Request $request, $uuid): string
    {
        $client = Client::find($uuid);
        $data = [
            'client' => $client,
            'owner' => $client->owner(),
            'billingAddress' => $client->billingAddress(),
            'language' => $client->language(),
        ];

        $html = view_admin('clients.widgets.client_information', $data)->render();

        return $html;
    }

    public function getClientFinance(Request $request, $uuid): string
    {
        $client = Client::find($uuid);
        $data = [
            'client' => $client,
            'currency' => $client->currency,
        ];

        $html = view_admin('clients.widgets.client_finance', $data)->render();

        return $html;
    }

    public function getClientActions(Request $request, $uuid): string
    {
        $data = [
            'uuid' => $uuid,
        ];

        $html = view_admin('clients.widgets.actions', $data)->render();

        return $html;
    }

    public function getClientServices(Request $request, $uuid): string
    {
        $locale = config('locale.admin.default');

        $services = DB::table('services')
            ->leftJoin('prices', 'services.price_uuid', '=', 'prices.uuid')
            ->leftJoin('translations', function ($join) use ($locale) {
                $join->on('translations.model_uuid', '=', 'services.product_uuid')
                    ->where('translations.model_type', '=', 'App\\Models\\Product')
                    ->where('translations.field', '=', 'name')
                    ->where('translations.locale', '=', $locale);
            })
            ->where('services.client_uuid', $uuid)
            ->select(
                'services.uuid',
                'services.status',
                'services.termination_request',
                'services.idle',
                'translations.value as product_name',
                'prices.base as price_base',
                'prices.period as price_period'
            )
            ->orderByDesc('services.created_at')
            ->get();

        $html = view_admin('clients.widgets.client_services', [
            'services' => $services,
        ])->render();

        return $html;
    }

    public function getClientInvoices(Request $request, $uuid): string
    {
        $invoices = DB::table('invoices')
            ->where('client_uuid', $uuid)
            ->select('uuid', 'number', 'type', 'status', 'issue_date', 'total', 'currency_code')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $unpaidTotal = DB::table('invoices')
            ->where('client_uuid', $uuid)
            ->where('status', 'unpaid')
            ->sum('total');

        $currencyCode = DB::table('clients')
            ->join('currencies', 'clients.currency_uuid', '=', 'currencies.uuid')
            ->where('clients.uuid', $uuid)
            ->value('currencies.code') ?? '';

        $html = view_admin('clients.widgets.client_invoices', [
            'invoices' => $invoices,
            'unpaidTotal' => $unpaidTotal,
            'currencyCode' => $currencyCode,
        ])->render();

        return $html;
    }

    public function getClientTransactions(Request $request, $uuid): string
    {
        $locale = config('locale.admin.default');

        $transactions = DB::table('transactions')
            ->leftJoin('translations', function ($join) use ($locale) {
                $join->on('translations.model_uuid', '=', 'transactions.payment_gateway_uuid')
                    ->where('translations.model_type', '=', 'App\\Models\\PaymentGateway')
                    ->where('translations.field', '=', 'name')
                    ->where('translations.locale', '=', $locale);
            })
            ->where('transactions.client_uuid', $uuid)
            ->select(
                'transactions.uuid',
                'transactions.transaction_date',
                'transactions.type',
                'transactions.amount_gross',
                'transactions.currency_code',
                'translations.value as gateway_name'
            )
            ->orderByDesc('transactions.transaction_date')
            ->limit(15)
            ->get();

        $html = view_admin('clients.widgets.client_transactions', [
            'transactions' => $transactions,
        ])->render();

        return $html;
    }

    public function getClientNotes(Request $request, $uuid): string
    {
        $client = DB::table('clients')->where('uuid', $uuid)->select('admin_notes', 'notes')->first();

        $html = view_admin('clients.widgets.client_notes', [
            'adminNotes' => $client->admin_notes ?? '',
            'clientNotes' => $client->notes ?? '',
        ])->render();

        return $html;
    }

    public function getClientRecurringPayments(Request $request, $uuid): string
    {
        $client = Client::find($uuid);
        $totals = $client->calculateRecurringPaymentsBreakdown();

        $html = view_admin('clients.widgets.client_recurring_payments', [
            'totals' => $totals,
        ])->render();

        return $html;
    }
}
