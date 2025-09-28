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

namespace Modules\Notification\puqTraccarSMS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NotificationSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class puqTraccarSMS extends Controller
{
    public function testConnection(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $admin = app('admin');
        $data = $request->all();

        $toPhone = $data['phone_number'] ?? '';
        if (empty($toPhone)) {
            $toPhone = $admin->phone_number ?? '';
        }

        if (empty($toPhone)) {
            return response()->json([
                'status' => 'error',
                'message' => [__('Notification.puqTraccarSMS.Phone_number_is_not_provided_and_admin_phone_number_is_not_set')],
            ], 422);
        }

        $text = "Hello {$admin->firstname} {$admin->lastname},\n\n".
            "This is a test SMS to confirm that the connection with the SMS service is working correctly.\n\n".
            "Best regards,\nPUQcloud Team";

        $sendData = [
            'to_phone' => $toPhone,
            'text_mini' => $text,
        ];

        $notification_sender = NotificationSender::find($uuid);
        if (empty($notification_sender)) {
            return response()->json([
                'status' => 'error',
                'message' => [__('error.Module not found')],
            ], 404);
        }

        $send = $notification_sender->send($sendData);

        if (($send['status'] ?? 'error') === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => __('message.Successfully'),
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => [($send['error'] ?? '')],
        ], 500);
    }
}


