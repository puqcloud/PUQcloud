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

class AdminWidgetsController extends Controller
{
    public function dashboard(): View
    {
        $title = __('main.Dashboard');

        return view_admin('dashboard.dashboard', compact('title'));
    }

    public function getDashboardWidgets(): JsonResponse
    {
        return response()->json([
            'data' => AdminWidgetsService::getAdminDashboardWidgets(),
        ], 200);

    }

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

    // Client Summary --------------------------------------------------------------------------------------------------
    public function getClientSummaryWidgets(): JsonResponse
    {
        return response()->json([
            'data' => AdminWidgetsService::getAdminClientSummaryWidgets(),
        ], 200);

    }

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
}
