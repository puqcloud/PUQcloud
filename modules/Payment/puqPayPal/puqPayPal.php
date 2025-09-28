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
use App\Models\Invoice;
use App\Modules\Payment;
use Illuminate\Http\JsonResponse;
use Modules\Payment\puqPayPal\Services\PayPalClient;

class puqPayPal extends Payment
{
    public string $payment_gateway_uuid = '';

    public array $module_data = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        $this->module_data = [
            'client_id' => $data['client_id'] ?? '',
            'secret' => $data['secret'] ?? '',
            'webhook_id' => $data['webhook_id'] ?? '',

            'sandbox_client_id' => $data['sandbox_client_id'] ?? '',
            'sandbox_secret' => $data['sandbox_secret'] ?? '',
            'sandbox_webhook_id' => $data['sandbox_webhook_id'] ?? '',

            'sandbox' => $data['sandbox'] ?? '',
        ];

        return $this->module_data;
    }

    public function getSettingsPage(array $data = []): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['webhook_url'] = route('static.module.post', ['type' => 'Payment', 'name' => 'puqPayPal', 'method' => 'apiWebhookPost', 'uuid' => $this->payment_gateway_uuid]);
        $data['admin'] = app('admin');

        return $this->view('configuration', $data);
    }

    public function saveModuleData(array $data = []): array
    {
        if ($data['sandbox'] == 'yes') {
            $data['sandbox'] = true;
        } else {
            $data['sandbox'] = false;
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    /**
     * Generate the HTML for client area PayPal payment.
     *
     * @param array{
     *     invoice: \App\Models\Invoice,
     *     uuid: string
     * } $data
     * @return string HTML content for the client area.
     */
    public function getClientAreaHtml(array $data = []): string
    {
        $invoice = $data['invoice'];
        $currency = $invoice->client->currency;
        $due_amount = $invoice->getDueAmountAttribute();

        $paypal = new PayPalClient($this->module_data);

        $response = $paypal->createOrder(
            referenceId: $invoice->uuid,
            invoiceId: $invoice->number,
            description: 'Cloud Service Credit',
            amount: $due_amount,
            currency: $currency->code,
            return_url: route('client.web.panel.module.web', ['type' => $this->module_type, 'name' => $this->module_name, 'method' => 'returnUrl', 'uuid' => $this->payment_gateway_uuid]),
            cancel_url: route('client.web.panel.module.web', ['type' => $this->module_type, 'name' => $this->module_name, 'method' => 'returnUrl', 'uuid' => $this->payment_gateway_uuid]),
        );

        return $this->view('client_area', ['data' => $response]);
    }

    public function adminPermissions(): array
    {
        return [
            [
                'name' => 'Test Connection',
                'key' => 'test-connection',
                'description' => 'Permission for Test Connection',
            ],
        ];
    }

    public function adminApiRoutes(): array
    {
        return [
            [
                'method' => 'post',
                'uri' => 'test_connection/{uuid}',
                'permission' => 'test-connection',
                'name' => 'test_connection.post',
                'controller' => 'puqPayPal@testConnection',
            ],
        ];
    }

    /**
     * @param array{
     *     request: \Illuminate\Http\Request,
     *     uuid: string
     * } $data
     * @return array{
     *     blade: string,
     *     variables: array{
     *         order_id: string|null,
     *         uuid: string,
     *         title: string
     *     }
     * }
     */
    public function controllerClientWeb_returnUrl(array $data = []): array
    {
        $title = __('Payment.puqPayPal.Payment Status');
        $request = $data['request'];
        $orderId = $request->get('token');

        return ['blade' => 'return_url', 'variables' => [
            'order_id' => $orderId,
            'payment_gateway_uuid' => $this->payment_gateway_uuid,
            'title' => $title,
        ]];
    }

    /**
     * Handles PayPal API return URL via POST after client payment is completed.
     *
     * @param array{
     *     request: \Illuminate\Http\Request,
     *     uuid: string
     * } $data
     */
    public function controllerClientApi_apiReturnUrlPost(array $data = []): JsonResponse
    {
        $request = $data['request'];
        $orderId = $request->get('token');

        $paypal = new PayPalClient($this->module_data);
        $result = $paypal->captureOrder($orderId);

        if ($result['status'] === 'success' && $result['data']['status'] === 'COMPLETED') {

            $purchase = $result['data']['purchase_units'][0] ?? [];
            $capture = $purchase['payments']['captures'][0] ?? [];

            $invoice_uuid = $purchase['reference_id'] ?? null;
            $transactionId = $capture['id'] ?? null;
            $amount = $capture['amount']['value'] ?? null;
            $paypalFee = $capture['seller_receivable_breakdown']['paypal_fee']['value'] ?? null;
            $description = $result['data']['payer']['email_address'] ?? null;

            $add_payment = $this->handleInvoicePayment([
                'invoice_uuid' => $invoice_uuid,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'paypal_fee' => $paypalFee,
                'email' => $description,
                'method' => 'Return URL - Capture',
            ]);
            if ($add_payment['status'] === 'success') {
                return response()->json($add_payment);
            }

            return response()->json($add_payment, 422);
        }

        $errors = [];

        foreach ($result['errors'] as $error) {
            $errors[] = $error['name'] ? ' '.' '.$error['message'] : '';
        }

        return response()->json([
            'status' => 'error',
            'errors' => $errors,
        ], 422);
    }

    public function controllerClientStatic_apiWebhookPost(array $data = []): JsonResponse
    {
        $request = $data['request'];
        $payload = $request->all();
        $headers = array_change_key_case($request->headers->all(), CASE_LOWER);

        $paypal = new PayPalClient($this->module_data);

        $pp_headers = [
            'paypal-auth-algo' => $headers['paypal-auth-algo'][0] ?? '',
            'paypal-cert-url' => $headers['paypal-cert-url'][0] ?? '',
            'paypal-transmission-id' => $headers['paypal-transmission-id'][0] ?? '',
            'paypal-transmission-sig' => $headers['paypal-transmission-sig'][0] ?? '',
            'paypal-transmission-time' => $headers['paypal-transmission-time'][0] ?? '',
        ];

        if (! $paypal->verifyWebhookSignature($pp_headers, $payload)) {
            $this->logError('WebhookInvalidSignature', $pp_headers, $payload);

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        $this->logInfo('WebhookReceived', $eventType, $resource);

        $invoice_uuid = $resource['purchase_units'][0]['reference_id'] ?? '';
        $invoice = Invoice::query()->where('uuid', $invoice_uuid)->first();

        if (! $invoice) {
            return response()->json(['status' => 'error', 'errors' => ['Invoice not found']], 404);
        }

        if ($invoice->status !== 'unpaid') {
            return response()->json([
                'status' => 'error',
                'errors' => ['Invoice already paid']]
            );
        }

        // CHECKOUT.ORDER.APPROVED
        if ($eventType === 'CHECKOUT.ORDER.APPROVED') {
            $orderId = $resource['id'] ?? null;

            if (! $orderId) {
                $this->logError('WebhookOrderApprovedMissingOrderId', [], $payload);

                return response()->json(['status' => 'error', 'message' => 'Missing order ID'], 422);
            }

            $result = $paypal->captureOrder($orderId);
            $this->logDebug('WebhookOrderApprovedCaptureResponse', $orderId, $result);

            if ($result['status'] === 'success' && ($result['data']['status'] ?? '') === 'COMPLETED') {
                $purchase = $result['data']['purchase_units'][0] ?? [];
                $capture = $purchase['payments']['captures'][0] ?? [];

                $add_payment = $this->handleInvoicePayment([
                    'invoice_uuid' => $purchase['reference_id'] ?? null,
                    'transaction_id' => $capture['id'] ?? null,
                    'amount' => $capture['amount']['value'] ?? null,
                    'paypal_fee' => $capture['seller_receivable_breakdown']['paypal_fee']['value'] ?? null,
                    'email' => $result['data']['payer']['email_address'] ?? null,
                    'method' => 'Webhook - Capture after Approved',
                ]);
                if ($add_payment['status'] === 'success') {
                    return response()->json($add_payment);
                }

                return response()->json($add_payment, 422);
            }

            $this->logError('WebhookOrderApprovedCaptureFailed', $orderId, $result);

            return response()->json(['status' => 'error', 'message' => 'Capture failed'], 422);
        }

        $this->logDebug('WebhookUnknownEvent', ['event' => $eventType], $payload);

        return response()->json(['status' => 'success', 'message' => 'Event skipped']);
    }
}
