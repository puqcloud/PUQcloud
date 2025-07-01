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
use App\Modules\Payment;
use Illuminate\Http\JsonResponse;
use Modules\Payment\puqStripe\Services\StripeClient;

class puqStripe extends Payment
{
    public string $payment_gateway_uuid = '';

    public array $module_data = [];

    public function getModuleData(array $data = []): array
    {
        $this->module_data = [

            'publishable_key' => $data['publishable_key'] ?? '',
            'secret_key' => $data['secret_key'] ?? '',
            'webhook_secret' => $data['webhook_secret'] ?? '',

            'sandbox_publishable_key' => $data['sandbox_publishable_key'] ?? '',
            'sandbox_secret_key' => $data['sandbox_secret_key'] ?? '',
            'sandbox_webhook_secret' => $data['sandbox_webhook_secret'] ?? '',

            'sandbox' => $data['sandbox'] ?? '',
        ];

        return $this->module_data;
    }

    public function getSettingsPage(array $data = []): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['webhook_url'] = route('static.module.post', ['type' => 'Payment', 'name' => 'puqStripe', 'method' => 'apiWebhookPost', 'uuid' => $this->payment_gateway_uuid]);
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

    public function getClientAreaHtml(array $data = []): string
    {
        $invoice = $data['invoice'];
        $amount = $invoice->getDueAmountAttribute();
        $currency = $invoice->client->currency->code;

        $stripe = new StripeClient($this->module_data);

        $response = $stripe->createSession(
            referenceId: $invoice->uuid,
            invoiceId: $invoice->number,
            description: 'Invoice Proforma #'.$invoice->number,
            amount: $amount,
            currency: $currency,
            return_url: route('client.web.panel.module.web', [
                'type' => $this->module_type,
                'name' => $this->module_name,
                'method' => 'returnUrl',
                'uuid' => $this->payment_gateway_uuid,
            ]),
            cancel_url: route('client.web.panel.module.web', [
                'type' => $this->module_type,
                'name' => $this->module_name,
                'method' => 'returnUrl',
                'uuid' => $this->payment_gateway_uuid,
            ]),
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
                'controller' => 'puqStripe@testConnection',
            ],
        ];
    }

    public function controllerClientWeb_returnUrl(array $data = []): array
    {
        $title = __('Payment.puqStripe.Payment Status');
        $request = $data['request'];
        $sessionId = $request->get('session_id');

        return ['blade' => 'return_url', 'variables' => [
            'sessionId' => $sessionId,
            'payment_gateway_uuid' => $this->payment_gateway_uuid,
            'title' => $title,
        ]];
    }

    public function controllerClientApi_apiReturnUrlPost(array $data = []): JsonResponse
    {
        $request = $data['request'];
        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            return response()->json(['status' => 'error', 'message' => 'Missing session ID'], 400);
        }

        $stripe = new StripeClient($this->module_data);
        $result = $stripe->getSession($sessionId);

        if ($result['status'] !== 'success') {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Failed to retrieve session',
            ], 422);
        }

        $session = $result['data'];

        if (($session['payment_status'] ?? null) === 'paid') {
            $add_payment = $this->handleInvoicePayment([
                'invoice_uuid' => $session['client_reference_id'] ?? null,
                'transaction_id' => $session['payment_intent'] ?? null,
                'amount' => isset($session['amount_total']) ? $session['amount_total'] / 100 : null,
                'email' => $session['customer_details']['email'] ?? $session['customer_email'] ?? null,
                'method' => 'Return URL - Session',
            ]);
            if ($add_payment['status'] === 'success') {
                return response()->json($add_payment);
            }

            return response()->json($add_payment, 422);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Payment not completed',
        ], 422);
    }

    public function controllerClientStatic_apiWebhookPost(array $data = []): JsonResponse
    {
        $request = $data['request'];
        $payload = $request->getContent();
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        $stripe = new StripeClient($this->module_data);

        $event = $stripe->verifyWebhook($payload, $sig_header);

        if (! $event) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];
            $add_payment = $this->handleInvoicePayment([
                'invoice_uuid' => $session['client_reference_id'] ?? null,
                'transaction_id' => $session['payment_intent'] ?? null,
                'amount' => isset($session['amount_total']) ? $session['amount_total'] / 100 : null,
                'email' => $session['customer_details']['email'] ?? $session['customer_email'] ?? null,
                'method' => 'Webhook',
            ]);
            if ($add_payment['status'] === 'success') {
                return response()->json($add_payment);
            }

            return response()->json($add_payment, 422);
        }

        return response()->json(['status' => 'success']);
    }
}
