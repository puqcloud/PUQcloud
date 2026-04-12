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

/*
 * WEB Routes:
 *   GET  services                                              -> services()                [admin.web.services]
 *   GET  service/create                                        -> serviceCreate()           [admin.web.service.create]
 *
 * API Routes:
 *   GET    services                                            -> getServices()             [admin.api.services.get]
 *   GET    service/{uuid}                                      -> getService()              [admin.api.service.get]
 *   POST   service                                             -> postService()             [admin.api.service.post]
 *   PUT    service/{uuid}                                      -> putService()              [admin.api.service.put]
 *   POST   service/{uuid}/action                               -> postServiceAction()       [admin.api.service.action.post]
 *   PUT    service/{uuid}/status                               -> putServiceStatus()        [admin.api.service.status.put]
 *   PUT    service/{uuid}/billing_mode                         -> putServiceBillingMode()   [admin.api.service.billing_mode.put]
 *   GET    service/{uuid}/module                               -> getServiceModule()        [admin.api.service.module.get]
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
use OpenApi\Attributes as OA;
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

    #[OA\Post(
        path: 'admin.api.service.post',
        description: 'Creates a new service for a client with specified product, price, and options.',
        summary: 'Create a new service',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['client_uuid', 'product_uuid', 'product_price_uuid'],
                    properties: [
                        new OA\Property(property: 'client_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'product_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'product_price_uuid', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Services'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'redirect', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
            'billing_mode' => $request->get('billing_mode'),
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

    #[OA\Get(
        path: 'admin.api.service.get',
        description: 'Returns detailed information about a service, including product, options, and pricing.',
        summary: 'Get service details by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Services'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the service',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.services.get',
        description: 'Returns a DataTable list of services.',
        summary: 'Get services list',
        security: [['apiKeyAuth' => []]],
        tags: ['Services'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Services list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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


    #[OA\Put(
        path: 'admin.api.service.put',
        description: 'Updates a service including dates, labels, notes, options, and module data.',
        summary: 'Update service by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'order_date', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'activated_date', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'terminated_date', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'suspended_date', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'billing_timestamp', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'admin_label', type: 'string', nullable: true),
                        new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Services'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the service to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

        app(\App\Services\HookService::class)->callHooks('ServiceEdit', ['service' => $service]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $service,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.service.module.get',
        description: 'Returns the admin area page HTML for the service module.',
        summary: 'Get service module admin page',
        security: [['apiKeyAuth' => []]],
        tags: ['Services'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the service',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service module page returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Post(
        path: 'admin.api.service.action.post',
        description: 'Executes an action on a service (e.g., create, suspend, unsuspend, terminate).',
        summary: 'Run service action',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['action'],
                    properties: [
                        new OA\Property(property: 'action', description: 'Action to execute', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Services'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the service',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Action executed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', nullable: true),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.service.status.put',
        description: 'Forcibly changes the service status or idle state.',
        summary: 'Update service status',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['type', 'value'],
                    properties: [
                        new OA\Property(property: 'type', description: 'Type of change (status or idle)', type: 'string', enum: ['status', 'idle']),
                        new OA\Property(property: 'value', description: 'New value', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Services'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the service',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status changed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    public function putServiceBillingMode(Request $request, $uuid): JsonResponse
    {
        $service = Service::find($uuid);
        if (!$service) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $newMode = $request->input('billing_mode');
        if (! in_array($newMode, ['prepaid', 'invoice'])) {
            return response()->json(['errors' => ['Invalid billing mode']], 422);
        }

        $result = $service->switchBillingMode($newMode);

        if ($result['status'] === 'error') {
            return response()->json(['errors' => $result['errors']], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
        ]);
    }
}
