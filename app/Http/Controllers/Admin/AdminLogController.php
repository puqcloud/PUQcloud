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
use App\Models\ActivityLog;
use App\Models\AdminSessionLog;
use App\Models\ClientSessionLog;
use App\Models\ModuleLog;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdminLogController extends Controller
{
    public function adminSessions(): View
    {
        $title = __('main.Admin Sessions');

        return view_admin('admin_session_logs.admin_session_logs', compact('title'));
    }

    public function getAdminSessions(Request $request): JsonResponse
    {
        $query = AdminSessionLog::with('admin');

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->start_date);
            $endDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->end_date);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($request->has('hide_me') && $request->hide_me) {
            $admin = app('admin');
            $query->where('admin_uuid', '<>', $admin->uuid);
        }

        return response()->json([
            'data' => DataTables::eloquent($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('created_at', 'like', "%{$search}%")
                                ->orWhere('admin_uuid', 'like', "%{$search}%")
                                ->orWhere('ip_address', 'like', "%{$search}%")
                                ->orWhereHas('admin', function ($q2) use ($search) {
                                    $q2->where('email', 'like', "%{$search}%")
                                        ->orWhere('firstname', 'like', "%{$search}%")
                                        ->orWhere('lastname', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->order[0]['column'];
                        $dir = $request->order[0]['dir'];
                        if ($order == 0) {
                            $query->orderBy('created_at', $dir);
                        }
                    }
                })
                ->make(true),
        ], 200);
    }

    public function activityLogs(): View
    {
        $title = __('main.Activity Log');

        return view_admin('activity_logs.activity_logs', compact('title'));
    }

    public function getActivityLogs(Request $request): JsonResponse
    {
        $query = ActivityLog::query()
            ->select([
                'activity_logs.*',
            ]);

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
                            $q->where('activity_logs.created_at', 'like', "%{$search}%")
                                ->orWhere('activity_logs.admin_uuid', 'like', "%{$search}%")
                                ->orWhere('activity_logs.user_uuid', 'like', "%{$search}%")
                                ->orWhere('activity_logs.client_uuid', 'like', "%{$search}%")
                                ->orWhere('activity_logs.action', 'like', "%{$search}%")
                                ->orWhere('activity_logs.level', 'like', "%{$search}%")
                                ->orWhere('activity_logs.description', 'like', "%{$search}%")
                                ->orWhere('activity_logs.ip_address', 'like', "%{$search}%")
                                ->orWhere('activity_logs.model_type', 'like', "%{$search}%")
                                ->orWhere('activity_logs.model_uuid', 'like', "%{$search}%")
                                ->orWhere('activity_logs.model_old_data', 'like', "%{$search}%")
                                ->orWhere('activity_logs.model_new_data', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($activity_log) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('activity-log-view')) {
                        if (! empty($activity_log->model_uuid)) {
                            $urls['get'] = route('admin.api.activity_log.get', $activity_log->uuid);
                        }
                    }

                    return $urls;
                })
                ->addColumn('initializer', function ($activity_log) {
                    $initializer = [
                        'name' => 'System',
                        'web_edit' => null,
                    ];
                    $admin_online = app('admin');
                    if ($activity_log->admin) {
                        $initializer['firstname'] = $activity_log->admin->firstname;
                        $initializer['lastname'] = $activity_log->admin->lastname;
                        $initializer['email'] = $activity_log->admin->email;
                        if ($admin_online->hasPermission('admins-view')) {
                            $initializer['web_edit'] = route('admin.web.admin', $activity_log->admin->uuid);
                        }
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

    public function getActivityLog(Request $request, $uuid): JsonResponse
    {
        $activity_log = ActivityLog::find($uuid);

        if (empty($activity_log)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $activity_log->toArray();

        $model_old_data = $activity_log->model_old_data;
        if ($this->isJson($model_old_data)) {
            $decodedRequest = json_decode($model_old_data, true);
            $responseData['model_old_data'] = json_encode($decodedRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $decodedRequest = @unserialize($model_old_data);
            if ($decodedRequest !== false || $model_old_data === 'b:0;') {
                $responseData['model_old_data'] = print_r($decodedRequest, true);
            } else {
                $responseData['model_old_data'] = $model_old_data;
            }
        }

        $model_new_data = $activity_log->model_new_data;
        if ($this->isJson($model_new_data)) {
            $decodedRequest = json_decode($model_new_data, true);
            $responseData['model_new_data'] = json_encode($decodedRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $decodedRequest = @unserialize($model_new_data);
            if ($decodedRequest !== false || $model_new_data === 'b:0;') {
                $responseData['model_new_data'] = print_r($decodedRequest, true);
            } else {
                $responseData['model_new_data'] = $model_new_data;
            }
        }

        return response()->json([
            'data' => $responseData,
        ]);
    }

    public function moduleLogs(): View
    {
        $title = __('main.Module Log');

        return view_admin('module_logs.module_logs', compact('title'));
    }

    public function getModuleLogs(Request $request): JsonResponse
    {
        $query = ModuleLog::query()
            ->select([
                'module_logs.uuid',
                'module_logs.type',
                'module_logs.name',
                'module_logs.action',
                'module_logs.level',
                'module_logs.created_at',
            ]);

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->start_date);
            $endDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->end_date);
            $query->whereBetween('module_logs.created_at', [$startDate, $endDate]);
        }

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('module_logs.created_at', 'like', "%{$search}%")
                                ->orWhere('module_logs.type', 'like', "%{$search}%")
                                ->orWhere('module_logs.name', 'like', "%{$search}%")
                                ->orWhere('module_logs.action', 'like', "%{$search}%")
                                ->orWhere('module_logs.level', 'like', "%{$search}%")
                                ->orWhere('module_logs.request', 'like', "%{$search}%")
                                ->orWhere('module_logs.response', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($module_log) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('module-log-view')) {
                        $urls['get'] = route('admin.api.module_log.get', $module_log->uuid);
                    }

                    return $urls;
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->order[0]['column'];
                        $dir = $request->order[0]['dir'];
                        if ($order == 0) {
                            $query->orderBy('module_logs.created_at', $dir);
                        }
                    }
                })
                ->make(true),
        ], 200);
    }

    public function getModuleLog(Request $request, $uuid): JsonResponse
    {
        $module_log = ModuleLog::find($uuid);

        if (empty($module_log)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $module_log->toArray();

        $requestContent = $module_log->request;
        if ($this->isJson($requestContent)) {
            $decodedRequest = json_decode($requestContent, true);
            $responseData['request'] = json_encode($decodedRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $decodedRequest = @unserialize($requestContent, ['allowed_classes' => false]);
            $responseData['request'] = $decodedRequest !== false || $requestContent === 'b:0;'
                ? print_r($decodedRequest, true)
                : $requestContent;
        }

        $responseContent = $module_log->response;
        if ($this->isJson($responseContent)) {
            $decodedResponse = json_decode($responseContent, true);
            $responseData['response'] = json_encode($decodedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $decodedResponse = @unserialize($responseContent, ['allowed_classes' => false]);
            $responseData['response'] = $decodedResponse !== false || $responseContent === 'b:0;'
                ? print_r($decodedResponse, true)
                : $responseContent;
        }

        return response()->json([
            'data' => $responseData,
        ]);
    }

    /**
     * Check if a string is a valid JSON format.
     */
    private function isJson(?string $string): bool
    {
        if (empty($string)) {
            return false;
        }
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }

    public function clientSessions(): View
    {
        $title = __('main.Client Sessions');

        return view_admin('client_session_logs.client_session_logs', compact('title'));
    }

    public function getClientSessions(Request $request): JsonResponse
    {
        $query = ClientSessionLog::with('client', 'user');

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->start_date);
            $endDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->end_date);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return response()->json([
            'data' => DataTables::eloquent($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('created_at', 'like', "%{$search}%")
                                ->orWhere('client_uuid', 'like', "%{$search}%")
                                ->orWhere('user_uuid', 'like', "%{$search}%")
                                ->orWhere('ip_address', 'like', "%{$search}%")
                                ->orWhereHas('client', function ($q2) use ($search) {
                                    $q2->where('firstname', 'like', "%{$search}%")
                                        ->orWhere('lastname', 'like', "%{$search}%")
                                        ->orWhere('company_name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('user', function ($q3) use ($search) {
                                    $q3->where('firstname', 'like', "%{$search}%")
                                        ->orWhere('lastname', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->order[0]['column'];
                        $dir = $request->order[0]['dir'];
                        if ($order == 0) {
                            $query->orderBy('created_at', $dir);
                        }
                    }
                })
                ->make(true),
        ], 200);
    }
}
