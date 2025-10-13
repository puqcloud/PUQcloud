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

namespace App\Services;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\NotificationSender;
use App\Models\NotificationStatus;
use App\Models\Task;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class NotificationService
{
    protected array $config;

    public function __construct()
    {
        $categories = array_merge(
            config('adminNotifications.categories'),
            config('clientNotifications.categories')
        );
        $this->config = (array) $categories;
    }

    public function sendNotification(array $data): array
    {
        $notification = Notification::find($data['notification_uuid']);
        $notification_sender = NotificationSender::find($data['notification_sender_uuid']);
        $send = $notification_sender->send(
            [
                'to_email' => $data['to_email'] ?? '',
                'to_phone' => $data['to_phone'] ?? '',
                'subject' => $notification->subject ?? '',
                'text' => html_entity_decode($notification->text ?? ''),
                'layout_text' => html_entity_decode($notification->layout ?? ''),
                'text_mini' => $notification->text_mini ?? '',
                'attachments' => $data['attachments'] ?? [],
            ]
        );

        $notification_status = NotificationStatus::updateOrCreate(
            [
                'notification_uuid' => $notification->uuid ?? '',
                'notification_sender_uuid' => $notification_sender->uuid ?? '',
            ],
            [
                'to_email' => $send['data']['to_email'] ?? '',
                'to_phone' => $send['data']['to_phone'] ?? '',
                'bell' => $send['data']['bell'] ?? false,
                'notification_sender_module' => $notification_sender->module->module->name ?? '',
                'sending_status' => $send['data']['status'] ?? '',
                'delivery_status' => '',
            ]
        );

        $notification_status->save();

        return $send;
    }

    public function render(string $templateContent, array $variables): string
    {
        $tempFileName = 'temp_'.uniqid().'.blade.php';
        $tempFilePath = storage_path("app/temp_files/{$tempFileName}");
        if (! File::exists(storage_path('app/temp_files/'))) {
            File::makeDirectory(storage_path('app/temp_files/'), 0755, true);
        }

        try {
            File::put($tempFilePath, $templateContent);
            $renderedText = View::file($tempFilePath, $variables)->render();
        } catch (\Exception $e) {
            throw new \Exception('Failed to render template: '.$e->getMessage());
        } finally {
            File::delete($tempFilePath);
        }

        return $renderedText;
    }

    private function prepareAndSend(
        $recipient, // Admin, User
        string $role, // admin, client
        $notification_rule,
        array $data,
        ?string $overrideEmail = null,
        ?string $overridePhone = null,
        ?string $forceUuid = null,
        array $tags = []
    ): void {
        if (empty($notification_rule)) {
            return;
        }

        $senders = $notification_rule->notificationsenders;
        $layout = $notification_rule->notificationlayout;
        $template = $notification_rule->notificationtemplate;

        if (empty($senders) || empty($layout) || empty($template)) {
            return;
        }

        $locale = $recipient->language ?? config("locale.{$role}.default");
        session(['locale' => $locale]);
        App::setLocale($locale);
        TranslationService::init($role);

        $template->setLocale($locale);
        $layout->setLocale($locale);

        $subject = $this->render($template->subject, $data);
        $text = $this->render($template->text, $data);
        $text_mini = $this->render($template->text_mini, $data);

        $home_company = $recipient->getHomeCompany();

        if (empty($home_company)) {
            return;
        }

        $logoUrl = $home_company->images['logo'] ?? asset('puqcloud/images/logo.png');

        $layoutHtml = $this->render($layout->layout, [
            'locale' => $locale,
            'title' => $subject,
            'logo_url' => $logoUrl,
            'content' => html_entity_decode($text),
            'signature' => $home_company->signature ?? '',
        ]);

        $notification = new Notification([
            'subject' => $subject,
            'text' => $text,
            'text_mini' => $text_mini,
            'layout' => $layoutHtml,
            'model_type' => get_class($recipient),
            'model_uuid' => $forceUuid ?? $recipient->uuid,
        ]);
        $notification->save();

        foreach ($senders as $sender) {
            if ($role === 'client' && $tags === ['OneTimeCode'] && $sender->module->name === 'puqBell') {
                continue;
            }

            $taskData = [
                // 'data' => $data,
                'to_email' => $tags === ['OneTimeCode'] ? $overrideEmail : $recipient->email,
                'to_phone' => $tags === ['OneTimeCode'] ? $overridePhone : $recipient->phone_number,
                'notification_uuid' => $notification->uuid,
                'notification_sender_uuid' => $sender->uuid,
                'attachments' => $data['attachments'] ?? [],
            ];

            Task::add('NotificationJob', ucfirst($role).'Notification', $taskData, $tags);
        }
    }

    public function toAllAdmin($category, $notification, $data): string
    {
        $admins = Admin::all();
        foreach ($admins as $admin) {
            $this->toAdmin($admin, $category, $notification, $data);
        }

        return 'success';
    }

    public function toAdmin($admin, $category, $notification, $data): void
    {
        $data['admin'] = $admin;
        $notification_rule = $admin->notificationRule($category, $notification);
        $this->prepareAndSend($admin, 'admin', $notification_rule, $data, null, null, null, ['AdminNotification']);
    }

    public function toClient($client, $category, $notification, $data): string
    {
        $users = $client->users;
        foreach ($users as $user) {
            $this->toUser($user, $category, $notification, $data);
        }

        return 'success';
    }

    public function toUser($user, $category, $notification, $data): void
    {
        $data['user'] = $user;
        $home_company = $user->getHomeCompany();
        $group = $home_company->group;
        $notification_rule = $group->notificationRules()
            ->where('category', $category)
            ->where('notification', $notification)
            ->first();
        $this->prepareAndSend($user, 'client', $notification_rule, $data, null, null, null, ['ClientNotification']);
    }

    public function toUserOneTimeCode($user, $email, $phone_number, $notification_rule, $data): void
    {
        $data['user'] = $user;
        $this->prepareAndSend($user, 'client', $notification_rule, $data, $email, $phone_number, '0', ['OneTimeCode']);
    }

    public static function hooks(): void
    {
        add_hook('AdminFailedAuthorization', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Admin Failed Login Attempt',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('AdminChangePermissions', 0, function ($vars) {
            $notification_service = new NotificationService;
            $notification_service->toAdmin($vars['admin'], 'staffAdministrative', 'Role Change Notification', $vars);
        });

        add_hook('PendingService', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'New Order Notification',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('CreateServiceError', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Create Failed',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('CreateServiceSuccess', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Create Successful',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('SuspendServiceError', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Suspend Failed',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('SuspendServiceSuccess', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Suspend Successful',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('UnsuspendServiceError', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Unsuspend Failed',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('UnsuspendServiceSuccess', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Unsuspend Successful',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('TerminationServiceError', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Termination Failed',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('TerminationServiceSuccess', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Termination Successful',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('CancellationServiceError', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Cancellation Failed',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('UserResetPassword', 0, function ($vars) {
            $notification_service = new NotificationService;
            $notification_service->toUser($vars['user'], 'clientAdministrative', 'Client Reset Password', $vars);
        });

        add_hook('CancellationServiceSuccess', 0, function ($vars) {
            $data = [
                'data' => $vars,
                'category' => 'staffOperational',
                'notification' => 'Service Cancellation Successful',
            ];
            $tags = [
                'AllAdminNotification',
            ];
            Task::add('AllAdminNotificationJob', 'System', $data, $tags);
        });

        add_hook('UserAfterRegister', 0, function ($vars) {
            $notification_service = new NotificationService;
            $notification_service->toUser($vars['user'], 'clientAdministrative', 'Client Welcome Email', []);
        });

        add_hook('InvoiceCreated', 0, function ($vars) {

            $invoice = $vars['invoice'];

            $data = [
                'data' => [
                    'invoice' => $invoice,
                    'attachments' => [
                        [
                            'data' => $invoice->generatePdfBase64(),
                            'name' => $invoice->getSafeFilename(),
                            'mime' => 'application/pdf',
                        ]],
                ],
                'client' => $invoice->client,
                'category' => 'clientOperational',
                'notification' => 'Client Invoice Created',
            ];
            $tags = [
                'ClientNotificationJob',
            ];
            Task::add('ClientNotificationJob', 'System', $data, $tags);
        });

        add_hook('ProformaInvoiceCreated', 0, function ($vars) {

            $invoice = $vars['invoice'];

            $data = [
                'data' => [
                    'invoice' => $invoice,
                    'attachments' => [
                        [
                            'data' => $invoice->generatePdfBase64(),
                            'name' => $invoice->getSafeFilename(),
                            'mime' => 'application/pdf',
                        ]],
                ],
                'client' => $invoice->client,
                'category' => 'clientOperational',
                'notification' => 'Client Proforma Invoice Created',
            ];
            $tags = [
                'ClientNotificationJob',
            ];
            Task::add('ClientNotificationJob', 'System', $data, $tags);
        });

    }
}
