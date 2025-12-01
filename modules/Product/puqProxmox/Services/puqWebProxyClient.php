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

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class puqWebProxyClient
{

    public string $api_url;
    public string $api_key;
    public int $api_timeout;

    public function __construct(array $config, $api_timeout = 300)
    {

        $this->api_url = $config['api_url'] ?? '';
        //$this->api_key = Crypt::decryptString($config['api_key']);
        $this->api_key = $config['api_key'];

        $this->api_timeout = $api_timeout;
    }

    private function request(string $path, string $method = 'GET', array $data = []): array
    {
        $url = $this->api_url."/".$path;

//        $this->logDebug('API Request - Init', [
//            'path' => $path,
//            'method' => $method,
//            'data' => $data,
//            'url' => $url,
//        ]);

        try {
            $method = strtoupper($method);

//            $this->logDebug('API Request - Preparing HTTP client', [
//                'timeout' => $this->api_timeout,
//                'api_key' => $this->api_key,
//            ]);

            $http = Http::withHeaders([
                'X-API-Key' => $this->api_key,
            ])->timeout($this->api_timeout)
                ->withOptions(['verify' => false]);

//            $this->logDebug('API Request - Sending Request', [
//                'method' => $method,
//                'url' => $url,
//                'data' => $data,
//            ]);

            $response = match ($method) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                'PUT' => $http->put($url, $data),
                'PATCH' => $http->patch($url, $data),
                'DELETE' => $http->delete($url, $data),
                default => throw new \Exception("Unsupported method: $method"),
            };

            $statusCode = $response->status();

            $logContext = [
                'url' => $url,
                'method' => $method,
                'data' => $data,
                'status' => $statusCode,
                'headers' => $response->headers(),
            ];

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()['data'] ?? $response->json(),
                    'code' => $statusCode,
                ];
            }

            $this->logError('API Request - Error Response', $response->body(), $logContext);

            $json = $response->json();

            return [
                'status' => 'error',
                'errors' => $json['errors'] ?? ['Unknown API error'],
                'code' => $statusCode,
            ];

        } catch (RequestException $e) {
            $this->logError('API Request - RequestException', $e->getMessage(), [
                'url' => $url,
                'method' => $method,
                'data' => $data,
            ]);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        } catch (\Throwable $e) {
            $this->logError('API Request - Throwable', $e->getMessage(), [
                'url' => $url,
                'method' => $method,
                'data' => $data,
            ]);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    public function getSystemStatus(): array
    {
        return $this->request('system/status', 'GET', []);
    }

    public function deployMainConfig($data): array
    {
        return $this->request('nginx/deploy/main_config', 'PUT', $data);
    }

    public function deployServiceConfig($data): array
    {
        return $this->request('nginx/deploy/service_config', 'PUT', $data);
    }

    public function removeServiceConfig($data): array
    {
        return $this->request('nginx/deploy/service_config', 'DELETE', $data);
    }

    public function removeServiceConfigs($data): array
    {
        return $this->request('nginx/deploy/service_configs', 'DELETE', $data);
    }

    private function logDebug(string $action, array $details = [], mixed $message = ''): void
    {
        if (function_exists('logModule')) {
            logModule('Product', 'puqProxmox', $action, 'debug', $details, $message);
        }
    }

    private function logInfo(string $action, array $details = [], mixed $message = ''): void
    {
        if (function_exists('logModule')) {
            logModule('Product', 'puqProxmox', $action, 'info', $details, $message);
        }
    }

    private function logError(string $action, string $message, mixed $details = []): void
    {
        if (function_exists('logModule')) {
            logModule('Product', 'puqProxmox', $action, 'error', $details, $message);
        }
    }
}
