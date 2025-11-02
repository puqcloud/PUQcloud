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

class puqPowerDnsClient
{
    public string $api_host;

    public string $api_key;

    public int $api_timeout;

    public function __construct(array $config, int $api_timeout = 300)
    {
        $this->api_host = $config['server'] ?? '';
        $this->api_key = $config['api_key'] ?? '';
        $this->api_timeout = $api_timeout;
    }

    private function requestAPI(string $path, string $method = 'GET', array $data = []): array
    {
        $url = rtrim($this->api_host, '/').'/api/v1/'.ltrim($path, '/');

        $this->logInfo('API Request - Init', [
            'path' => $path,
            'method' => $method,
            'data' => $data,
            'url' => $url,
        ]);

        try {
            $method = strtoupper($method);

            $http = Http::withHeaders([
                'X-API-Key' => $this->api_key,
            ])->timeout($this->api_timeout)
                ->withOptions(['verify' => false]);

            $response = match ($method) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                'PUT' => $http->put($url, $data),
                'PATCH' => $http->patch($url, $data),
                'DELETE' => $http->delete($url, $data),
                default => throw new \Exception("Unsupported method: $method"),
            };

            $statusCode = $response->status();
            $json = $response->json();

            if ($response->successful()) {
                $this->logInfo('API Request - Success', ['url' => $url, 'method' => $method, 'data' => $data], $json);

                return [
                    'status' => 'success',
                    'data' => $json['data'] ?? $json,
                    'code' => $statusCode,
                ];
            }

            if (is_array($json) && ! empty($json['message'])) {
                $errors = [$json['message']];
            } elseif (is_array($json) && ! empty($json['error'])) {
                $errors = [$json['error']];
            } elseif (is_array($json) && ! empty($json)) {
                $errors = $json;
            } elseif (is_string($json)) {
                $decoded = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (! empty($decoded['error'])) {
                        $errors = [$decoded['error']];
                    } elseif (! empty($decoded['message'])) {
                        $errors = [$decoded['message']];
                    } else {
                        $errors = [$json];
                    }
                } else {
                    $errors = [$json];
                }
            } else {
                $errors = [$response->body() ?: 'Unknown API error'];
            }

            $this->logError('API Request - Error Response', $response->body(), ['url' => $url, 'method' => $method, 'data' => $data, 'status' => $statusCode]);

            return [
                'status' => 'error',
                'errors' => $errors,
                'code' => $statusCode,
            ];

        } catch (RequestException $e) {
            $this->logError('API Request - RequestException', $e->getMessage(), ['url' => $url, 'method' => $method, 'data' => $data]);

            return ['status' => 'error', 'errors' => [$e->getMessage()]];
        } catch (\Throwable $e) {
            $this->logError('API Request - Throwable', $e->getMessage(), ['url' => $url, 'method' => $method, 'data' => $data]);

            return ['status' => 'error', 'errors' => [$e->getMessage()]];
        }
    }

    public function testConnection(): array
    {
        $result = $this->requestAPI('servers', 'GET');

        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'message' => 'Connection to PowerDNS API successful',
                'data' => $result['data'],
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Failed to connect to PowerDNS API',
            'errors' => $result['errors'] ?? ['Unknown error'],
        ];
    }

    public function getZones(): array
    {
        return $this->requestAPI('servers/localhost/zones', 'GET');
    }

    public function getZone(string $zoneName): array
    {
        return $this->requestAPI("servers/localhost/zones/{$zoneName}", 'GET');
    }

    public function createZone(array $zoneData, int $chunkSize = 500): array
    {
        $initialData = $zoneData;
        $initialData['rrsets'] = [];
        $result = $this->requestAPI('servers/localhost/zones', 'POST', $initialData);

        if (! isset($zoneData['rrsets']) || empty($zoneData['rrsets'])) {
            return $result;
        }

        foreach (array_chunk($zoneData['rrsets'], $chunkSize) as $chunk) {
            $this->requestAPI("servers/localhost/zones/{$zoneData['name']}", 'PATCH', ['rrsets' => $chunk]);
        }

        return $result;
    }

    public function updateZone(string $zoneName, array $zoneData, int $chunkSize = 500): array
    {
        if (! isset($zoneData['rrsets']) || empty($zoneData['rrsets'])) {
            return $this->requestAPI("servers/localhost/zones/{$zoneName}", 'PATCH', $zoneData);
        }

        $rrsets = $zoneData['rrsets'];
        unset($zoneData['rrsets']);

        if (! empty($zoneData)) {
            $this->requestAPI("servers/localhost/zones/{$zoneName}", 'PATCH', $zoneData);
        }

        foreach (array_chunk($rrsets, $chunkSize) as $chunk) {
            $this->requestAPI("servers/localhost/zones/{$zoneName}", 'PATCH', ['rrsets' => $chunk]);
        }

        return ['status' => 'success', 'message' => 'Zone updated in chunks'];
    }

    public function deleteZone(string $zoneName): array
    {
        return $this->requestAPI("servers/localhost/zones/{$zoneName}", 'DELETE');
    }

    private function logDebug(string $action, array $details = [], mixed $message = ''): void
    {
        if (function_exists('logModule')) {
            logModule('DnsServer', 'puqPowerDNS', $action, 'debug', $details, $message);
        }
    }

    private function logInfo(string $action, array $details = [], mixed $message = ''): void
    {
        if (function_exists('logModule')) {
            logModule('DnsServer', 'puqPowerDNS', $action, 'info', $details, $message);
        }
    }

    private function logError(string $action, string $message, mixed $details = []): void
    {
        if (function_exists('logModule')) {
            logModule('DnsServer', 'puqPowerDNS', $action, 'error', $details, $message);
        }
    }
}
