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

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\AdminSessionLog;
use App\Models\ClientSessionLog;
use App\Models\ModuleLog;
use App\Models\Notification;
use App\Models\Task;
use App\Services\CleanupService;
use Illuminate\Console\Command;

class SystemCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'System:Cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all logs, sessions and other parameter lists as in history settings';

    public function handle()
    {
        CleanupService::deleteOldRecords(AdminSessionLog::class, 'time_based.admin_session_log_history');

        CleanupService::deleteOldRecords(ClientSessionLog::class, 'time_based.client_session_log_history');

        CleanupService::deleteOldRecords(ActivityLog::class, 'time_based.activity_log_history');

        CleanupService::deleteOldRecords(ModuleLog::class, 'time_based.module_log_history');

        CleanupService::deleteOldRecords(Notification::class, 'time_based.notification_history');

        CleanupService::deleteOldRecords(Task::class, 'time_based.completed_task_queue_history', ['status' => 'success']);

        CleanupService::deleteOldRecords(Task::class, 'time_based.task_queue_history');

    }
}
