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

namespace Modules\Payment\puqPayPal\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayPalClient
{
    protected string $clientId;

    protected string $secret;

    protected string $webhookId;

    protected string $baseUrl;

    protected ?string $token = null;

    public function __construct(array $config)
    {
        $sandbox = $config['sandbox'] ?? true;

        $this->clientId = $sandbox ? $config['sandbox_client_id'] ?? '' : $config['client_id'] ?? '';
        $this->secret = $sandbox ? $config['sandbox_secret'] ?? '' : $config['secret'] ?? '';
        $this->webhookId = $sandbox ? $config['sandbox_webhook_id'] ?? '' : $config['webhook_id'] ?? '';
        $this->baseUrl = $sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        $this->token = $this->getAccessToken();
    }

    protected function getAccessToken(): ?string
    {
        $requestData = [
            'grant_type' => 'client_credentials',
        ];

        $url = $this->baseUrl.'/v1/oauth2/token';

        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post($url, $requestData);

        if (function_exists('logModule')) {
            logModule(
                'Payment',
                'puqPayPal',
                'GetAccessToken',
                'info',
                [
                    'url' => $url,
                    'auth' => 'Basic Auth Used',
                    'request' => $requestData,
                ],
                'Requesting access token...'
            );
        }

        if ($response->successful()) {
            return $response->json('access_token');
        }

        if (function_exists('logModule')) {
            logModule(
                'Payment',
                'puqPayPal',
                'GetAccessToken',
                'error',
                [
                    'url' => $url,
                    'auth' => 'Basic Auth Used',
                    'request' => $requestData,
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                ],
                $response->body()
            );
        }

        return null;
    }

    public function createOrder(
        string $referenceId,
        string $invoiceId,
        string $description,
        string $amount,
        string $currency,
        string $return_url,
        string $cancel_url,
    ): array {
        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $referenceId,
                'description' => $description,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $amount,
                ],
                'invoice_id' => $invoiceId,
            ]],
            'application_context' => [
                'return_url' => config('paypal.return_url', $return_url),
                'cancel_url' => config('paypal.cancel_url', $cancel_url),
            ],
        ];

        if (function_exists('logModule')) {
            logModule(
                'Payment',
                'puqPayPal',
                'CreateOrder',
                'info',
                $data,
                'Sending order creation request...'
            );
        }

        $response = $this->request('POST', '/v2/checkout/orders', $data);

        if ($response['status'] === 'success') {
            $links = $response['data']['links'] ?? [];

            foreach ($links as $link) {
                if ($link['rel'] === 'approve') {
                    return [
                        'status' => 'success',
                        'approval_url' => $link['href'],
                        'order_id' => $response['data']['id'],
                    ];
                }
            }

            if (function_exists('logModule')) {
                logModule(
                    'Payment',
                    'puqPayPal',
                    'CreateOrder',
                    'error',
                    ['links' => $links, 'order_id' => $response['data']['id'] ?? null],
                    'No approval link found'
                );
            }

            return [
                'status' => 'error',
                'errors' => ['No approval link found'],
            ];
        }

        if (function_exists('logModule')) {
            logModule(
                'Payment',
                'puqPayPal',
                'CreateOrder',
                'error',
                ['request' => $data],
                $response
            );
        }

        return $response;
    }

    public function captureOrder(string $orderId): array
    {
        if (function_exists('logModule')) {
            logModule(
                'Payment',
                'puqPayPal',
                'CaptureOrder',
                'info',
                ['orderId' => $orderId],
                'Sending capture request...'
            );
        }

        $response = $this->request('POST', "/v2/checkout/orders/{$orderId}/capture", ['orderId' => $orderId]);

        if ($response['status'] === 'success') {
            return [
                'status' => 'success',
                'data' => $response['data'],
            ];
        }

        if (function_exists('logModule')) {
            logModule(
                'Payment',
                'puqPayPal',
                'CaptureOrder',
                'error',
                ['orderId' => $orderId],
                $response
            );
        }

        return [
            'status' => 'error',
            'errors' => $response['errors'] ?? ['Unknown error during capture'],
        ];
    }

    /**
     * @throws \Exception
     */
    public function verifyWebhookSignature(array $headers, array $body): bool
    {
        $data = [
            'auth_algo' => $headers['paypal-auth-algo'] ?? '',
            'cert_url' => $headers['paypal-cert-url'] ?? '',
            'transmission_id' => $headers['paypal-transmission-id'] ?? '',
            'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
            'transmission_time' => $headers['paypal-transmission-time'] ?? '',
            'webhook_id' => $this->webhookId,
            'webhook_event' => $body,
        ];

        if (function_exists('logModule')) {
            logModule(
                'Payment',
                'puqPayPal',
                'WebhookVerification',
                'info',
                ['headers' => $headers],
                'Verifying webhook signature...'
            );
        }

        $response = $this->request('POST', '/v1/notifications/verify-webhook-signature', $data);

        if (
            $response['status'] !== 'success' ||
            ($response['data']['verification_status'] ?? '') !== 'SUCCESS'
        ) {
            if (function_exists('logModule')) {
                logModule(
                    'Payment',
                    'puqPayPal',
                    'WebhookVerification',
                    'error',
                    ['headers' => $headers, 'body' => $body, 'response' => $response],
                    'Webhook signature verification failed'
                );
            }

            return false;
        }

        return true;
    }

    public function request(string $method, string $endpoint, array $data = []): array
    {
        if (! $this->token) {
            $errorLog = [
                'status' => 'error',
                'errors' => ['No token available'],
                'code' => 401,
            ];
            if (function_exists('logModule')) {
                logModule('Payment', 'puqPayPal', 'Request', 'error', [], $errorLog);
            }

            return $errorLog;
        }

        $http = Http::withToken($this->token)
            ->withHeaders([
                'PayPal-Request-Id' => (string) Str::uuid(),
            ])->acceptJson();
        $url = $this->baseUrl.$endpoint;

        $response = match (strtoupper($method)) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PATCH' => $http->patch($url, $data),
            'DELETE' => $http->delete($url, $data),
            default => throw new \Exception("Unsupported method: $method"),
        };

        $statusCode = $response->status();

        $logContext = [
            'url' => $url,
            'method' => strtoupper($method),
            'data' => $data,
            'status' => $statusCode,
            'headers' => $response->headers(),
        ];

        if ($response->successful()) {
            return [
                'status' => 'success',
                'data' => $response->json(),
                'code' => $statusCode,
            ];
        }

        if (function_exists('logModule')) {
            logModule(
                'Payment',
                'puqPayPal',
                'Request',
                'error',
                $logContext,
                $response->body()
            );
        }

        return [
            'status' => 'error',
            'errors' => [$response->json() ?: 'Unknown API error'],
            'code' => $statusCode,
        ];
    }
}
