<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Dmytro Kravchenko <dmytro@kravchenko.im>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

use App\Modules\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class puqTraccarSMS extends Notification
{
    public function __construct()
    {
        parent::__construct();
    }

    public function activate(): string
    {
        // Create default Notification Sender for this module if not exists
        try {
            $moduleModel = \App\Models\Module::query()->where('type', 'Notification')->where('name', $this->module_name)->first();
            if ($moduleModel) {
                $defaultName = 'Default Traccar SMS sender';
                $sender = \App\Models\NotificationSender::where('name', $defaultName)->first();
                $data = [
                    'name' => $defaultName,
                    'module_uuid' => $moduleModel->uuid,
                    'configuration' => ['token' => '', 'url' => ''],
                    'description' => 'Created by system',
                ];
                if ($sender) {
                    $sender->update($data);
                } else {
                    \App\Models\NotificationSender::create($data);
                }
            }
        } catch (\Throwable $e) {
            $this->logError('activate', [], $e->getMessage());
        }

        return 'success';
    }

    public function getModuleData(array $data = []): array
    {
        return [
            'token' => $data['token'] ?? '',
            'url' => $data['url'] ?? '',
        ];
    }

    public function getSettingsPage(array $data = []): string
    {
        $data['admin'] = app('admin');
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['http_client_available'] = class_exists('Illuminate\\Support\\Facades\\Http');
        $data['phone_number'] = $data['phone_number'] ?? '';

        return $this->view('configuration', $data);
    }

    public function saveModuleData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'token' => 'required|string',
            'url' => 'required|url',
        ], [
            'token.required' => __('error.The token field is required'),
            'url.required' => __('error.The URL field is required'),
            'url.url' => __('error.The URL must be a valid URL'),
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
            'code' => 200,
        ];
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
                'controller' => 'puqTraccarSMS@testConnection',
            ],
        ];
    }

    public function send(array $data = []): array
    {
        $url = $data['url'] ?? '';
        $token = $data['token'] ?? '';

        // Normalize message for SMS: prefer text_mini; mirror into text for consistency in logs
        $smsMessage = $data['text_mini'] ?? '';
        if ($smsMessage === '' && ! empty($data['text'])) {
            $smsMessage = $data['text'];
        }
        if (empty($data['text'])) {
            $data['text'] = $smsMessage;
        }

        $payload = [
            'to' => $data['to_phone'] ?? '',
            'message' => $smsMessage,
        ];

        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            $rawToken = trim($token ?? '');
            if ($rawToken !== '') {
                // Determine auth scheme automatically: Basic vs Bearer vs Raw (Traccar default)
                if (str_starts_with($rawToken, 'Basic ')) {
                    $headers['Authorization'] = $rawToken;
                } elseif (str_starts_with($rawToken, 'Bearer ')) {
                    $headers['Authorization'] = $rawToken;
                } elseif (strpos($rawToken, ':') !== false) {
                    // username:password → Basic
                    $headers['Authorization'] = 'Basic '.base64_encode($rawToken);
                } else {
                    // Default: raw token (per Traccar sms.http.authorization)
                    $headers['Authorization'] = $rawToken;
                }
            }

            $response = Http::withHeaders($headers)
                ->asJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->post($url, $payload);

            if ($response->successful()) {
                $this->logInfo('send', ['data' => $data, 'payload' => $payload], 'success');

                return [
                    'status' => 'success',
                    'to_phone' => $payload['to'] ?? '',
                    'response' => $response->body(),
                ];
            }

            $error = $response->body();
            $this->logError('send', ['data' => $data, 'payload' => $payload], $error);

            return [
                'status' => 'error',
                'to_phone' => $payload['to'] ?? '',
                'error' => $error,
            ];
        } catch (\Exception $e) {
            $this->logError('send', ['data' => $data, 'payload' => $payload], $e->getMessage());

            return [
                'status' => 'error',
                'to_phone' => $payload['to'] ?? '',
                'error' => $e->getMessage(),
            ];
        }
    }
}
