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

namespace Modules\Product\puqProxmox\Commands;

use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Modules\Product\puqProxmox\Models\PuqPmLxcInstance;
use Carbon\Carbon;

class puqProxmoxMakeBackups extends Command
{
    protected $signature = 'puqProxmox:MakeBackups';
    protected $description = 'Make Backups task.';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $this->info("=== Starting Proxmox backup task ===");
        $instances = PuqPmLxcInstance::query()
            ->where('backup_count', '>', 0)
            ->get();

        $now = Carbon::now();
        $this->line("Current time: {$now}");

        foreach ($instances as $instance) {
            $this->line("--------------------------------------------------");
            $this->info("Processing instance: {$instance->id}, Service: {$instance->service_uuid}");

            $service = $instance->service;
            if ($service->status != 'active') {
                $this->warn("Skipped: Service status is '{$service->status}', not 'active'.");
                continue;
            }

            $schedule = $instance->getBackupSchedule();
            $today = strtolower($now->format('l'));
            $this->line("Today is: {$today}");

            if (!isset($schedule[$today]) || !$schedule[$today]['enable']) {
                $this->warn("Skipped: No schedule enabled for today.");
                continue;
            }

            $scheduledTime = Carbon::createFromTimeString($schedule[$today]['time']);
            $this->line("Scheduled backup time: {$scheduledTime->format('H:i:s')}");

            if ($now->lessThan($scheduledTime)) {
                $this->warn("Skipped: Current time is before scheduled time.");
                continue;
            }

            $lastBackup = $instance->last_backup_at ? Carbon::parse($instance->last_backup_at) : null;
            $backupNeeded = false;

            if (!$lastBackup) {
                $this->info("No previous backup found. Backup needed.");
                $backupNeeded = true;
            } else {
                $this->line("Last backup was at: {$lastBackup}");

                $sameDay = $lastBackup->format('Y-m-d') === $now->format('Y-m-d');
                $timePassed = $lastBackup->lessThan($scheduledTime);

                if (!$sameDay) {
                    $this->info("Backup needed: Last backup was on a different day.");
                    $backupNeeded = true;
                } elseif ($timePassed) {
                    $this->info("Backup needed: Last backup was before today's schedule.");
                    $backupNeeded = true;
                } else {
                    $this->warn("Skipped: Backup already done today at scheduled time.");
                }
            }

            if ($backupNeeded) {
                $this->line("Starting backup...");
                $backup = $instance->makeScheduleBackup();

                if ($backup['status'] == 'success') {
                    $this->info("Backup completed successfully.");
                    $instance->last_backup_at = $scheduledTime;
                    $instance->save();
                } else {
                    $this->error("Backup failed: " . ($backup['message'] ?? 'Unknown error.'));
                }
            }
        }

        $this->info("=== Backup task finished ===");
        return 0;
    }
}
