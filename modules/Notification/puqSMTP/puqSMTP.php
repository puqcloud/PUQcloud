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
use App\Modules\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class puqSMTP extends Notification
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        return [
            'email' => $data['email'] ?? '',
            'server' => $data['server'] ?? '',
            'sender_name' => $data['sender_name'] ?? '',
            'port' => $data['port'] ?? '',
            'encryption' => $data['encryption'] ?? '',
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
        ];
    }

    public function getSettingsPage(array $data = []): string
    {
        $data['admin'] = app('admin');
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;

        return $this->view('configuration', $data);
    }

    public function saveModuleData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'sender_name' => 'string',
            'username' => 'string',
            'server' => 'string',
            'port' => 'integer|min:1|max:65535',
            'encryption' => 'in:ssl,tls,starttls,none',
        ], [
            'email.required' => __('error.The email field is required'),
            'email.email' => __('error.The email must be a valid email address'),
            'sender_name.string' => __('error.The Sender Name must be a valid string'),
            'username.string' => __('error.The Username must be a valid string'),
            'server.string' => __('error.The Server must be a valid string'),
            'port.integer' => __('error.The Port must be a valid number'),
            'port.min' => __('error.The Port must be at least 1'),
            'port.max' => __('error.The Port must be less than or equal to 65535'),
            'encryption.in' => __('error.The Encryption must be one of the following: ssl, tls, starttls, none'),
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
                'controller' => 'puqSMTP@testConnection',
            ],
        ];
    }

    public function send(array $data = []): array
    {
        $mail_config = [
            'driver' => 'smtp',
            'host' => $data['server'] ?? '',
            'port' => $data['port'] ?? '',
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'encryption' => $data['encryption'] ?? '',
        ];

        try {
            config(['mail' => $mail_config]);
            Mail::send([], [], function ($message) use ($data) {
                $message->to($data['to_email'])
                    ->subject($data['subject'])
                    ->from($data['email'], $data['sender_name']);

                $message->html($data['layout_text']);

                if (! empty($data['attachments']) && is_array($data['attachments'])) {
                    foreach ($data['attachments'] as $attachment) {
                        $message->attachData(
                            base64_decode($attachment['data']),
                            $attachment['name'],
                            ['mime' => $attachment['mime'] ?? 'application/octet-stream']
                        );
                    }
                }
            });
            $this->logInfo('send', ['mail_config' => $mail_config, 'data' => $data], 'success');

            return [
                'status' => 'success',
                'to_email' => $data['to_email'],
            ];
        } catch (\Exception $e) {
            $this->logError('send', ['mail_config' => $mail_config, 'data' => $data], $e->getMessage());

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'to_email' => $data['to_email'],
            ];
        }
    }
}
