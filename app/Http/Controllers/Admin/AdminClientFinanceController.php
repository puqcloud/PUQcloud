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
 *   GET    client/{uuid}/services                           -> getClientServices()                  [admin.api.client.services.get]
 *   GET    client/{uuid}/transactions                       -> getClientTransactions()              [admin.api.client.transactions.get]
 *   POST   client/{uuid}/transaction                        -> postClientTransaction()              [admin.api.client.transaction.post]
 *   GET    client/{uuid}/invoices                           -> getClientInvoices()                  [admin.api.client.invoices.get]
 *   POST   client/{uuid}/invoice/proforma/add-funds         -> postClientInvoiceProformaAddFunds()  [admin.api.client.invoice.proforma.add_funds.post]
 *   POST   client/{uuid}/invoice/standalone                 -> postClientInvoiceStandalone()        [admin.api.client.invoice.standalone.post]
 *   GET    client/{uuid}/services/select                    -> getServicesSelect()                  [admin.api.client.services.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminClientFinanceController extends Controller
{
    #[OA\Get(
        path: 'admin.api.client.services.get',
        description: 'Returns a DataTable list of services belonging to a specific client.',
        summary: 'Get client services list',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client services list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClientServices(Request $request, $uuid): JsonResponse
    {
        $client = Client::query()->where('uuid', $uuid)->first();
        $query = $client->services()->with('product');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('status', 'like', "%{$search}%")
                                ->orWhere('admin_label', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhereHas('product', function ($q2) use ($search) {
                                    $q2->where('key', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->addColumn('product_key', function ($service) {
                    return $service->product->key ?? '';
                })
                ->addColumn('product_name', function ($service) {
                    return $service->product->name;
                })
                ->addColumn('termination_time', function ($service) {
                    return $service->getTerminationTime();
                })
                ->addColumn('cancellation_time', function ($service) {
                    return $service->getCancellationTime();
                })
                ->addColumn('product_group_keys', function ($service) {
                    $product = $service->product;

                    return $product->productGroups()->pluck('key')->toArray();
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
                ->addColumn('urls', function ($service) use ($client) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('clients-view')) {
                        $urls['edit'] = route('admin.web.client.tab', ['uuid' => $client->uuid, 'tab' => 'services', 'edit' => $service->uuid]);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.client.transactions.get',
        description: 'Returns a DataTable list of transactions belonging to a specific client.',
        summary: 'Get client transactions list',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client transactions list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClientTransactions(Request $request, $uuid): JsonResponse
    {
        $client = Client::query()->with('currency')->where('uuid', $uuid)->first();

        if (! $client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $locale = config('locale.admin.default');
        $query = $client->transactions()
            ->leftJoin('translations', function ($join) use ($locale) {
                $join->on('translations.model_uuid', '=', 'transactions.payment_gateway_uuid')
                    ->where('translations.model_type', '=', 'App\\Models\\PaymentGateway')
                    ->where('translations.field', '=', 'name')
                    ->where('translations.locale', '=', $locale);
            })
            ->select('transactions.*', 'translations.value as payment_gateway_name');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('transactions.transaction_date', 'like', "%{$search}%")
                                ->orWhere('transactions.uuid', 'like', "%{$search}%")
                                ->orWhere('transactions.transaction_id', 'like', "%{$search}%")
                                ->orWhere('transactions.description', 'like', "%{$search}%")
                                ->orWhere('transactions.type', 'like', "%{$search}%")
                                ->orWhere('transactions.amount_net', 'like', "%{$search}%")
                                ->orWhere('transactions.amount_gross', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($transaction) use ($client) {
                    return $client->currency->toArray();
                })
                ->addColumn('payment_gateway', function ($transaction) {
                    if ($transaction->payment_gateway_uuid) {
                        return ['uuid' => $transaction->payment_gateway_uuid, 'name' => $transaction->payment_gateway_name ?? ''];
                    }

                    return [];
                })
                ->addColumn('urls', function ($transaction) use ($client) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('finance-view')) {
                        $urls['view'] = ''; // route('admin.api.client.transaction.get', ['uuid' => $client->uuid, 't_uuid' => $transaction->uuid]);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.client.transaction.post',
        description: 'Creates a new adjustment transaction for a client.',
        summary: 'Create client transaction',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['amount', 'description'],
                    properties: [
                        new OA\Property(property: 'amount', type: 'number'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'transaction_id', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transaction created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Client not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postClientTransaction(Request $request, $uuid): JsonResponse
    {
        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ((float) $value === 0.0) {
                        $fail(__('error.Amount cannot be zero'));
                    }
                },
            ],
            'transaction_id' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
        ], [
            'amount.required' => __('error.The amount field is required'),
            'amount.numeric' => __('error.The amount must be a number'),
            'description.required' => __('error.The description field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $transaction = new Transaction;

        $transaction->client_uuid = $client->uuid;

        $admin = app('admin');
        if (! empty($admin->uuid)) {
            $transaction->admin_uuid = $admin->uuid;
        }
        $transaction->type = 'adjustment';
        $transaction->amount_net = $request->get('amount');
        $transaction->amount_gross = $request->get('amount');
        $transaction->description = $request->get('description');
        $transaction->transaction_id = $request->get('transaction_id');
        $transaction->transaction_date = now();
        $transaction->period_start = now();
        $transaction->period_stop = now();
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    #[OA\Get(
        path: 'admin.api.client.invoices.get',
        description: 'Returns a DataTable list of invoices belonging to a specific client.',
        summary: 'Get client invoices list',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client invoices list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClientInvoices(Request $request, $uuid): JsonResponse
    {
        $client = Client::query()->where('uuid', $uuid)->first();

        if (! $client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $query = $client->invoices()->with('homeCompany');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('type', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('status', 'like', "%{$search}%")
                                ->orWhere('issue_date', 'like', "%{$search}%")
                                ->orWhere('due_date', 'like', "%{$search}%")
                                ->orWhere('paid_date', 'like', "%{$search}%")
                                ->orWhere('refunded_date', 'like', "%{$search}%")
                                ->orWhere('canceled_date', 'like', "%{$search}%")
                                ->orWhere('total', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($invoice) use ($client) {
                    return $client->currency->toArray();
                })
                ->addColumn('urls', function ($invoice) use ($client) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('finance-view')) {
                        $urls['edit'] = route('admin.web.client.tab', ['uuid' => $client->uuid, 'tab' => 'invoices', 'edit' => $invoice->uuid]);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.client.invoice.proforma.add_funds.post',
        description: 'Creates a proforma invoice for adding funds to a client account.',
        summary: 'Create proforma invoice for adding funds',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['amount'],
                    properties: [
                        new OA\Property(property: 'amount', type: 'number'),
                    ]
                )
            )
        ),
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proforma invoice created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Client not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postClientInvoiceProformaAddFunds(Request $request, $uuid): JsonResponse
    {
        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ((float) $value === 0.0) {
                        $fail(__('error.Amount cannot be zero'));
                    }
                },
            ],
        ], [
            'amount.required' => __('error.The amount field is required'),
            'amount.numeric' => __('error.The amount must be a number'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $client->createInvoiceProformaAddFunds($request->get('amount'));

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    #[OA\Post(
        path: 'admin.api.client.invoice.standalone.post',
        description: 'Creates a standalone custom invoice for a client.',
        summary: 'Create standalone invoice',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the client', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Standalone invoice created successfully'),
            new OA\Response(response: 404, description: 'Client not found'),
        ]
    )]
    public function postClientInvoiceStandalone(Request $request, $uuid): JsonResponse
    {
        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $invoiceUuid = $client->createStandaloneInvoice();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => ['uuid' => $invoiceUuid],
        ]);
    }

    #[OA\Get(
        path: 'admin.api.client.services.select.get',
        description: 'Returns a list of services for a client for select2 dropdown.',
        summary: 'Get client services select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
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
                description: 'Client services select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServicesSelect(Request $request, $uuid): JsonResponse
    {
        $search = $request->input('q') ?? $request->input('term');

        $query = DB::table('services')
            ->join('products', 'services.product_uuid', '=', 'products.uuid')
            ->where('services.client_uuid', $uuid)
            ->select('services.uuid', 'services.admin_label', 'products.key as product_key');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('services.uuid', 'like', "%{$search}%")
                    ->orWhere('services.admin_label', 'like', "%{$search}%");
            });
        }

        $services = $query->orderBy('services.created_at', 'desc')->get();

        $results = $services->map(function ($service) {
            return [
                'id' => $service->uuid,
                'text' => $service->product_key.' - '.$service->admin_label,
            ];
        });

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ]);
    }
}
