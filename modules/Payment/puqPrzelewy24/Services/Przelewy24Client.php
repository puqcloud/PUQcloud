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

namespace Modules\Payment\puqPrzelewy24\Services;

use Illuminate\Support\Facades\Http;

class Przelewy24Client
{
    protected string $merchantId; // Klucz do zamówień

    protected string $posId; // User

    protected string $crc;

    protected string $apiKey; // secretId

    protected string $baseUrl;

    public function __construct(array $config)
    {
        $sandbox = $config['sandbox'] ?? true;

        $this->merchantId = $sandbox
            ? $config['sandbox_merchant_id'] ?? ''
            : $config['merchant_id'] ?? '';

        $this->posId = $sandbox
            ? $config['sandbox_pos_id'] ?? ''
            : $config['pos_id'] ?? '';

        $this->crc = $sandbox
            ? $config['sandbox_crc'] ?? ''
            : $config['crc'] ?? '';

        $this->apiKey = $sandbox
            ? $config['sandbox_api_key'] ?? ''
            : $config['api_key'] ?? '';

        $this->baseUrl = $sandbox
            ? 'https://sandbox.przelewy24.pl'
            : 'https://secure.przelewy24.pl';
    }

    protected function registerTransactionSign(array $data): string
    {
        $signData = [
            'sessionId' => $data['sessionId'],
            'merchantId' => (int) $data['merchantId'],
            'amount' => (int) $data['amount'],
            'currency' => $data['currency'],
            'crc' => $this->crc,
        ];

        $json = json_encode($signData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha384', $json);
    }

    public function testConnection(): array
    {
        return $this->request('GET', '/api/v1/testAccess', [], 'Test Connection');
    }

    public function registerTransaction(array $params): array
    {
        $data = [
            'merchantId' => $this->posId,
            'posId' => $this->posId,
            'sessionId' => $params['sessionId'],
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'description' => $params['description'],
            'email' => $params['email'],
            'country' => 'PL',
            'urlReturn' => $params['urlReturn'],
            'urlStatus' => $params['urlStatus'],
            'language' => 'pl',
        ];

        $data['sign'] = $this->registerTransactionSign($data);
        $result = $this->request('POST', '/api/v1/transaction/register', $data, 'Register Transaction');

        if ($result['status'] == 'success') {
            return ['status' => 'success',
                'data' => [
                    'url' => $this->baseUrl.'/trnRequest/'.$result['data']['data']['token'],
                    'description' => $data['description'],
                    'token' => $result['data']['data']['token'],
                ],
            ];
        }

        return ['status' => 'error',
            'errors' => [$result['data']['error'] ?? ''],
            'data' => $result,
        ];
    }

    protected function verifyTransactionSign(array $data): string
    {
        $signData = [
            'sessionId' => $data['sessionId'],
            'orderId' => (int) $data['orderId'],
            'amount' => (int) $data['amount'],
            'currency' => $data['currency'],
            'crc' => $this->crc,
        ];

        $json = json_encode($signData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha384', $json);
    }

    public function verifyTransaction(array $params): array
    {
        $data = [
            'merchantId' => $this->posId,
            'posId' => $this->posId,
            'sessionId' => $params['sessionId'],
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'orderId' => $params['orderId'],
        ];

        $data['sign'] = $this->verifyTransactionSign($data);

        $result = $this->request('PUT', '/api/v1/transaction/verify', $data, 'Verify Transaction');

        if ($result['status'] == 'success') {
            return ['status' => 'success',
                'data' => [
                    'status' => $result['data']['data']['status'] ?? '',
                ],
            ];
        }

        return ['status' => 'error',
            'errors' => [$result['data']['error'] ?? ''],
            'data' => $result,
        ];
    }

    protected function verifyStatusSign(array $data): string
    {
        $signData = [
            'merchantId' => (int) $data['merchantId'],
            'posId' => (int) $data['posId'],
            'sessionId' => (string) $data['sessionId'],
            'amount' => (int) $data['amount'],
            'originAmount' => (int) $data['originAmount'],
            'currency' => (string) $data['currency'],
            'orderId' => (int) $data['orderId'],
            'methodId' => (int) $data['methodId'],
            'statement' => (string) $data['statement'],
            'crc' => $this->crc,
        ];

        $json = json_encode($signData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha384', $json);
    }

    public function verifyStatus(array $params): bool
    {
        $sign = $this->verifyStatusSign($params);

        return $sign == $params['sign'];
    }

    protected function request(string $method, string $endpoint, array $data = [], string $action = 'Request'): array
    {
        $url = $this->baseUrl.$endpoint;

        $username = $this->posId;
        $password = $this->apiKey;

        $http = Http::withBasicAuth($username, $password);

        switch (strtoupper($method)) {
            case 'GET':
                $response = $http->get($url);
                break;

            case 'PUT':
                $response = $http->asJson()->put($url, $data);
                break;

            case 'POST':
            default:
                $response = $http->asJson()->post($url, $data);
                break;
        }

        $logContext = [
            'username' => $username,
            'password' => $password,
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
            logModule('Payment', 'puqPrzelewy24', $action, 'error', $logContext, $parsedBody);

            return [
                'status' => 'error',
                'code' => $response->status(),
                'errors' => [$parsedBody['error'] ?? 'Unknown error'],
                'data' => $parsedBody,
            ];
        }

        logModule('Payment', 'puqPrzelewy24', $action, 'info', $logContext, $parsedBody);

        return [
            'status' => 'success',
            'data' => $parsedBody,
        ];
    }
}
