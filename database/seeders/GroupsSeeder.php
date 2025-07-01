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

namespace Database\Seeders;

use App\Models\Group;
use App\Models\NotificationLayout;
use App\Models\NotificationRule;
use App\Models\NotificationSender;
use App\Models\NotificationTemplate;
use App\Services\AdminPermissionService;
use Illuminate\Database\Seeder;

class GroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notification_sender_name = 'Default PHP mail sender';
        $notification_sender_name2 = 'Default Bell sender';

        $admin_template_permission_group_name = 'Admin Template Permission';
        $client_template_permission_group_name = 'Client Template Permission';
        $admin_modules_permission_group_name = 'Admin Modules Permission';
        $system_permission_group_name = 'System Permission';
        $admin_notifications_group_name = 'Admin Notifications';
        $client_notifications_group_name = 'Client Notifications';
        $super_admin_group_name = 'Super Admin';

        // --------------------------------------------------------------------------------------------------------------
        $admin_template_permission_group = Group::where('name', $admin_template_permission_group_name)->first();
        if (! $admin_template_permission_group) {
            $admin_template_permission_group = Group::create(
                [
                    'name' => $admin_template_permission_group_name,
                    'description' => 'Creating by system',
                    'type' => 'adminTemplate',
                ]
            );
        }

        $admin_template_permission_group->refresh();
        $permissions = new AdminPermissionService;
        foreach ($permissions->getAdminTemplatePermissions() as $permission) {
            $admin_template_permission_group->addPermission($permission['key']);
        }

        // --------------------------------------------------------------------------------------------------------------
        $client_template_permission_group = Group::where('name', $client_template_permission_group_name)->first();
        if (! $client_template_permission_group) {
            $client_template_permission_group = Group::create(
                [
                    'name' => $client_template_permission_group_name,
                    'description' => 'Creating by system',
                    'type' => 'clientTemplate',
                ]
            );
        }

        $client_template_permission_group->refresh();
        $permissions = new AdminPermissionService;
        foreach ($permissions->getClientTemplatePermissions() as $permission) {
            $client_template_permission_group->addPermission($permission['key']);
        }
        // --------------------------------------------------------------------------------------------------------------
        $system_permission_group = Group::where('name', $system_permission_group_name)->first();
        if (! $system_permission_group) {
            $system_permission_group = Group::create(
                [
                    'name' => $system_permission_group_name,
                    'description' => 'Creating by system',
                    'type' => 'system',
                ]
            );
        }

        $system_permission_group->refresh();
        $permissions = new AdminPermissionService;
        foreach ($permissions->getSystemPermissions() as $permission) {
            $system_permission_group->addPermission($permission['key']);
        }

        // --------------------------------------------------------------------------------------------------------------
        Group::where('name', $admin_notifications_group_name)->delete();
        $admin_notifications_group = Group::create(
            [
                'name' => $admin_notifications_group_name,
                'description' => 'Creating by system',
                'type' => 'notification',
            ]
        );

        $admin_notifications_group->refresh();

        $notification_sender = NotificationSender::where('name', $notification_sender_name)->first();
        $notification_sender2 = NotificationSender::where('name', $notification_sender_name2)->first();

        $notification_admin_layout = NotificationLayout::where('name', 'Admin Default Layout')->first();

        foreach (config('adminNotifications.categories') as $category) {
            foreach ($category['notifications'] as $notification) {
                $notification_template = NotificationTemplate::where('name', $notification['name'])->where('category', $category['key'])->first();
                if ($notification_template) {
                    $notification_rule = NotificationRule::create(
                        [
                            'group_uuid' => $admin_notifications_group->uuid,
                            'category' => $category['key'],
                            'notification' => $notification['name'],
                            'notification_layout_uuid' => $notification_admin_layout->uuid,
                            'notification_template_uuid' => $notification_template->uuid,
                        ]
                    );
                    $notification_rule->notificationsenders()->attach([$notification_sender->uuid, $notification_sender2->uuid]);
                }
            }
        }
        // --------------------------------------------------------------------------------------------------------------
        $client_notifications_group = Group::updateOrCreate(
            ['name' => $client_notifications_group_name],
            [
                'description' => 'Creating by system',
                'type' => 'notification',
            ]
        );

        $client_notifications_group->refresh();
        $client_notifications_group->notificationRules()->delete();

        $notification_sender = NotificationSender::where('name', $notification_sender_name)->first();
        $notification_sender2 = NotificationSender::where('name', $notification_sender_name2)->first();

        $notification_client_layout = NotificationLayout::where('name', 'Client Default Layout')->first();

        foreach (config('clientNotifications.categories') as $category) {
            foreach ($category['notifications'] as $notification) {
                $notification_template = NotificationTemplate::where('name', $notification['name'])->where('category', $category['key'])->first();
                if ($notification_template) {
                    $notification_rule = NotificationRule::create(
                        [
                            'group_uuid' => $client_notifications_group->uuid,
                            'category' => $category['key'],
                            'notification' => $notification['name'],
                            'notification_layout_uuid' => $notification_client_layout->uuid,
                            'notification_template_uuid' => $notification_template->uuid,
                        ]
                    );
                    $notification_rule->notificationsenders()->attach([$notification_sender->uuid, $notification_sender2->uuid]);
                }
            }
        }
        // --------------------------------------------------------------------------------------------------------------
        $super_admin_group = Group::where('name', $super_admin_group_name)->first();
        if (! $super_admin_group) {
            $super_admin_group = Group::create(
                [
                    'name' => $super_admin_group_name,
                    'description' => 'Creating by system',
                    'type' => 'groups',
                ]
            );
        }

        // --------------------------------------------------------------------------------------------------------------
        $admin_modules_permission_group = Group::where('name', $admin_modules_permission_group_name)->first();
        if (! $admin_modules_permission_group) {
            $admin_modules_permission_group = Group::create(
                [
                    'name' => $admin_modules_permission_group_name,
                    'description' => 'Creating by system',
                    'type' => 'modules',
                ]
            );
        }

        $admin_modules_permission_group->refresh();
        $permissions = new AdminPermissionService;

        foreach ($permissions->getModulesPermissions() as $permission) {
            $admin_modules_permission_group->addPermission($permission['key']);
        }

        $super_admin_group->refresh();
        $super_admin_group->addRelatedGroup($admin_template_permission_group);
        $super_admin_group->addRelatedGroup($client_template_permission_group);
        $super_admin_group->addRelatedGroup($system_permission_group);
        $super_admin_group->addRelatedGroup($admin_notifications_group);
        $super_admin_group->addRelatedGroup($admin_modules_permission_group);

    }
}
