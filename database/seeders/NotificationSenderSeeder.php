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

use App\Models\Module;
use App\Models\NotificationSender;
use Illuminate\Database\Seeder;

class NotificationSenderSeeder extends Seeder
{
    protected $email;

    protected $name;

    public function __construct($email = 'admin@example.com', $name = 'Default Name')
    {
        $this->email = $email;
        $this->name = $name;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $module_puqPHPmail = Module::query()->where('type', 'Notification')->where('name', 'puqPHPmail')->first();
        $notification_sender_name = 'Default PHP mail sender';
        $notification_sender_data = [
            'name' => $notification_sender_name,
            'module_uuid' => $module_puqPHPmail->uuid,
            'configuration' => ['email' => $this->email, 'sender_name' => 'PUQ Cloud'],
            'description' => 'Created by system',
        ];

        $notification_sender = NotificationSender::where('name', $notification_sender_name)->first();

        if ($notification_sender) {
            $notification_sender->update(
                $notification_sender_data
            );
        } else {
            NotificationSender::create($notification_sender_data);
        }

        $module_puqBell = Module::query()->where('type', 'Notification')->where('name', 'puqBell')->first();
        $notification_sender_name = 'Default Bell sender';
        $notification_sender_data = [
            'name' => $notification_sender_name,
            'module_uuid' => $module_puqBell->uuid,
            'configuration' => [],
            'description' => 'Created by system',
        ];

        $notification_sender = NotificationSender::where('name', $notification_sender_name)->first();

        if ($notification_sender) {
            $notification_sender->update(
                $notification_sender_data
            );
        } else {
            NotificationSender::create($notification_sender_data);
        }

    }
}
