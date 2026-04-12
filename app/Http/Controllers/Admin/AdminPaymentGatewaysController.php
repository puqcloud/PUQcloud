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
 * API Routes:
 *   GET  payment_modules/select                             -> getPaymentModulesSelect()             [admin.api.payment_modules.select.get]
 *   GET  home_company/{uuid}/payment_gateways               -> getHomeCompanyPaymentGateways()       [admin.api.home_company.payment_gateways.get]
 *   POST home_company/{uuid}/payment_gateway                -> postHomeCompanyPaymentGateway()       [admin.api.home_company.payment_gateway.post]
 *   GET  payment_gateway/{uuid}                             -> getPaymentGateway()                   [admin.api.payment_gateway.get]
 *   PUT  payment_gateway/{uuid}                             -> putPaymentGateway()                   [admin.api.payment_gateway.put]
 *   DELETE payment_gateway/{uuid}                           -> deletePaymentGateway()                [admin.api.payment_gateway.delete]
 *   POST payment_gateway/update_order                       -> postPaymentGatewayUpdateOrder()       [admin.api.payment_gateway.update_order.post]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\HomeCompany;
use App\Models\Module;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminPaymentGatewaysController extends Controller
{
    #[OA\Get(
        path: 'admin.api.payment_modules.select.get',
        description: 'Returns a select list of payment modules.',
        summary: 'Get payment modules select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment modules select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getPaymentModulesSelect(Request $request): JsonResponse
    {
        $notificationModules = Module::all();
        $modules = [];
        foreach ($notificationModules as $module) {
            if ($module->type != 'Payment') {
                continue;
            }
            $module_name = ! empty($module->module_data['name']) ? $module->module_data['name'] : $module->name;
            $modules[] = [
                'id' => $module->uuid,
                'text' => $module_name.' ('.$module->status.')',
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredModules = array_filter($modules, function ($module) use ($searchTerm) {
            return empty($searchTerm) || stripos($module['text'], $searchTerm) !== false;
        });

        return response()->json(['data' => [
            'results' => array_values($filteredModules),
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    #[OA\Get(
        path: 'admin.api.home_company.payment_gateways.get',
        description: 'Returns a list of payment gateways associated with a home company, including currency and module data.',
        summary: 'Get payment gateways for a home company',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the home company',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateways list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Home company not found'),
        ]
    )]
    public function getHomeCompanyPaymentGateways(Request $request, $uuid): JsonResponse
    {
        $home_company = HomeCompany::find($uuid);

        if (empty($home_company)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $query = $home_company->paymentGateways()->with('currencies');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($payment_gateway) use ($home_company) {
                    $admin = app('admin');
                    $urls = [];
                    if ($admin->hasPermission('finance-view')) {
                        $urls['edit'] = route('admin.web.home_company.tab', ['uuid' => $home_company->uuid, 'tab' => 'payment_gateways', 'edit' => $payment_gateway->uuid]);
                        $urls['get'] = route('admin.api.payment_gateway.get', $payment_gateway->uuid);
                    }

                    if ($admin->hasPermission('finance-view')) {
                        $urls['delete'] = route('admin.api.payment_gateway.delete', $payment_gateway->uuid);
                    }

                    return $urls;
                })
                ->addColumn('module_data', function ($payment_gateway) {
                    return $payment_gateway->getModuleConfig();
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.home_company.payment_gateway.post',
        description: 'Creates a new payment gateway for a home company with a unique key and associated payment module.',
        summary: 'Create payment gateway for a home company',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['key', 'module_uuid'],
                    properties: [
                        new OA\Property(property: 'key', description: 'Unique key for the payment gateway', type: 'string', example: 'paypal_main'),
                        new OA\Property(property: 'module_uuid', description: 'UUID of the payment module', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the home company',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateway created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                        new OA\Property(property: 'redirect', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Home company not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postHomeCompanyPaymentGateway(Request $request, $uuid): JsonResponse
    {
        $home_company = HomeCompany::find($uuid);

        if (empty($home_company)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $modules = Module::query()->where('type', 'Payment')->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:payment_gateways,key',
            'module_uuid' => 'required|in:'.implode(',', $modules),
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
            'module.in' => __('error.The selected Module is invalid'),
            'module.required' => __('error.The Module field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $payment_gateway = new PaymentGateway;
        $payment_gateway->key = $request->get('key');
        $payment_gateway->module_uuid = $request->get('module_uuid');
        $payment_gateway->configuration = json_encode([]);
        $payment_gateway->home_company_uuid = $home_company->uuid;
        $order = PaymentGateway::query()
            ->where('home_company_uuid', $payment_gateway->home_company_uuid)
            ->max('order');
        $payment_gateway->order = ($order ?? 0) + 1;
        $payment_gateway->save();
        $payment_gateway->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
            'redirect' => route('admin.web.home_company.tab', ['uuid' => $home_company->uuid, 'tab' => 'payment_gateways', 'edit' => $payment_gateway->uuid]),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.payment_gateway.get',
        description: 'Returns detailed information about a payment gateway, including currencies, module configuration, and settings HTML.',
        summary: 'Get payment gateway by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the payment gateway',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'locale',
                description: 'Optional locale for translations',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateway data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Payment gateway not found'),
        ]
    )]
    public function getPaymentGateway(Request $request, $uuid): JsonResponse
    {
        $payment_gateway = PaymentGateway::find($uuid);

        if (empty($payment_gateway)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (! empty($request->input('locale'))) {
            $payment_gateway->setLocale($request->input('locale'));
        }

        $module_html = $payment_gateway->getSettingsPage();
        $responseData = $payment_gateway->toArray();

        $responseData['currencies'] = $payment_gateway->currencies;

        $responseData['currencies_data'] = [];
        foreach ($payment_gateway->currencies as $currency) {
            $default = '';
            if ($currency->default) {
                $default = '*';
            }
            $responseData['currencies_data'][] = [
                'id' => $currency->uuid,
                'text' => $currency->code.$default,
            ];
        }

        $responseData['module_html'] = $module_html;

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.payment_gateway.put',
        description: 'Updates a payment gateway by UUID, including key, name, description, module data, and currency associations.',
        summary: 'Update payment gateway by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'key', description: 'Unique key for the payment gateway', type: 'string', example: 'paypal_main'),
                        new OA\Property(property: 'name', description: 'Payment gateway name', type: 'string', example: 'PayPal'),
                        new OA\Property(property: 'description', description: 'Payment gateway description', type: 'string'),
                        new OA\Property(property: 'locale', description: 'Locale for translations', type: 'string'),
                        new OA\Property(property: 'currencies', description: 'Array of currency UUIDs', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the payment gateway',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateway updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Payment gateway not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putPaymentGateway(Request $request, $uuid): JsonResponse
    {
        $payment_gateway = PaymentGateway::find($uuid);

        if (empty($payment_gateway)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'unique:payment_gateways,key,'.$payment_gateway->uuid.',uuid',
        ], [
            'name.unique' => __('error.The key is already in taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $payment_gateway->key = $request->input('key');

        if (! empty($request->input('locale'))) {
            $payment_gateway->setLocale($request->input('locale'));
        }

        if ($request->has('name')) {
            $payment_gateway->name = $request->input('name');
        }

        if ($request->has('description')) {
            $payment_gateway->description = $request->input('description');
        }

        $save_module_data = $payment_gateway->saveModuleData($request->all());

        if ($save_module_data['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $save_module_data['message'],
            ], $save_module_data['code']);
        }

        $payment_gateway->save();
        $payment_gateway->refresh();

        if ($request->has('currencies')) {
            $currenciesUuids = $request->input('currencies');
            $existingCurrencies = Currency::whereIn('uuid', $currenciesUuids)->pluck('uuid')->toArray();
            $validCurrencies = array_intersect($currenciesUuids, $existingCurrencies);
            $payment_gateway->currencies()->detach();
            foreach ($validCurrencies as $currenciesUuid) {
                $payment_gateway->currencies()->attach($currenciesUuid);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $payment_gateway,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.payment_gateway.delete',
        description: 'Deletes a payment gateway by UUID and reorders remaining payment gateways.',
        summary: 'Delete payment gateway by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the payment gateway to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateway deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Payment gateway not found'),
        ]
    )]
    public function deletePaymentGateway(Request $request, $uuid): JsonResponse
    {
        $payment_gateway = PaymentGateway::find($uuid);

        if (empty($payment_gateway)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $home_company_uuid = $payment_gateway->home_company_uuid;

        try {
            $deleted = $payment_gateway->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
            PaymentGateway::reorder($home_company_uuid);
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

    #[OA\Post(
        path: 'admin.api.payment_gateway.update_order.post',
        description: 'Updates the display order of a payment gateway by moving it to a new position within its home company.',
        summary: 'Update payment gateway order',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['uuid', 'new_order'],
                    properties: [
                        new OA\Property(property: 'uuid', description: 'UUID of the payment gateway to reorder', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'new_order', description: 'New order position', type: 'integer', example: 2),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateway order updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Payment gateway not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postPaymentGatewayUpdateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:payment_gateways,uuid',
            'new_order' => 'required|integer',
        ], [
            'uuid.required' => __('error.The uuid field is required'),
            'uuid.exists' => __('error.The uuid does not exist in the payment_gateways table'),
            'new_order.required' => __('error.The new_order field is required'),
            'new_order.integer' => __('error.The new_order must be an integer'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $paymentGateway = PaymentGateway::where('uuid', $request->input('uuid'))->first();

        if (! $paymentGateway) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $paymentGateways = PaymentGateway::orderBy('order')
            ->where('home_company_uuid', $paymentGateway->home_company_uuid)
            ->get();

        $minOrder = $paymentGateways->first()->order;
        $maxOrder = $paymentGateways->last()->order;

        if ($paymentGateway->order == $minOrder && $request->input('new_order') < $minOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The first item cannot be moved up')],
            ], 422);
        }

        if ($paymentGateway->order == $maxOrder && $request->input('new_order') > $maxOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The last item cannot be moved down')],
            ], 422);
        }

        $currentOrder = $paymentGateway->order;
        $newOrder = $request->input('new_order');

        if ($newOrder > $currentOrder) {
            PaymentGateway::where('order', '>', $currentOrder)
                ->where('home_company_uuid', $paymentGateway->home_company_uuid)
                ->where('order', '<=', $newOrder)
                ->decrement('order');
        } else {
            PaymentGateway::where('order', '<', $currentOrder)
                ->where('home_company_uuid', $paymentGateway->home_company_uuid)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }

        $paymentGateway->order = $newOrder;
        $paymentGateway->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }
}
