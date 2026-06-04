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

namespace Modules\Notification\puqPHPmail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NotificationSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class puqPHPmail extends Controller
{
    #[OA\Post(
        path: 'admin.api.Notification.puqPHPmail.test_connection.post',
        description: 'Tests the PHP mail connection by sending a test email to the admin.',
        summary: 'Test PHP mail connection',
        security: [['apiKeyAuth' => []]],
        tags: ['puqPHPmail'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection test result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function testConnection(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'sender_name' => 'required',
        ], [
            'email.required' => __('error.The email field is required'),
            'sender_name.required' => __('error.The Sender Name field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ]);
        }

        $admin = app('admin');
        $data = $request->all();

        $email = $data['email'] ?? '';
        $senderName = $data['sender_name'] ?? '';

        $fileContent = "Hello {$admin->firstname} {$admin->lastname},\n\n".
            'This is a test email to confirm that the connection with the email account '.
            $email." is working correctly.\n\n".
            "If you have received this email, the PHP mail connection was successful.\n\n".
            "Best regards,\nPUQcloud Team";

        $base64File = base64_encode($fileContent);

        $sendData = [
            'to_email' => $admin->email ?? '',
            'subject' => "Successful Test Connection from {$senderName} ({$email})",
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
            'email' => $email,
            'sender_name' => $senderName,
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
