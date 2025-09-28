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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class puqNextcloudClient
{
    public $server_hostname;

    public $server_httpprefix = 'https';

    public $server_port = 443;

    public $server_username;

    public $server_password;

    public function __construct(array $config)
    {
        $this->server_hostname = $config['host'];
        $this->server_port = $config['port'];
        $this->server_username = $config['username'];
        $this->server_password = Crypt::decryptString($config['password']);

        if (! $config['ssl']) {
            $this->server_httpprefix = 'http';
        }
    }

    public function apiRequest($url, $method = 'GET', $data = []): array
    {
        if (empty($this->server_hostname)) {
            logModule(
                'Product',
                'puqNextcloud',
                'apiRequest',
                'error',
                '',
                'Server hostname not set'
            );

            return [
                'status' => 'error',
                'error' => 'API request error: Server hostname not set',
                'data' => '',
            ];
        }

        $fullUrl = "{$this->server_httpprefix}://{$this->server_hostname}:{$this->server_port}/ocs/v1.php{$url}";

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'OCS-APIRequest' => 'true',
            ])
                ->withBasicAuth($this->server_username, $this->server_password)
                ->timeout(30)
                ->withOptions([
                    'verify' => false,
                ])
                ->send($method, $fullUrl, ['form_params' => $data]);

            $json = $response->json();
        } catch (\Exception $e) {
            logModule(
                'Product',
                'puqNextcloud',
                'apiRequest',
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
                'puqNextcloud',
                'apiRequest',
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

        $meta = $json['ocs']['meta'] ?? [];
        $dataResponse = $json['ocs']['data'] ?? [];

        if (($meta['statuscode'] ?? 0) != 100) {
            $errorCode = $meta['statuscode'] ?? 0;
            $message = $meta['message'] ?? 'Unknown error';

            $errorMessage = match ($errorCode) {
                998 => 'Invalid query (998)',
                101 => 'User does not exist (101)',
                3107 => 'User already exists (3107)',
                3121, 3103 => 'Wrong password format ('.$errorCode.')',
                default => $message." ({$errorCode})",
            };

            logModule(
                'Product',
                'puqNextcloud',
                'apiRequest',
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

        return [
            'status' => 'success',
            'error' => '',
            'data' => $dataResponse,
        ];
    }

    public function apiTestConnection(): array
    {

        $response = $this->apiRequest('/cloud/users/'.$this->server_username, 'GET');
        if ($response['status'] !== 'success') {
            $errorMsg = 'API connection problem, Error: '.$response['error'];

            return [
                'status' => 'error',
                'error' => $errorMsg,
            ];
        }

        $serverInfo = $this->apiRequest('/apps/serverinfo/api/v1/info', 'GET');

        logModule(
            'Product',
            'puqNextcloud',
            'apiTestConnection',
            'info',
            ['server' => $this->server_hostname],
            $serverInfo
        );

        return $serverInfo;
    }
}
