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

namespace Modules\Payment\puqStripe\Services;

use Illuminate\Support\Facades\Http;

class StripeClient
{
    protected string $publishableKey;

    protected string $secretKey;

    protected string $webhookSecret;

    protected string $baseUrl;

    public function __construct(array $config)
    {
        $sandbox = $config['sandbox'] ?? true;

        $this->publishableKey = $sandbox
            ? $config['sandbox_publishable_key'] ?? ''
            : $config['publishable_key'] ?? '';

        $this->secretKey = $sandbox
            ? $config['sandbox_secret_key'] ?? ''
            : $config['secret_key'] ?? '';

        $this->webhookSecret = $sandbox
            ? $config['sandbox_webhook_secret'] ?? ''
            : $config['webhook_secret'] ?? '';

        $this->baseUrl = 'https://api.stripe.com/v1';
    }

    public function createSession(
        string $referenceId,
        string $invoiceId,
        string $description,
        string $amount,
        string $currency,
        string $return_url,
        string $cancel_url,
    ): array {
        $data = [
            // 'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => intval($amount * 100),
            'line_items[0][price_data][product_data][name]' => $description,
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'client_reference_id' => $referenceId,
            'success_url' => $return_url.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancel_url.'?session_id={CHECKOUT_SESSION_ID}',
            'metadata[invoice_id]' => $invoiceId,
        ];

        return $this->request('POST', '/checkout/sessions', $data, 'Create Session');
    }

    public function testConnection(): array
    {
        return $this->request('GET', '/account', [], 'Test Connection');
    }

    public function getSession(string $sessionId): array
    {
        return $this->request(
            'GET',
            "/checkout/sessions/{$sessionId}",
            [],
            'Get Session'
        );
    }

    public function verifyWebhook(string $payload, string $sigHeader, string $action = 'Verify Webhook'): ?array
    {
        $secret = $this->webhookSecret;

        $logContext = [
            'payload' => $payload,
            'sig_header' => $sigHeader,
            'webhook_secret' => $secret,
        ];

        if (! $sigHeader) {
            if (function_exists('logModule')) {
                logModule('Payment', 'puqStripe', $action, 'error', $logContext, 'Missing Stripe Signature header');
            }

            return null;
        }

        $parts = explode(',', $sigHeader);
        $sig = null;
        $timestamp = null;

        foreach ($parts as $part) {
            if (strpos($part, 't=') === 0) {
                $timestamp = substr($part, 2);
            } elseif (strpos($part, 'v1=') === 0) {
                $sig = substr($part, 3);
            }
        }

        if (! $timestamp || ! $sig) {
            if (function_exists('logModule')) {
                logModule('Payment', 'puqStripe', $action, 'error', $logContext, 'Missing timestamp or signature in Stripe Signature header');
            }

            return null;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            if (function_exists('logModule')) {
                logModule('Payment', 'puqStripe', $action, 'error', $logContext, 'Timestamp too old or too new in Stripe Signature header');
            }

            return null;
        }

        $expectedSig = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        if (! hash_equals($expectedSig, $sig)) {
            if (function_exists('logModule')) {
                logModule('Payment', 'puqStripe', $action, 'error', $logContext,
                    [
                        'expectedSig' => $expectedSig,
                        'sig', $sig,
                        'error' => 'Signature mismatch',
                    ]);
            }

            return null;
        }

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (function_exists('logModule')) {
                logModule('Payment', 'puqStripe', $action, 'error', $logContext, 'Invalid JSON payload');
            }

            return null;
        }

        if (function_exists('logModule')) {
            logModule('Payment', 'puqStripe', $action, 'info', $logContext, 'Webhook verification passed');
        }

        return $data;
    }

    protected function request(string $method, string $endpoint, array $data = [], string $action = 'Request'): array
    {
        $url = $this->baseUrl.$endpoint;

        $response = Http::withToken($this->secretKey)
            ->asForm()
            ->$method($url, $data);

        $logContext = [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'status' => $response->status(),
        ];

        $responseBody = $response->body();
        try {
            $parsedBody = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $parsedBody = $responseBody;
        }

        if ($response->failed()) {
            if (function_exists('logModule')) {
                logModule('Payment', 'puqStripe', $action, 'error', $logContext, $parsedBody);
            }

            return [
                'status' => 'error',
                'code' => $response->status(),
                'errors' => [$parsedBody['error']['message'] ?? 'Unknown error'],
                'data' => $parsedBody,
            ];
        }

        if (function_exists('logModule')) {
            logModule('Payment', 'puqStripe', $action, 'info', $logContext, $parsedBody);
        }

        return [
            'status' => 'success',
            'data' => is_array($parsedBody) ? $parsedBody : ['raw' => $parsedBody],
        ];
    }
}
