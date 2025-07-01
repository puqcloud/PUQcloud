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
use Modules\Payment\puqPrzelewy24\Services\Przelewy24Client;

class puqPrzelewy24 extends Payment
{
    public string $payment_gateway_uuid = '';

    public array $module_data = [];

    public function getModuleData(array $data = []): array
    {
        $this->module_data = [

            'merchant_id' => $data['merchant_id'] ?? '',
            'pos_id' => $data['pos_id'] ?? '',
            'crc' => $data['crc'] ?? '',
            'api_key' => $data['api_key'] ?? '',

            'sandbox_merchant_id' => $data['sandbox_merchant_id'] ?? '',
            'sandbox_pos_id' => $data['sandbox_pos_id'] ?? '',
            'sandbox_crc' => $data['sandbox_crc'] ?? '',
            'sandbox_api_key' => $data['sandbox_api_key'] ?? '',

            'sandbox' => $data['sandbox'] ?? '',
        ];

        return $this->module_data;
    }

    public function getSettingsPage(array $data = []): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['webhook_url'] = route('static.module.post', ['type' => 'Payment', 'name' => 'puqPrzelewy24', 'method' => 'apiWebhookPost', 'uuid' => $this->payment_gateway_uuid]);
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

        $p24 = new Przelewy24Client($this->module_data);

        $response = $p24->registerTransaction([
            'sessionId' => $invoice->uuid,
            'description' => 'Invoice Proforma #'.$invoice->number,
            'amount' => (int) ($amount * 100),
            'currency' => $currency,
            'email' => $invoice->client->owner()->email,
            'urlReturn' => route('client.web.panel.module.web', [
                'type' => $this->module_type,
                'name' => $this->module_name,
                'method' => 'returnUrl',
                'uuid' => $this->payment_gateway_uuid,
            ]).'?sessionId='.$invoice->uuid,
            'urlStatus' => route('static.module.post', [
                'type' => $this->module_type,
                'name' => $this->module_name,
                'method' => 'statusUrl',
                'uuid' => $this->payment_gateway_uuid,
            ]).'?sessionId='.$invoice->uuid,
        ]);

        //        if ($response['status'] == 'success') {
        //            $invoice->admin_notes = $response['data']['token'] ?? '';
        //            $invoice->save();
        //        }

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
                'controller' => 'puqPrzelewy24@testConnection',
            ],
        ];
    }

    public function controllerClientWeb_returnUrl(array $data = []): array
    {
        $title = __('Payment.puqPrzelewy24.Payment Status');
        $request = $data['request'];
        $sessionId = $request->get('sessionId');

        return ['blade' => 'return_url', 'variables' => [
            'sessionId' => $sessionId,
            'payment_gateway_uuid' => $this->payment_gateway_uuid,
            'title' => $title,
        ]];
    }

    public function controllerClientApi_apiReturnUrlPost(array $data = []): JsonResponse
    {
        $request = $data['request'];
        $sessionId = $request->get('sessionId');

        if (! $sessionId) {
            return response()->json(['status' => 'error', 'errors' => [__('Payment.puqPrzelewy24.Missing Session ID')]], 400);
        }

        $invoice = Invoice::query()->where('uuid', $sessionId)->first();

        if (! $invoice) {
            return response()->json(['status' => 'error', 'errors' => [__('Payment.puqPrzelewy24.Missing Invoice')]], 400);
        }
        sleep(5);

        return response()->json([
            'status' => 'success',
            'message' => __('Payment.puqPrzelewy24.Payment Processed'),
            'data' => [
                'url' => route('client.web.panel.client.invoice.details', ['uuid' => $invoice->reference_invoice_uuid ?? $invoice->uuid]),
                'sessionId' => $sessionId,
            ],
        ]);
    }

    public function controllerClientStatic_statusUrl(array $data = []): JsonResponse
    {
        $request = $data['request'];
        $sessionId = $request->get('sessionId');

        if (! $sessionId) {
            return response()->json(['status' => 'error', 'message' => 'Missing Session ID'], 400);
        }

        $invoice = Invoice::query()->where('uuid', $sessionId)->first();

        if (! $invoice) {
            return response()->json(['status' => 'error', 'message' => 'Missing Invoice'], 400);
        }

        $p24 = new Przelewy24Client($this->module_data);
        $event = $p24->verifyStatus($request->all());

        if (! $event) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $currency = $invoice->client->currency->code;
        $amount = $invoice->getDueAmountAttribute();

        $result = $p24->verifyTransaction([
            'sessionId' => $invoice->uuid,
            'amount' => (int) ($amount * 100),
            'currency' => $currency,
            'orderId' => $request->get('orderId'),
        ]);

        if ($result['status'] == 'success' and $result['data']['status'] == 'success') {
            $add_payment = $this->handleInvoicePayment([
                'invoice_uuid' => $invoice->uuid ?? null,
                'transaction_id' => $request->get('orderId') ?? null,
                'amount' => $request->get('amount') / 100 ?? null,
                'email' => $invoice->client->owner()->email ?? null,
                'method' => 'Status URL - Verify',
            ]);
            if ($add_payment['status'] === 'success') {
                return response()->json($add_payment);
            }

            return response()->json($add_payment, 422);
        }

        return response()->json(['status' => 'success']);
    }
}
