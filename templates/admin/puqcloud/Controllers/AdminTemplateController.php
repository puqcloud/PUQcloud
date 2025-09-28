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

namespace Template\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTemplateController extends Controller
{
    public function info(Request $request): View
    {
        $title = __('admin_template.Info');
        $info = include config('template.admin.base_path').'/config.php';

        return view_admin('admin_zone.info', compact('title', 'info'));
    }

    public function layoutOptions(Request $request): View
    {
        $title = __('admin_template.Layout Options');

        return view_admin('admin_zone.layout_options', compact('title'));
    }

    public function putLayoutOptions(Request $request): JsonResponse
    {
        if ($request->has('fixed_header')) {
            if ($request->input('fixed_header') == 'yes') {
                SettingService::set('layoutOptionFixed_header', 'fixed-header');
            } else {
                SettingService::set('layoutOptionFixed_header', '');
            }
        }

        if ($request->has('fixed_sidebar')) {
            if ($request->input('fixed_sidebar') == 'yes') {
                SettingService::set('layoutOptionFixed_sidebar', 'fixed-sidebar');
            } else {
                SettingService::set('layoutOptionFixed_sidebar', '');
            }
        }

        if ($request->has('header_color_scheme')) {
            SettingService::set('layoutOptionHeaderColorScheme', $request->input('header_color_scheme'));
        }

        if ($request->has('sidebar_color_scheme')) {
            SettingService::set('layoutOptionSidebarColorScheme', $request->input('sidebar_color_scheme'));
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);

    }
}
