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
use App\Models\Module;
use App\Models\NotificationSender;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class AdminAddOnsController extends Controller
{
    public function marketplace(): View
    {
        $title = __('main.Marketplace');

        return view_admin('add_ons.marketplace', compact('title'));
    }

    public function modules(): View
    {
        $title = __('main.Modules');

        return view_admin('add_ons.modules', compact('title'));
    }

    public function getModules(Request $request): JsonResponse
    {
        $query = Module::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($module) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('add-ons-modules-management')) {

                        if ($module->status == 'inactive') {
                            $urls['activate'] = route('admin.api.add_ons.module.activate.post', $module->uuid);
                        }

                        if ($module->status == 'active' or $module->status == 'restricted') {
                            $urls['deactivate'] = route('admin.api.add_ons.module.deactivate.post', $module->uuid);
                        }

                        // if ($module->status == 'error') {
                        $urls['delete'] = route('admin.api.add_ons.module.delete.delete', $module->uuid);
                        // }

                        if ($module->status == 'restricted') {
                            $urls['update'] = route('admin.api.add_ons.module.update.post', $module->uuid);
                        }
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);

    }

    public function postModuleActivate(Request $request, $uuid): JsonResponse
    {

        $module = Module::find($uuid);

        if (empty($module)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($module->status !== 'inactive') {
            return response()->json([
                'errors' => [__('error.Module activated')],
            ], 409);
        }

        $activate = $module->moduleActivate();
        if ($activate !== 'success') {
            return response()->json([
                'errors' => [$activate],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Activated successfully'),
        ], 200);

    }

    public function postModuleDeactivate(Request $request, $uuid): JsonResponse
    {

        $module = Module::find($uuid);

        if (empty($module)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($module->status == 'inactive') {
            return response()->json([
                'errors' => [__('error.Module inactive')],
            ], 409);
        }

        $deactivate = $module->moduleDeactivate();
        if ($deactivate !== 'success') {
            return response()->json([
                'errors' => [$deactivate],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deactivated successfully'),
        ], 200);

    }

    public function postModuleUpdate(Request $request, $uuid): JsonResponse
    {

        $module = Module::find($uuid);

        if (empty($module)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($module->status !== 'restricted') {
            return response()->json([
                'errors' => [__('error.The module does not require updating')],
            ], 409);
        }

        $update = $module->moduleUpdate();
        if ($update !== 'success') {
            return response()->json([
                'errors' => [$update],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ], 200);

    }

    public function postModuleDelete(Request $request, $uuid): JsonResponse
    {
        $module = Module::find($uuid);

        if (empty($module)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $module->delete();
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

    public function reports(): View
    {
        $title = __('main.Reports');

        return view_admin('add_ons.reports', compact('title'));
    }

    public function handle(Request $request, $type, $name, $controller): JsonResponse
    {
        $admin = app('admin');

        if ($type == 'Notification') {
            if ($admin->hasPermission('notification-senders-management')) {
                return $this->handleNotification($request, $type, $name, $controller);
            } else {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('error.Permission Denied')],
                ], 403);
            }
        }

        return response()->json([
            'status' => 'error',
            'errors' => [__('error.Not found')],
        ], 404);
    }

    public function handleNotification($request, $type, $name, $controller): JsonResponse
    {
        $notificationSender = NotificationSender::find($request->input('uuid'));

        if (empty($notificationSender)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Model not found')],
            ], 404);
        }

        $notificationSender->initializeModuleClass();
        if (empty($notificationSender->module_class)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Module not found')],
            ], 404);
        }

        $controllerMethod = 'controller_'.$controller;
        if (method_exists($notificationSender->module_class, $controllerMethod)) {
            $result = $notificationSender->module_class->{$controllerMethod}($request->all());

            if ($result['status'] == 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'] ?? '',
                ], $result['code'] ?? 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'] ?? '',
                    'errors' => $result['errors'] ?? [],
                ], $result['code'] ?? 500);
            }
        }

        return response()->json([
            'errors' => [__('error.Controller not found')],
        ], 404);
    }
}
