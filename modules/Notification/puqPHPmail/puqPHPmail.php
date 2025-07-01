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
use Illuminate\Support\Facades\Validator;

class puqPHPmail extends Notification
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        return [
            'email' => $data['email'] ?? '',
            'sender_name' => $data['sender_name'] ?? '',
        ];
    }

    public function getSettingsPage(array $data = []): string
    {
        $data['admin'] = app('admin');
        if (in_array('mail', explode(',', ini_get('disable_functions')))) {
            $data['php_mail_enabled'] = false;
        } else {
            $data['php_mail_enabled'] = true;
        }
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;

        return $this->view('configuration', $data);
    }

    public function saveModuleData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'sender_name' => 'string',
        ], [
            'email.required' => __('error.The email field is required'),
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'sender_name.string' => __('error.The Sender Name must be a valid string'),
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
                'controller' => 'puqPHPmail@testConnection',
            ],
        ];
    }

    public function send(array $data = []): array
    {
        $to = $data['to_email'];
        $subject = $data['subject'] ?? '';
        $message = $data['layout_text'] ?? '';
        $headers = 'From: "'.$data['sender_name'].'" <'.$data['email'].">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        $boundary = md5(uniqid(time()));
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        $messageBody = "--$boundary\r\n";
        $messageBody .= "Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n\r\n";

        $messageBody .= "--alt-$boundary\r\n";
        $messageBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $messageBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $messageBody .= $message."\r\n\r\n";
        $messageBody .= "--alt-$boundary--\r\n";

        if (! empty($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                $fileName = $attachment['name'];
                $mimeType = $attachment['mime'] ?? 'application/octet-stream';
                if (str_starts_with($mimeType, 'text/')) {
                    $decodedContent = base64_decode($attachment['data']);
                    $decodedContent = mb_convert_encoding($decodedContent, 'UTF-8');
                    $decodedContent = str_replace(["\r\n", "\r"], "\n", $decodedContent);
                    $decodedContent = str_replace("\n", "\r\n", $decodedContent);
                    $fileContent = chunk_split(base64_encode($decodedContent));
                } else {
                    $fileContent = chunk_split($attachment['data']);
                }
                $messageBody .= "--$boundary\r\n";
                $messageBody .= "Content-Type: $mimeType; charset=UTF-8; name=\"$fileName\"\r\n";
                $messageBody .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n";
                $messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $messageBody .= $fileContent."\r\n\r\n";
            }
        }

        $messageBody .= "--$boundary--";

        if (mail($to, $subject, $messageBody, $headers)) {
            $this->logInfo('send', ['data' => $data, 'success']);

            return [
                'status' => 'success',
                'to_email' => $to,
            ];
        } else {
            $error = error_get_last();
            $this->logError('send', [
                'data' => $data,
            ], $error);

            return [
                'status' => 'error',
                'error' => $error,
                'to_email' => $to,
            ];
        }
    }
}
