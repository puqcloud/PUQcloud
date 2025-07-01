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
use App\Models\Price;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminServicesController extends Controller
{
    public function serviceCreate(): View
    {
        $title = __('main.Create New Service');

        return view_admin('services.service_create', compact('title'));
    }

    public function postService(Request $request): JsonResponse
    {
        $data = [
            'client' => $request->get('client'),
            'product' => $request->get('product'),
            'product_price' => $request->get('product_price'),
            'option' => $request->get('option', []),
        ];

        $result = Service::createFromArray($data);

        if (! empty($result['error'])) {
            return response()->json(['errors' => [$result['error']]], $result['code']);
        }

        $service = $result['service'];
        $client = $service->client;

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $service,
            'redirect' => route('admin.web.client.tab', [
                'uuid' => $client->uuid,
                'tab' => 'services',
                'edit' => $service->uuid,
            ]),
        ]);
    }

    public function getService(Request $request, $uuid): JsonResponse
    {
        $service = Service::with('product')->with('productOptions')->find($uuid);
        if (empty($service)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $product = Product::with('productOptionGroups.productOptions.prices')->find($service->product_uuid);
        $price = Price::find($service->price_uuid);

        $service->product_option_groups = $product->getOptionGroupsWithPrices($price);
        $service->price_detailed = $service->getPriceDetailed();

        return response()->json([
            'data' => $service,
        ]);
    }

    public function putService(Request $request, $uuid): JsonResponse
    {
        $service = Service::find($uuid);

        if (! $service) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validated = $request->validate([
            'order_date' => 'nullable|date',
            'activated_date' => 'nullable|date',
            'terminated_date' => 'nullable|date',
            'suspended_date' => 'nullable|date',
            'billing_timestamp' => 'nullable|date',
            'admin_label' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string',
        ]);

        $orderDate = $request->filled('order_date') ? Carbon::parse($request->input('order_date')) : null;
        $activatedDate = $request->filled('activated_date') ? Carbon::parse($request->input('activated_date')) : null;
        $terminatedDate = $request->filled('terminated_date') ? Carbon::parse($request->input('terminated_date')) : null;
        $billingTimestamp = $request->filled('billing_timestamp') ? Carbon::parse($request->input('billing_timestamp')) : null;

        if ($orderDate && $activatedDate && $orderDate->gt($activatedDate)) {
            return response()->json([
                'errors' => [__('error.Order date must be before or equal to Activation date')],
            ], 422);
        }

        if ($activatedDate && $terminatedDate && $activatedDate->gt($terminatedDate)) {
            return response()->json([
                'errors' => [__('error.Activation date must be before or equal to Terminate date')],
            ], 422);
        }

        if ($activatedDate && $billingTimestamp && $billingTimestamp->lt($activatedDate)) {
            return response()->json([
                'errors' => [__('error.Billing timestamp must not be before Activation date')],
            ], 422);
        }

        $service->fill($validated);
        $service->save();
        $service->refresh();

        if ($request->has('option')) {
            $service->updateProductOptions($request->get('option'));
        }

        $save_module_data = $service->saveModuleData($request->all());
        if ($save_module_data['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $save_module_data['message'] ?? '',
                'errors' => $save_module_data['errors'] ?? [],
            ], $save_module_data['code']);
        }
        $service->save();
        $service->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $service,
        ]);
    }

    public function getServiceModule(Request $request, $uuid): JsonResponse
    {
        $service = Service::find($uuid);

        if (! $service) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => null,
            'data' => $service->getAdminAreaPage(),
        ]);
    }

    public function postServiceAction(Request $request, $uuid): JsonResponse
    {
        $service = Service::find($uuid);

        if (! $service) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $action = $request->input('action');
        $result = $service->runAction($action);

        if ($result['status'] === 'error') {
            return response()->json([
                'errors' => $result['errors'] ?? [],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'] ?? 'Success',
            'data' => $result['data'] ?? null,
        ]);
    }

    public function putServiceStatus(Request $request, $uuid): JsonResponse
    {
        $service = Service::find($uuid);
        if (! $service) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $type = $request->input('type');
        $value = $request->input('value');

        if ($type === 'status') {
            $old = $service->status;
            $service->status = $value;
            $service->save();

            logActivity('warning', 'Service:'.$service->uuid.' '."Forced status change from {$old} to {$value}", 'Force Status', null, null, $service->user_uuid);

            return response()->json(['status' => 'success', 'message' => __('message.Status forcibly changed')]);
        }

        if ($type === 'idle') {
            $old = 'false';
            $new = 'false';
            if ($service->idle) {
                $old = 'true';
            }
            $service->idle = false;
            if ($value == '1') {
                $new = 'true';
                $service->idle = true;
            }
            $service->save();

            logActivity('warning', 'Service:'.$service->uuid.' '."Forced idle change from {$old} to {$new}", 'Force Idle', null, null, $service->user_uuid);

            return response()->json(['status' => 'success', 'message' => __('message.Idle forcibly changed')]);
        }

        return response()->json(['errors' => ['Unknown type']], 422);
    }
}
