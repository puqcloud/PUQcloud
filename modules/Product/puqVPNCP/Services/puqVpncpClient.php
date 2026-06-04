<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class puqVpncpClient
{
    public $api_url;

    public $api_token;

    public $verify_ssl;

    public function __construct(array $config)
    {
        $this->api_url = rtrim($config['api_url'] ?? '', '/');
        $this->api_token = Crypt::decryptString($config['api_token']);
        $this->verify_ssl = $config['verify_ssl'] ?? true;
    }

    public function requestAPI($path, $method = 'GET', $data = [], $timeout = 30): array
    {
        if (empty($this->api_url)) {
            logModule(
                'Product',
                'puqVPNCP',
                'requestAPI',
                'error',
                '',
                'API URL not set'
            );

            return [
                'status' => 'error',
                'error' => 'API request error: API URL not set',
                'data' => '',
            ];
        }

        $fullUrl = $this->api_url.'/api/v1'.$path;

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->api_token,
            ])
                ->timeout($timeout)
                ->withOptions([
                    'verify' => $this->verify_ssl,
                ])
                ->send($method, $fullUrl, ['json' => $data]);

            $json = $response->json();
        } catch (\Exception $e) {
            logModule(
                'Product',
                'puqVPNCP',
                'requestAPI',
                'error',
                ['url' => $fullUrl, 'method' => $method, 'data' => $data],
                $e->getMessage()
            );

            return [
                'status' => 'error',
                'error' => 'Exception: '.$e->getMessage(),
                'data' => '',
            ];
        }

        if (empty($json)) {
            logModule(
                'Product',
                'puqVPNCP',
                'requestAPI',
                'error',
                ['url' => $fullUrl, 'method' => $method, 'data' => $data],
                'Empty API response'
            );

            return [
                'status' => 'error',
                'error' => 'Server not available or invalid credentials',
                'data' => '',
            ];
        }

        if (isset($json['success']) && $json['success'] === true) {
            return [
                'status' => 'success',
                'error' => '',
                'data' => $json['data'] ?? $json,
            ];
        }

        $rawError = $json['message'] ?? $json['error'] ?? 'Unknown API error';
        if (is_array($rawError)) {
            $errorMessage = implode(', ', array_map(function ($v) {
                return is_array($v) ? implode(', ', $v) : (string) $v;
            }, $rawError));
        } else {
            $errorMessage = (string) $rawError;
        }

        // Append field-level errors if present
        if (! empty($json['fields']) && is_array($json['fields'])) {
            $fieldErrors = [];
            foreach ($json['fields'] as $field => $msg) {
                $fieldErrors[] = $field.': '.(is_array($msg) ? implode(', ', $msg) : $msg);
            }
            $errorMessage .= ' ('.implode('; ', $fieldErrors).')';
        }

        logModule(
            'Product',
            'puqVPNCP',
            'requestAPI',
            'error',
            ['url' => $fullUrl, 'method' => $method, 'data' => $data],
            $json
        );

        return [
            'status' => 'error',
            'error' => $errorMessage,
            'data' => '',
        ];
    }

    // System
    public function getSystemStatus(): array
    {
        return $this->requestAPI('/system/status');
    }

    public function getLicense(): array
    {
        return $this->requestAPI('/license');
    }

    // Networks
    public function getNetworks(): array
    {
        return $this->requestAPI('/network');
    }

    public function getNetworkClients($name): array
    {
        return $this->requestAPI('/network/'.urlencode($name).'/client');
    }

    public function getAvailableIPs($name, $timeout = 10): array
    {
        return $this->requestAPI('/network/'.urlencode($name).'/available_ip', 'GET', [], $timeout);
    }

    // Clients
    public function createClient($data): array
    {
        return $this->requestAPI('/client', 'POST', $data);
    }

    public function getClient($name): array
    {
        return $this->requestAPI('/client/'.urlencode($name));
    }

    public function updateClient($name, $data): array
    {
        return $this->requestAPI('/client/'.urlencode($name), 'PUT', $data);
    }

    public function deleteClient($name): array
    {
        return $this->requestAPI('/client/'.urlencode($name), 'DELETE');
    }

    // Suggested
    public function getClientSuggested(): array
    {
        return $this->requestAPI('/client/suggested');
    }

    // Configs
    public function getConfigText($name): array
    {
        return $this->requestAPI('/client/'.urlencode($name).'/config/text');
    }

    public function getConfigQR($name): array
    {
        return $this->requestAPI('/client/'.urlencode($name).'/config/qr');
    }

    public function getOpenVPNProfile($name): array
    {
        return $this->requestAPI('/client/'.urlencode($name).'/openvpn/profile');
    }

    public function getIKEv2Profile($name): array
    {
        return $this->requestAPI('/client/'.urlencode($name).'/ikev2/profile');
    }

    public function getOTL($name): array
    {
        return $this->requestAPI('/client/'.urlencode($name).'/otl');
    }

    // Traffic
    public function getClientTraffic($name, $year, $month): array
    {
        return $this->requestAPI('/client/'.urlencode($name).'/traffic/'.$year.'/'.$month);
    }

    public function getClientTrafficAvailable($name): array
    {
        return $this->requestAPI('/client/'.urlencode($name).'/traffic/available');
    }

    // Test connection
    public function apiTestConnection(): array
    {
        $response = $this->getSystemStatus();
        if ($response['status'] !== 'success') {
            $errorMsg = 'API connection problem, Error: '.$response['error'];

            return [
                'status' => 'error',
                'error' => $errorMsg,
            ];
        }

        $license = $this->getLicense();
        $networks = $this->getNetworks();

        logModule(
            'Product',
            'puqVPNCP',
            'apiTestConnection',
            'info',
            ['server' => $this->api_url],
            ['system' => $response, 'license' => $license, 'networks' => $networks]
        );

        return [
            'status' => 'success',
            'error' => '',
            'data' => [
                'system' => $response['data'] ?? [],
                'license' => $license['data'] ?? [],
                'networks' => $networks['data'] ?? [],
            ],
        ];
    }
}
