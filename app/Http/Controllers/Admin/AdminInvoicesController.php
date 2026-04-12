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
 *   GET    invoice/{uuid}                                   -> getInvoice()                          [admin.api.invoice.get]
 *   DELETE invoice/{uuid}                                   -> deleteInvoice()                       [admin.api.invoice.delete]
 *   GET    invoice/{uuid}/items                             -> getInvoiceItems()                     [admin.api.invoice.items.get]
 *   GET    invoice/{uuid}/transactions                      -> getInvoiceTransactions()              [admin.api.invoice.transactions.get]
 *   PUT    invoice/{uuid}/publish                           -> putInvoicePublish()                   [admin.api.invoice.publish.put]
 *   PUT    invoice/{uuid}/cancel                            -> putInvoiceCancel()                    [admin.api.invoice.cancel.put]
 *   POST   invoice/{uuid}/add_payment                       -> postInvoiceAddPayment()               [admin.api.invoice.add_payment.post]
 *   POST   invoice/{uuid}/make_refund                       -> postInvoiceMakeRefund()               [admin.api.invoice.make_refund.post]
 *   GET    invoice/{uuid}/pdf                               -> getInvoicePdf()                       [admin.api.invoice.pdf.get]
 *   GET    invoice/{uuid}/payment_gateways/select           -> getInvoicePaymentGatewaysSelect()     [admin.api.invoice.payment_gateways.select.get]
 *   POST   invoice/{uuid}/resend_notification               -> postInvoiceResendNotification()       [admin.api.invoice.resend_notification.post]
 *   PUT    invoice/{uuid}/due_date                          -> putInvoiceDueDate()                   [admin.api.invoice.due_date.put]
 *   PUT    invoice/{uuid}/to_draft                          -> putInvoiceToDraft()                   [admin.api.invoice.to_draft.put]
 *   POST   invoice/{uuid}/item                              -> postInvoiceItem()                     [admin.api.invoice.item.post]
 *   PUT    invoice/{uuid}/item/{item_uuid}                  -> putInvoiceItem()                      [admin.api.invoice.item.put]
 *   DELETE invoice/{uuid}/item/{item_uuid}                  -> deleteInvoiceItem()                   [admin.api.invoice.item.delete]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Services\HookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminInvoicesController extends Controller
{
    #[OA\Get(
        path: 'admin.api.invoice.get',
        description: 'Returns detailed information about an invoice by UUID.',
        summary: 'Get invoice by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
        ]
    )]
    public function getInvoice(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'data' => $invoice,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.invoice.delete',
        description: 'Deletes a draft invoice by UUID. Only invoices with draft status can be deleted.',
        summary: 'Delete invoice by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'redirect', type: 'string'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found or not in draft status'),
        ]
    )]
    public function deleteInvoice(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);
        $client_uuid = $invoice->client_uuid;

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($invoice->status != 'draft') {
            return response()->json([
                'errors' => [__('error.The status should be draft')],
            ], 404);
        }

        try {
            $deleted = $invoice->delete();
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
            'redirect' => route('admin.web.client.tab', ['uuid' => $client_uuid, 'tab' => 'invoices']),
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.invoice.items.get',
        description: 'Returns invoice line items with subtotal, tax breakdown, and total calculations.',
        summary: 'Get invoice items by invoice UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice items returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getInvoiceItems(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::with(['invoiceItems'])->where('uuid', $uuid)->first();
        $currency_format = $invoice->client->currency->format;
        $query = InvoiceItem::query()->where('invoice_uuid', $uuid);

        $subtotal = $invoice->invoiceItems->sum('amount');
        $taxes = [
            'tax_1' => $invoice->tax_1,
            'tax_2' => $invoice->tax_2,
            'tax_3' => $invoice->tax_3,
        ];

        $data = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && ! empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('description', 'like', "%{$search}%")
                            ->orWhere('amount', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('urls', function ($transaction) {
                $admin_online = app('admin');
                $urls = [];
                if ($admin_online && $admin_online->hasPermission('finance-edit')) {
                    $urls['edit'] = ''; // route('admin.api.client.transaction.get', ['uuid' => $client->uuid, 't_uuid' => $transaction->uuid]);
                }

                return $urls;
            })
            ->make(true);

        $additionalRows = [
            [
                'uuid' => null,
                'invoice_uuid' => $invoice->uuid,
                'description' => __('main.Subtotal'),
                'amount' => number_format_custom($subtotal, 2, $currency_format),
                'taxed' => null,
                'notes' => 'subtotal',
                'created_at' => null,
                'updated_at' => null,
                'urls' => [],
            ],
        ];

        foreach ($taxes as $key => $tax) {
            $name = $key.'_name';
            $amount = $key.'_amount';
            if (! empty($invoice->$name)) {
                $additionalRows[] = [
                    'invoice_uuid' => $invoice->uuid,
                    'description' => $invoice->$name." ({$tax}%)",
                    'amount' => number_format_custom($invoice->$amount, 2, $currency_format),
                    'taxed' => null,
                    'notes' => 'tax',
                    'urls' => [],
                ];
            }
        }

        $additionalRows[] = [
            'invoice_uuid' => $invoice->uuid,
            'description' => __('main.Tax'),
            'amount' => number_format_custom($invoice->tax, 2, $currency_format),
            'taxed' => null,
            'notes' => 'tax',
            'urls' => [],
        ];

        $additionalRows[] = [
            'invoice_uuid' => $invoice->uuid,
            'description' => __('main.Total'),
            'amount' => number_format_custom($invoice->total, 2, $currency_format),
            'taxed' => null,
            'notes' => 'total',
            'urls' => [],
        ];

        $data = $data->getData();
        $data->data = array_merge($data->data, $additionalRows);

        return response()->json([
            'data' => ['original' => $data],
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.invoice.transactions.get',
        description: 'Returns transactions associated with an invoice, including currency and payment gateway data.',
        summary: 'Get invoice transactions by invoice UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice transactions returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
        ]
    )]
    public function getInvoiceTransactions(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::query()->with('client.currency')->where('uuid', $uuid)->first();

        if (! $invoice) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $locale = config('locale.admin.default');
        $query = $invoice->transactions()
            ->leftJoin('translations', function ($join) use ($locale) {
                $join->on('translations.model_uuid', '=', 'transactions.payment_gateway_uuid')
                    ->where('translations.model_type', '=', 'App\\Models\\PaymentGateway')
                    ->where('translations.field', '=', 'name')
                    ->where('translations.locale', '=', $locale);
            })
            ->select('transactions.*', 'translations.value as payment_gateway_name')
            ->orderBy('transactions.transaction_date', 'desc');

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
                                ->orWhere('transactions.amount_net', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($transaction) use ($invoice) {
                    return $invoice->client->currency->toArray();
                })
                ->addColumn('payment_gateway', function ($transaction) {
                    if ($transaction->payment_gateway_uuid) {
                        return ['uuid' => $transaction->payment_gateway_uuid, 'name' => $transaction->payment_gateway_name ?? ''];
                    }

                    return [];
                })
                ->addColumn('urls', function ($transaction) {
                    $admin_online = app('admin');
                    $urls = [];

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.invoice.publish.put',
        description: 'Publishes a draft invoice, assigning it an invoice number and changing its status.',
        summary: 'Publish invoice by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice to publish',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice published successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Publish validation error'),
        ]
    )]
    public function putInvoicePublish(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $publish = $invoice->publish();

        if ($publish['status'] == 'error') {
            return response()->json([
                'errors' => $publish['errors'],
            ], 422);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $invoice,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.invoice.cancel.put',
        description: 'Cancels an invoice, reversing its status and related transactions.',
        summary: 'Cancel invoice by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice to cancel',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice cancelled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Cancel validation error'),
        ]
    )]
    public function putInvoiceCancel(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $publish = $invoice->cancel();

        if ($publish['status'] == 'error') {
            return response()->json([
                'errors' => $publish['errors'],
            ], 422);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $invoice,
        ]);
    }

    #[OA\Post(
        path: 'admin.api.invoice.add_payment.post',
        description: 'Adds a manual payment to an invoice with a specified payment gateway, amount, and transaction ID.',
        summary: 'Add payment to invoice',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['payment_gateway_uuid', 'amount', 'transaction_id'],
                    properties: [
                        new OA\Property(property: 'payment_gateway_uuid', description: 'UUID of the payment gateway', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'amount', description: 'Payment amount (minimum 0.01)', type: 'number', example: 100.00),
                        new OA\Property(property: 'transaction_id', description: 'External transaction ID', type: 'string', example: 'TXN-12345'),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment added successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'redirect', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postInvoiceAddPayment(Request $request, $uuid): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'payment_gateway_uuid' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'required',
        ], [
            'payment_gateway_uuid.required' => __('error.The Payment Gateway field is required'),
            'amount.required' => __('error.The Amount field is required'),
            'amount.numeric' => __('error.The Amount must be a number'),
            'amount.min' => __('error.The Amount must be greater than zero'),
            'transaction_id.required' => __('error.The Transaction ID field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $add_payment = $invoice->addPayment($request->all());

        if ($add_payment['status'] == 'error') {
            return response()->json([
                'errors' => $add_payment['errors'],
            ], 422);
        }

        if (! empty($add_payment['message'])) {
            return response()->json([
                'status' => 'success',
                'message' => $add_payment['message'],
            ]);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'redirect' => route('admin.web.client.tab', ['uuid' => $invoice->client->uuid, 'tab' => 'invoices', 'edit' => $invoice->reference_invoice_uuid]),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.invoice.make_refund.post',
        description: 'Creates a refund (credit note) for an invoice.',
        summary: 'Make refund for invoice',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice to refund',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Refund created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'redirect', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Refund validation error'),
        ]
    )]
    public function postInvoiceMakeRefund(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $make_refund = $invoice->makeRefund($request->all());

        if ($make_refund['status'] == 'error') {
            return response()->json([
                'errors' => $make_refund['errors'],
            ], 422);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'redirect' => route('admin.web.client.tab', ['uuid' => $invoice->client->uuid, 'tab' => 'invoices', 'edit' => $make_refund['data']['uuid'] ?? $uuid]),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.invoice.pdf.get',
        description: 'Generates and downloads a PDF file for an invoice.',
        summary: 'Download invoice PDF',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PDF file downloaded successfully',
                content: new OA\MediaType(
                    mediaType: 'application/pdf'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
        ]
    )]
    public function getInvoicePdf(Request $request, $uuid): Response|JsonResponse
    {
        $invoice = Invoice::with('homeCompany')->find($uuid);

        if (! $invoice) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return $invoice->generatePdf()->download($invoice->getSafeFilename());
    }

    #[OA\Get(
        path: 'admin.api.invoice.payment_gateways.select.get',
        description: 'Returns a select list of payment gateways available for an invoice based on its home company.',
        summary: 'Get invoice payment gateways select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
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
                description: 'Payment gateways select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
        ]
    )]
    public function getInvoicePaymentGatewaysSelect(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $search = $request->input('q');

        if (! empty($search)) {
            $payment_gateways = $invoice->homeCompany->paymentGateways()->where('key', 'like', '%'.$search.'%')->get();
        } else {
            $payment_gateways = $invoice->homeCompany->paymentGateways()->orderBy('order')->get();
        }

        $results = [];
        foreach ($payment_gateways->toArray() as $payment_gateway) {
            $results[] = [
                'id' => $payment_gateway['uuid'],
                'text' => $payment_gateway['key'],
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    #[OA\Post(
        path: 'admin.api.invoice.resend_notification.post',
        description: 'Resends the notification for an invoice. Only works for unpaid proforma or paid invoices.',
        summary: 'Resend invoice notification',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Notification sent successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Notification cannot be resent for this invoice status'),
        ]
    )]
    public function postInvoiceResendNotification(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($invoice->type === 'proforma' && $invoice->status === 'unpaid') {
            app(\App\Services\HookService::class)->callHooks('ProformaInvoiceCreated', ['invoice' => $invoice]);
        } elseif ($invoice->type === 'invoice' && $invoice->status === 'paid') {
            app(\App\Services\HookService::class)->callHooks('InvoiceCreated', ['invoice' => $invoice]);
        } else {
            return response()->json([
                'errors' => [__('error.Notification can only be resent for unpaid proforma or paid invoices')],
            ], 422);
        }

        logActivity(
            'info',
            'Invoice:'.$invoice->uuid.' ('.$invoice->number.') notification resent.',
            'resend_notification',
            null,
            null,
            null,
            $invoice->client_uuid
        );

        return response()->json([
            'status' => 'success',
            'message' => __('message.Notification sent successfully'),
        ]);
    }

    #[OA\Put(
        path: 'admin.api.invoice.due_date.put',
        description: 'Updates the due date of a standalone draft invoice.',
        summary: 'Update invoice due date',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['due_date'],
                    properties: [
                        new OA\Property(property: 'due_date', description: 'New due date', type: 'string', format: 'date', example: '2026-04-15'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Due date updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putInvoiceDueDate(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($invoice->status != 'draft' || $invoice->billing_purpose != 'standalone') {
            return response()->json([
                'errors' => [__('error.Due date can only be changed on standalone draft invoices')],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $invoice->due_date = $request->get('due_date');
        $invoice->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $invoice,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.invoice.to_draft.put',
        description: 'Reverts an invoice back to draft status.',
        summary: 'Revert invoice to draft',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice reverted to draft successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Cannot revert to draft'),
        ]
    )]
    public function putInvoiceToDraft(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $result = $invoice->toDraft();

        if ($result['status'] == 'error') {
            return response()->json([
                'errors' => $result['errors'],
            ], 422);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $invoice,
        ]);
    }

    #[OA\Post(
        path: 'admin.api.invoice.item.post',
        description: 'Adds a new line item to a standalone draft invoice.',
        summary: 'Add invoice item',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['description', 'amount'],
                    properties: [
                        new OA\Property(property: 'description', description: 'Item description', type: 'string', example: 'Service fee'),
                        new OA\Property(property: 'amount', description: 'Item amount', type: 'number', example: 50.00),
                        new OA\Property(property: 'taxed', description: 'Whether the item is taxed', type: 'boolean', example: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice item created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Validation error or invoice not in draft status'),
        ]
    )]
    public function postInvoiceItem(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($invoice->status != 'draft' || $invoice->billing_purpose != 'standalone') {
            return response()->json([
                'errors' => [__('error.Items can only be added to standalone draft invoices')],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric',
            'taxed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $item = new InvoiceItem;
        $item->invoice_uuid = $invoice->uuid;
        $item->description = $request->get('description');
        $item->amount = $request->get('amount');
        $item->taxed = $request->get('taxed', true);
        $item->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $item,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.invoice.item.put',
        description: 'Updates an existing line item on a standalone draft invoice.',
        summary: 'Update invoice item',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'item_uuid',
                description: 'UUID of the invoice item',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['description', 'amount'],
                    properties: [
                        new OA\Property(property: 'description', description: 'Item description', type: 'string', example: 'Service fee'),
                        new OA\Property(property: 'amount', description: 'Item amount', type: 'number', example: 50.00),
                        new OA\Property(property: 'taxed', description: 'Whether the item is taxed', type: 'boolean', example: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice item updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice or item not found'),
            new OA\Response(response: 422, description: 'Validation error or invoice not in draft status'),
        ]
    )]
    public function putInvoiceItem(Request $request, $uuid, $item_uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($invoice->status != 'draft' || $invoice->billing_purpose != 'standalone') {
            return response()->json([
                'errors' => [__('error.Items can only be edited on standalone draft invoices')],
            ], 422);
        }

        $item = InvoiceItem::where('uuid', $item_uuid)->where('invoice_uuid', $uuid)->first();

        if (empty($item)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric',
            'taxed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $item->description = $request->get('description');
        $item->amount = $request->get('amount');
        $item->taxed = $request->get('taxed', $item->taxed);
        $item->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $item,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.invoice.item.delete',
        description: 'Deletes a line item from a standalone draft invoice.',
        summary: 'Delete invoice item',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the invoice',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'item_uuid',
                description: 'UUID of the invoice item to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice item deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Invoice or item not found'),
            new OA\Response(response: 422, description: 'Invoice not in draft status'),
        ]
    )]
    public function deleteInvoiceItem(Request $request, $uuid, $item_uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($invoice->status != 'draft' || $invoice->billing_purpose != 'standalone') {
            return response()->json([
                'errors' => [__('error.Items can only be deleted from standalone draft invoices')],
            ], 422);
        }

        $item = InvoiceItem::where('uuid', $item_uuid)->where('invoice_uuid', $uuid)->first();

        if (empty($item)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }
}
