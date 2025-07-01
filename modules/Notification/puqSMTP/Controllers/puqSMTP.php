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

namespace Modules\Notification\puqSMTP\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NotificationSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class puqSMTP extends Controller
{
    public function testConnection(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'sender_name' => 'required',
            'username' => 'required',
            'server' => 'required',
            'port' => 'integer|min:1|max:65535',
            'encryption' => 'in:ssl,tls,starttls,none',
        ], [
            'email.required' => __('error.The email field is required'),
            'sender_name.required' => __('error.The Sender Name field is required'),
            'username.required' => __('error.The Username field is required'),
            'server.required' => __('error.The Server field is required'),
            'port.integer' => __('error.The Port must be a valid number'),
            'port.min' => __('error.The Port must be at least 1'),
            'port.max' => __('error.The Port must be less than or equal to 65535'),
            'encryption.in' => __('error.The Encryption must be one of the following: ssl, tls, starttls, none'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $admin = app('admin');
        $data = $request->all();

        $fileContent = "Hello {$admin->firstname} {$admin->lastname},\n\n".
            'This is a test email to confirm that the connection with the email account '.
            ($data['email'] ?? '')." is working correctly.\n\n".
            "If you have received this email, the SMTP connection was successful.\n\n".
            "Best regards,\nPUQcloud Team";

        $base64File = base64_encode($fileContent);

        $sendData = [
            'to_email' => $admin->email ?? '',
            'subject' => 'Successful Test Connection from '.($data['sender_name'] ?? '').' ('.($data['email'] ?? '').')',
            'layout_text' => "Hello {$admin->firstname} {$admin->lastname},\n\n".
                "Please find the test connection details in the attached file.\n\n".
                "Best regards,\nPUQcloud Team",
            'attachments' => [
                [
                    'data' => $base64File,
                    'name' => 'test_connection.txt',
                    'mime' => 'text/plain',
                ],
            ],
        ];

        $notification_sender = NotificationSender::find($uuid);
        $send = $notification_sender->send($sendData);

        if ($send['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => __('message.Successfully'),
                'code' => 200,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'errors' => [$send['error'] ?? ''],
            'code' => 500,
        ]);
    }
}
