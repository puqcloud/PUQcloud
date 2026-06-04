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
use App\Models\ProductGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: 'client.api.cloud.group.list.get',
        description: 'Dynamic proxy endpoint that forwards requests to the product group list module controller. The actual response depends on the module implementation.',
        summary: 'Product group list proxy',
        security: [['apiKeyAuth' => []]],
        tags: ['Cloud'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the product group',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'method',
                description: 'Method name to call on the module',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response from the module',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(
                response: 404,
                description: 'Product group not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Controller or method not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function cloudProductGroupListApi(Request $request, $uuid, $method)
    {
        $product_group = ProductGroup::where('uuid', $uuid)->first();

        if (!$product_group) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $controllerPath = resolveServiceViewControllerPath('list', $product_group->list_template, 'ListController.php');

        if (!$controllerPath) {
            return response()->json([
                'errors' => [__('error.Controller file not found')],
            ], 500);
        }

        require_once $controllerPath;

        $className = 'ListController';

        if (!class_exists($className)) {
            return response()->json([
                'errors' => [__('error.Controller class not found')],
            ], 500);
        }

        $controller = new $className;
        $methodName = 'controller_'.$method;

        if (!method_exists($controller, $methodName)) {
            return response()->json([
                'errors' => [__('error.Method not found')],
            ], 500);
        }

        return $controller->$methodName($request, $product_group);
    }

    #[OA\Get(
        path: 'client.api.cloud.group.order.get',
        description: 'Dynamic proxy endpoint that forwards requests to the product group order module controller. The actual response depends on the module implementation.',
        summary: 'Product group order proxy',
        security: [['apiKeyAuth' => []]],
        tags: ['Cloud'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the product group',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'method',
                description: 'Method name to call on the module',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response from the module',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(
                response: 404,
                description: 'Product group not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Controller or method not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function cloudProductGroupOrderApi(Request $request, $uuid, $method)
    {
        $product_group = ProductGroup::where('uuid', $uuid)->first();

        if (!$product_group) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $controllerPath = resolveServiceViewControllerPath('order', $product_group->order_template, 'OrderController.php');

        if (!$controllerPath) {
            return response()->json([
                'errors' => [__('error.Controller file not found')],
            ], 500);
        }

        require_once $controllerPath;

        $className = 'OrderController';

        if (!class_exists($className)) {
            return response()->json([
                'errors' => [__('error.Controller class not found')],
            ], 500);
        }

        $controller = new $className;
        $methodName = 'controller_'.$method;

        if (!method_exists($controller, $methodName)) {
            return response()->json([
                'errors' => [__('error.Method not found')],
            ], 500);
        }

        return $controller->$methodName($request, $product_group);
    }

    public function cloudService(
        Request $request,
        $uuid,
        $tab = null
    ): RedirectResponse|Factory|\Illuminate\Contracts\View\View|Application {
        $user = app('user');
        $client = app('client');
        $service = $client->services()->where('uuid', $uuid)->whereIn('status',
            ['pending', 'active', 'suspended'])->findOrFail($uuid);
        $menu = $service->getClientAreaMenuConfig();
        if (!array_key_exists($tab, $menu)) {
            return redirect()->route('client.web.panel.cloud.service', ['uuid' => $uuid, 'tab' => 'general']);
        }
        $service->price_detailed = $service->getPriceDetailed();
        $service->product_options = $service->productOptions;
        $product = $service->product;
        $product_group = $product->productGroups()->first();
        $title = $service->client_label;

        return view_client('cloud.manage', compact('title', 'service', 'product', 'product_group', 'tab', 'menu'));
    }

    #[OA\Get(
        path: 'client.api.cloud.service.manage.get',
        description: 'Dynamic proxy endpoint that forwards requests to the service manage module controller. The actual response depends on the module implementation.',
        summary: 'Service manage proxy',
        security: [['apiKeyAuth' => []]],
        tags: ['Cloud'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the service',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'method',
                description: 'Method name to call on the module',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response from the module',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(
                response: 404,
                description: 'Client or service not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Controller or method not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function cloudServiceManageApi(Request $request, $uuid, $method)
    {
        $client = app('client');
        if (!$client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $service = $client->services()->where('uuid', $uuid)->first();

        if (!$service) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $product_group = $service->ProductGroups()->first();

        if (!$product_group) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $controllerPath = resolveServiceViewControllerPath('manage', $product_group->manage_template, 'ManageController.php');

        if (!$controllerPath) {
            return response()->json([
                'errors' => [__('error.Controller file not found')],
            ], 500);
        }

        require_once $controllerPath;

        $className = 'ManageController';

        if (!class_exists($className)) {
            return response()->json([
                'errors' => [__('error.Controller class not found')],
            ], 500);
        }

        $controller = new $className;
        $methodName = 'controller_'.$method;

        if (!method_exists($controller, $methodName)) {
            return response()->json([
                'errors' => [__('error.Method not found')],
            ], 500);
        }

        return $controller->$methodName($request, $service);
    }

    #[OA\Get(
        path: 'client.api.cloud.service.module.get',
        description: 'Dynamic proxy endpoint that forwards requests to the service module controller. The actual response depends on the module implementation.',
        summary: 'Service module proxy',
        security: [['apiKeyAuth' => []]],
        tags: ['Cloud'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the service',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'method',
                description: 'Method name to call on the module',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response from the module',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(
                response: 404,
                description: 'Client or service not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Module method error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function cloudServiceModuleApi(Request $request, $uuid, $method)
    {
        $client = app('client');
        if (!$client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $service = $client->services()->where('uuid', $uuid)->where('status', 'active')->first();
        if (!$service) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $result = $service->apiClientModuleController($request, $method);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        if (is_array($result)) {
            if (($result['status'] ?? null) === 'success') {
                return response()->json($result['data']);
            }

            return response()->json($result, $result['code'] ?? 500);
        }

        return response()->json($result);
    }

    public function cloudServiceUpgrade(Request $request, $uuid): RedirectResponse|Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
    {
        $client = app('client');
        $service = $client->services()->where('uuid', $uuid)
            ->where('status', 'active')
            ->where('termination_request', false)
            ->findOrFail($uuid);

        $product = $service->product;
        if (! $product->allow_upgrade && ! $product->allow_downgrade) {
            return redirect()->route('client.web.panel.cloud.service', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $product_group = $product->productGroups()->first();
        $title = $service->client_label.' - '.__('main.Upgrade / Downgrade');

        return view_client('cloud.upgrade', compact('title', 'service', 'product', 'product_group'));
    }

    public function cloudServiceUpgradeApi(Request $request, $uuid, $method)
    {
        $client = app('client');
        if (! $client) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $service = $client->services()->where('uuid', $uuid)
            ->where('status', 'active')
            ->where('termination_request', false)
            ->first();
        if (! $service) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $product = $service->product;
        if (! $product || (! $product->allow_upgrade && ! $product->allow_downgrade)) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $product_group = $service->productGroups()->first();
        if (! $product_group) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $controllerPath = resolveServiceViewControllerPath('upgrade', $product_group->upgrade_template ?? 'default', 'UpgradeController.php');

        if (! $controllerPath) {
            return response()->json(['errors' => [__('error.Controller file not found')]], 500);
        }

        require_once $controllerPath;

        $className = 'UpgradeController';

        if (! class_exists($className)) {
            return response()->json(['errors' => [__('error.Controller class not found')]], 500);
        }

        $controller = new $className;
        $methodName = 'controller_'.$method;

        if (! method_exists($controller, $methodName)) {
            return response()->json(['errors' => [__('error.Method not found')]], 500);
        }

        return $controller->$methodName($request, $service);
    }

    public function cloudServiceProration(Request $request, $uuid): RedirectResponse|Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
    {
        $client = app('client');
        $service = $client->services()->where('uuid', $uuid)
            ->where('status', 'active')
            ->where('termination_request', false)
            ->findOrFail($uuid);

        $product = $service->product;
        // Proration alignment makes no sense for prepaid services (the client is billed
        // from their balance at top-up time, not on a recurring invoice), so we block
        // access at the route level. This matches the button visibility in header.blade.php
        // and every ProrationController::validateMutable(..., requireInvoiceMode: true) check.
        if (! $product->allow_proration_alignment || $service->billing_mode !== 'invoice') {
            return redirect()->route('client.web.panel.cloud.service', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $product_group = $product->productGroups()->first();
        $title = $service->client_label.' - '.__('main.Billing Alignment');

        return view_client('cloud.proration', compact('title', 'service', 'product', 'product_group'));
    }

    public function cloudServiceProrationApi(Request $request, $uuid, $method)
    {
        $client = app('client');
        if (! $client) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $service = $client->services()->where('uuid', $uuid)
            ->where('status', 'active')
            ->where('termination_request', false)
            ->first();
        if (! $service) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $product = $service->product;
        // API-level guard: reject every proration call for prepaid services. Without this a
        // crafted request could hit controller_Execute directly and skip the view-level check.
        if (! $product || ! $product->allow_proration_alignment || $service->billing_mode !== 'invoice') {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $product_group = $service->productGroups()->first();
        if (! $product_group) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $controllerPath = resolveServiceViewControllerPath('proration', $product_group->proration_template ?? 'default', 'ProrationController.php');

        if (! $controllerPath) {
            return response()->json(['errors' => [__('error.Controller file not found')]], 500);
        }

        require_once $controllerPath;

        $className = 'ProrationController';

        if (! class_exists($className)) {
            return response()->json(['errors' => [__('error.Controller class not found')]], 500);
        }

        $controller = new $className;
        $methodName = 'controller_'.$method;

        if (! method_exists($controller, $methodName)) {
            return response()->json(['errors' => [__('error.Method not found')]], 500);
        }

        return $controller->$methodName($request, $service);
    }
}
