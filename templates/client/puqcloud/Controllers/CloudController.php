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

namespace Template\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CloudController extends Controller
{
    public function cloudProductGroup(Request $request, $uuid): View|Factory|Application
    {
        $product_group = ProductGroup::findOrFail($uuid);
        $product_group->setLocale(session('locale'));
        $title = $product_group->name;

        return view_client('cloud.list', compact('title', 'product_group'));
    }

    public function cloudProductGroupOrder(Request $request, $uuid): View|Factory|Application
    {
        $product_group = ProductGroup::findOrFail($uuid);
        $product_group->setLocale(session('locale'));
        $title = $product_group->name.' - '.__('main.Deploy Service');

        return view_client('cloud.order', compact('title', 'product_group'));
    }

    public function cloudProductGroupListApi(Request $request, $uuid, $method)
    {
        $product_group = ProductGroup::where('uuid', $uuid)->first();

        if (! $product_group) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $template = env('TEMPLATE_CLIENT', 'puqcloud');
        $controllerPath = base_path("templates/client/{$template}/views/service_views/list/{$product_group->list_template}/ListController.php");

        if (! file_exists($controllerPath)) {
            return response()->json([
                'errors' => [__('error.Controller file not found')],
            ], 500);
        }

        require_once $controllerPath;

        $className = 'ListController';

        if (! class_exists($className)) {
            return response()->json([
                'errors' => [__('error.Controller class not found')],
            ], 500);
        }

        $controller = new $className;
        $methodName = 'controller_'.$method;

        if (! method_exists($controller, $methodName)) {
            return response()->json([
                'errors' => [__('error.Method not found')],
            ], 500);
        }

        return $controller->$methodName($request, $product_group);
    }

    public function cloudProductGroupOrderApi(Request $request, $uuid, $method)
    {
        $product_group = ProductGroup::where('uuid', $uuid)->first();

        if (! $product_group) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $template = env('TEMPLATE_CLIENT', 'puqcloud');
        $controllerPath = base_path("templates/client/{$template}/views/service_views/order/{$product_group->order_template}/OrderController.php");

        if (! file_exists($controllerPath)) {
            return response()->json([
                'errors' => [__('error.Controller file not found')],
            ], 500);
        }

        require_once $controllerPath;

        $className = 'OrderController';

        if (! class_exists($className)) {
            return response()->json([
                'errors' => [__('error.Controller class not found')],
            ], 500);
        }

        $controller = new $className;
        $methodName = 'controller_'.$method;

        if (! method_exists($controller, $methodName)) {
            return response()->json([
                'errors' => [__('error.Method not found')],
            ], 500);
        }

        return $controller->$methodName($request, $product_group);
    }

    public function cloudService(Request $request, $uuid, $tab = null): RedirectResponse|Factory|\Illuminate\Contracts\View\View|Application
    {
        $user = app('user');
        $client = app('client');
        $service = $client->services()->where('uuid', $uuid)->whereIn('status', ['pending', 'active', 'suspended'])->findOrFail($uuid);
        $menu = $service->getClientAreaMenuConfig();
        if (! array_key_exists($tab, $menu)) {
            return redirect()->route('client.web.panel.cloud.service', ['uuid' => $uuid, 'tab' => 'general']);
        }
        $service->price_detailed = $service->getPriceDetailed();
        $service->product_options = $service->productOptions;
        $product = $service->product;
        $product_group = $product->productGroups()->first();
        $title = $service->client_label;

        return view_client('cloud.manage', compact('title', 'service', 'product', 'product_group', 'tab', 'menu'));
    }

    public function cloudServiceManageApi(Request $request, $uuid, $method)
    {
        $client = app('client');
        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $service = $client->services()->where('uuid', $uuid)->first();

        if (! $service) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $product_group = $service->ProductGroups()->first();

        if (! $product_group) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $template = env('TEMPLATE_CLIENT', 'puqcloud');
        $controllerPath = base_path("templates/client/{$template}/views/service_views/manage/{$product_group->manage_template}/ManageController.php");

        if (! file_exists($controllerPath)) {
            return response()->json([
                'errors' => [__('error.Controller file not found')],
            ], 500);
        }

        require_once $controllerPath;

        $className = 'ManageController';

        if (! class_exists($className)) {
            return response()->json([
                'errors' => [__('error.Controller class not found')],
            ], 500);
        }

        $controller = new $className;
        $methodName = 'controller_'.$method;

        if (! method_exists($controller, $methodName)) {
            return response()->json([
                'errors' => [__('error.Method not found')],
            ], 500);
        }

        return $controller->$methodName($request, $service);
    }

    public function cloudServiceModuleApi(Request $request, $uuid, $method)
    {
        $client = app('client');
        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $service = $client->services()->where('uuid', $uuid)->where('status', 'active')->first();

        if (! $service) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $result = $service->apiClientModuleController($request, $method);

        if ($result['status'] === 'success') {
            return $result['data'];
        }

        return response()->json($result, 500);
    }
}
