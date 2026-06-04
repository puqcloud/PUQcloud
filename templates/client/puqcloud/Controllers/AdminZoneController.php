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

namespace Template\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LayoutOptions;
use LoginLayoutOptions;
use OpenApi\Attributes as OA;

class AdminZoneController extends Controller
{
    public function info(Request $request): View
    {
        $title = __('client_template.Info');
        $info = include config('template.admin.base_path').'/config.php';

        return view_client('admin_zone.info', compact('title', 'info'));
    }

    public function layoutOptions(Request $request): View
    {
        $title = __('client_template.Layout Options');

        return view_client('admin_zone.layout_options', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.client_template.layout_options.get',
        description: 'Returns the client template layout options.',
        summary: 'Get layout options',
        security: [['apiKeyAuth' => []]],
        tags: ['Client Template'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Layout options returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getLayoutOptions(Request $request): JsonResponse
    {
        $layout_options = new LayoutOptions;

        return response()->json([
            'data' => $layout_options,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.client_template.layout_options.put',
        description: 'Updates the client template layout options.',
        summary: 'Update layout options',
        security: [['apiKeyAuth' => []]],
        tags: ['Client Template'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Layout options updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putLayoutOptions(Request $request): JsonResponse
    {

        if ($request->has('client_area_fixed_header')) {
            if ($request->input('client_area_fixed_header') == 'yes') {
                SettingService::set('clientAreaLayoutOptionFixed_header', 'fixed-header');
            } else {
                SettingService::set('clientAreaLayoutOptionFixed_header', '');
            }
        }

        if ($request->has('client_area_fixed_sidebar')) {
            if ($request->input('client_area_fixed_sidebar') == 'yes') {
                SettingService::set('clientAreaLayoutOptionFixed_sidebar', 'fixed-sidebar');
            } else {
                SettingService::set('clientAreaLayoutOptionFixed_sidebar', '');
            }
        }

        if ($request->has('client_area_header_color_scheme')) {
            SettingService::set('clientAreaLayoutOptionHeaderColorScheme', $request->input('client_area_header_color_scheme'));
        }

        if ($request->has('client_area_sidebar_color_scheme')) {
            SettingService::set('clientAreaLayoutOptionSidebarColorScheme', $request->input('client_area_sidebar_color_scheme'));
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);

    }

    public function loginLayoutOptions(Request $request): View
    {
        $title = __('client_template.Login Page');

        return view_client('admin_zone.login_page', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.client_template.login_layout_options.get',
        description: 'Returns the client template login page layout options.',
        summary: 'Get login layout options',
        security: [['apiKeyAuth' => []]],
        tags: ['Client Template'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login layout options returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getLoginLayoutOptions(Request $request): JsonResponse
    {
        $login_layout_options = new LoginLayoutOptions;

        return response()->json([
            'data' => $login_layout_options,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.client_template.login_layout_options.put',
        description: 'Updates the client template login page layout options.',
        summary: 'Update login layout options',
        security: [['apiKeyAuth' => []]],
        tags: ['Client Template'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login layout options updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putLoginLayoutOptions(Request $request): JsonResponse
    {
        if ($request->has('client_area_login_page_header_color_scheme')) {
            SettingService::set('clientAreaLoginPageHeaderColorScheme', $request->input('client_area_login_page_header_color_scheme'));
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);

    }
}
