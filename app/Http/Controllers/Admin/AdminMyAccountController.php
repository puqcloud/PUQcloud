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

/*
 * WEB Routes:
 *   GET  my_account                                            -> myAccount()                       [admin.web.my_account]
 *   GET  my_account/notifications                              -> myNotifications()                 [admin.web.my_account.notifications]
 *
 * API Routes:
 *   GET  my_account                                            -> getMyAccount()                    [admin.api.my_account.get]
 *   PUT  my_account                                            -> putMyAccount()                    [admin.api.my_account.put]
 *   GET  my_account/bell_notification                          -> getBellNotification()             [admin.api.my_account.bell_notification.get]
 *   GET  my_account/bell_notification/mark_read                -> getBellNotificationMarkRead()     [admin.api.my_account.bell_notification.mark_read.get]
 *   GET  my_account/bell_notification/mark_all_read            -> getBellNotificationMarkAllRead()  [admin.api.my_account.bell_notification.mark_all_read.get]
 *   GET  my_account/notifications                              -> getNotifications()                [admin.api.my_account.notifications.get]
 *   GET  my_account/notification/{uuid}                        -> getNotification()                 [admin.api.my_account.notification.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Notification;
use App\Models\NotificationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminMyAccountController extends Controller
{
    public function myAccount(): View
    {
        $title = __('main.My Account');

        return view_admin('my_account.my_account', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.my_account.get',
        description: 'Returns the current admin account details including language data.',
        summary: 'Get current admin account',
        security: [['apiKeyAuth' => []]],
        tags: ['My Account'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'firstname', type: 'string'),
                                new OA\Property(property: 'lastname', type: 'string'),
                                new OA\Property(property: 'language', type: 'string'),
                                new OA\Property(property: 'phone_number', type: 'string', nullable: true),
                                new OA\Property(
                                    property: 'language_data',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string'),
                                        new OA\Property(property: 'text', type: 'string'),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getMyAccount(): JsonResponse
    {
        $admin = app('admin');
        $language = [];
        foreach (config('locale.admin.locales') as $key => $value) {
            if ($key == $admin->language) {
                $language = [
                    'id' => $key,
                    'text' => $value['name'].' ('.$value['native'].')',
                ];
            }
        }

        $responseData = $admin->toArray();
        $responseData['language_data'] = $language;

        return response()->json([
            'data' => $responseData,
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.my_account.put',
        description: 'Updates the current admin account details.',
        summary: 'Update current admin account',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                        new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', nullable: true),
                        new OA\Property(property: 'firstname', type: 'string', nullable: true),
                        new OA\Property(property: 'lastname', type: 'string', nullable: true),
                        new OA\Property(property: 'language', type: 'string', nullable: true),
                        new OA\Property(property: 'phone_number', type: 'string', nullable: true),
                        new OA\Property(property: 'country_code', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        tags: ['My Account'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putMyAccount(Request $request): JsonResponse
    {
        $admin = app('admin');
        $locales = array_keys(config('locale.admin.locales'));

        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|unique:admins,email,'.$admin->uuid.',uuid',
            'password' => 'min:6|confirmed',
            'firstname' => 'nullable|string',
            'lastname' => 'nullable|string',
            'language' => 'nullable|in:'.implode(',', $locales),
        ], [
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'password.min' => __('error.The password must be at least 6 characters'),
            'password.confirmed' => __('error.The password confirmation does not match'),
            'firstname.string' => __('error.The firstname must be a valid string'),
            'lastname.string' => __('error.The lastname must be a valid string'),
            'language.in' => __('error.The selected language is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('email') && ! empty($request->input('email'))) {
            $admin->email = $request->input('email');
        }

        if ($request->filled('password')) {
            $admin->password = bcrypt($request->input('password'));
        }

        if (! empty($request->input('firstname'))) {
            $admin->firstname = $request->input('firstname');
        }

        if (! empty($request->input('lastname'))) {
            $admin->lastname = $request->input('lastname');
        }

        if (! empty($request->input('language'))) {
            $admin->language = $request->input('language');
        }

        if ($request->has('phone_number') and $request->has('country_code')) {
            $tel = str_replace(' ', '', $request->input('country_code').$request->input('phone_number'));
            if (! empty($tel)) {
                $admin->phone_number = $tel;
                if (Admin::where('phone_number', $admin->phone_number)
                    ->where('uuid', '!=', $admin->uuid)
                    ->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => [__('error.This phone number is already taken')],
                    ], 422);
                }
            }
        }

        $admin->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $admin,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.my_account.bell_notification.get',
        description: 'Returns undelivered bell notifications for the current admin.',
        summary: 'Get bell notifications',
        security: [['apiKeyAuth' => []]],
        tags: ['My Account'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bell notifications returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'notification_uuid', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'subject', type: 'string'),
                                    new OA\Property(property: 'text_mini', type: 'string'),
                                    new OA\Property(property: 'notification_status_uuid', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getBellNotification(Request $request): JsonResponse
    {
        $admin = app('admin');

        $notifications = Notification::query()
            ->select([
                'notifications.uuid as notification_uuid',
                'notifications.subject',
                'notifications.text_mini',
                'notification_statuses.uuid as notification_status_uuid',
                'notifications.created_at',
            ])
            ->with('notificationstatus')
            ->join('notification_statuses', 'notifications.uuid', '=', 'notification_statuses.notification_uuid')
            ->where('notifications.model_type', get_class($admin))
            ->where('notifications.model_uuid', $admin->uuid)
            ->where('notification_statuses.bell', true)
            ->where('notification_statuses.delivery_status', '!=', 'delivered')
            ->orderBy('notification_statuses.created_at', 'desc')
            ->get()->toArray();
        if (empty($notifications)) {
            $notifications = [];
        }

        return response()->json([
            'data' => $notifications,
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.my_account.bell_notification.mark_read.get',
        description: 'Marks a specific bell notification as read.',
        summary: 'Mark bell notification as read',
        security: [['apiKeyAuth' => []]],
        tags: ['My Account'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the notification status',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification marked as read',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getBellNotificationMarkRead(Request $request): JsonResponse
    {
        $admin = app('admin');
        $notification_status = NotificationStatus::where('uuid', $request->input('uuid'))
            ->whereHas('notification', function ($query) use ($admin) {
                $query->where('model_type', get_class($admin))
                    ->where('model_uuid', $admin->uuid);
            })
            ->first();

        if (empty($notification_status)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $notification_status->delivery_status = 'delivered';
        $notification_status->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $notification_status,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.my_account.bell_notification.mark_all_read.get',
        description: 'Marks all bell notifications as read for the current admin.',
        summary: 'Mark all bell notifications as read',
        security: [['apiKeyAuth' => []]],
        tags: ['My Account'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All notifications marked as read',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'All notifications marked as read'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'updated_count', type: 'integer'),
                                new OA\Property(property: 'updated_notifications', type: 'array', items: new OA\Items(type: 'string')),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getBellNotificationMarkAllRead(Request $request): JsonResponse
    {
        $admin = app('admin');

        $notification_statuses = NotificationStatus::whereHas('notification', function ($query) use ($admin) {
            $query->where('model_type', get_class($admin))
                ->where('model_uuid', $admin->uuid);
        })
            ->where('delivery_status', '!=', 'delivered')
            ->get();

        if ($notification_statuses->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.No unread notifications found')],
                'data' => [],
            ]);
        }

        $updatedCount = NotificationStatus::whereHas('notification', function ($query) use ($admin) {
            $query->where('model_type', get_class($admin))
                ->where('model_uuid', $admin->uuid);
        })
            ->where('delivery_status', '!=', 'delivered')
            ->update(['delivery_status' => 'delivered']);

        return response()->json([
            'status' => 'success',
            'message' => __('message.All notifications marked as read'),
            'data' => [
                'updated_count' => $updatedCount,
                'updated_notifications' => $notification_statuses->pluck('uuid')->toArray(),
            ],
        ]);
    }

    public function myNotifications(): View
    {
        $title = __('main.My Notifications');

        return view_admin('my_account.notifications', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.my_account.notifications.get',
        description: 'Returns a DataTable list of notifications for the current admin.',
        summary: 'Get admin notifications list',
        security: [['apiKeyAuth' => []]],
        tags: ['My Account'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notifications list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getNotifications(Request $request): JsonResponse
    {
        $admin = app('admin');
        $query = Notification::query()
            ->select([
                'notifications.uuid',
                'notifications.subject',
                'notifications.text_mini',
                'notifications.created_at',
                'notifications.model_type',
                'notifications.model_uuid',
                'notifications.created_at',
            ])
            ->where('notifications.model_type', get_class($admin))
            ->where('notifications.model_uuid', $admin->uuid);

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->start_date);
            $endDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->end_date);
            $query->whereBetween('notifications.created_at', [$startDate, $endDate]);
        }

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('notifications.created_at', 'like', "%{$search}%")
                                ->orWhere('notifications.subject', 'like', "%{$search}%")
                                ->orWhere('notifications.text_mini', 'like', "%{$search}%")
                                ->orWhere('notifications.model_uuid', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($notification) {
                    $urls = [];
                    if (! empty($notification->uuid)) {
                        $urls['get'] = route('admin.api.my_account.notification.get', $notification->uuid);

                    }

                    return $urls;
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->order[0]['column'];
                        $dir = $request->order[0]['dir'];
                        if ($order == 0) {
                            $query->orderBy('notifications.created_at', $dir);
                        }
                    }
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.my_account.notification.get',
        description: 'Returns detailed information about a specific notification for the current admin.',
        summary: 'Get notification by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['My Account'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the notification',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'subject', type: 'string'),
                                new OA\Property(property: 'text_mini', type: 'string', nullable: true),
                                new OA\Property(property: 'model_type', type: 'string'),
                                new OA\Property(property: 'model_uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getNotification(Request $request, $uuid): JsonResponse
    {
        $admin = app('admin');

        $notification_history = Notification::query()
            ->where('notifications.model_type', get_class($admin))
            ->where('notifications.model_uuid', $admin->uuid)
            ->where('notifications.uuid', $uuid)
            ->first();

        if (empty($notification_history)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $notification_history->toArray();

        return response()->json([
            'data' => $responseData,
        ]);
    }
}
