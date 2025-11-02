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
use Yajra\DataTables\DataTables;

class AdminServicesController extends Controller
{
    public function services(): View
    {
        $title = __('main.Services');

        return view_admin('services.services', compact('title'));
    }

    public function serviceCreate(): View
    {
        $title = __('main.Create New Service');

        return view_admin('services.service_create', compact('title'));
    }

    public function postService(Request $request): JsonResponse
    {
        $expected_fields = [
            "product_uuid", "product_price_uuid",
        ];

        $product = Product::query()->where('uuid', $request->get('product_uuid'))->first();
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The selected product was not found')],
            ], 404);
        }

        $option_uuids = [];
        foreach ($request->all() as $key => $value) {
            if (!in_array($key, $expected_fields)) {
                if ($product->hasProductOption($key, $value)) {
                    $option_uuids[$key] = $value;
                }
            }
        }

        $data = [
            'client_uuid' => $request->get('client_uuid'),
            'product_uuid' => $request->get('product_uuid'),
            'product_price_uuid' => $request->get('product_price_uuid'),
            'option_uuids' => $option_uuids,
        ];

        $result = Service::createFromArray($data);

        if ($result['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'],
            ],
                $result['code']
            );
        }

        $service = $result['data'];
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

    public function getServices(Request $request): JsonResponse
    {
        $query = Service::query()
            ->join('clients', 'clients.uuid', '=', 'services.client_uuid')
            ->with('product', 'client')
            ->select('services.*');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('services.status', 'like', "%{$search}%")
                                ->orWhere('services.admin_label', 'like', "%{$search}%")
                                ->orWhere('services.uuid', 'like', "%{$search}%")
                                ->orWhere('clients.firstname', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('client', function ($service) {
                    $client = $service->client;
                    $client->owner = $client->owner();
                    $client->web_url = route('admin.web.client.tab', ['uuid' => $client->uuid, 'tab' => 'general']);

                    return $client->toArray();
                })
                ->addColumn('product', function ($service) {
                    $product = $service->product;
                    return [
                        'key' => $product->key,
                        'name' => $product->name,
                        'web_url' => route('admin.web.product.tab', ['uuid' => $product->uuid, 'tab' => 'general']),
                    ];
                })
                ->addColumn('product_group', function ($service) {
                    $product = $service->product;
                    $product_group = $product->productGroups()->first();
                    return [
                        'key' => $product_group->key,
                        'name' => $product_group->name,
                        'web_url' => route('admin.web.product_group.tab', ['uuid' => $product_group->uuid, 'tab' => 'general']),
                    ];
                })

                ->addColumn('termination_time', function ($service) {
                    return $service->getTerminationTime();
                })
                ->addColumn('cancellation_time', function ($service) {
                    return $service->getCancellationTime();
                })
                ->addColumn('price', function ($service) {
                    $price = $service->price;
                    $currency = $price->currency;
                    $price_total = $service->getPriceTotal();

                    return [
                        'code' => $currency->code ?? '',
                        'amount' => number_format((float) $price_total['base'], 2),
                        'period' => $price->period,
                    ];
                })
                ->addColumn('urls', function ($service) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('clients-view')) {
                        $urls['edit'] = route('admin.web.client.tab',
                            ['uuid' => $service->client->uuid, 'tab' => 'services', 'edit' => $service->uuid]);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }


    public function putService(Request $request, $uuid): JsonResponse
    {
        $service = Service::find($uuid);

        if (!$service) {
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

        if (!$service) {
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

        if (!$service) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $action = $request->input('action');
        $result = $service->runAction($action);

        if ($result['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'] ?? [],
            ], 500);
        }

        if ($result['status'] == 'error') {
            return response()->json([
                'status' => 'error',
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
        if (!$service) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $type = $request->input('type');
        $value = $request->input('value');

        if ($type === 'status') {
            $old = $service->status;
            $service->status = $value;
            $service->save();

            logActivity('warning', 'Service:'.$service->uuid.' '."Forced status change from {$old} to {$value}",
                'Force Status', null, null, $service->user_uuid);

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

            logActivity('warning', 'Service:'.$service->uuid.' '."Forced idle change from {$old} to {$new}",
                'Force Idle', null, null, $service->user_uuid);

            return response()->json(['status' => 'success', 'message' => __('message.Idle forcibly changed')]);
        }

        return response()->json(['errors' => ['Unknown type']], 422);
    }
}
