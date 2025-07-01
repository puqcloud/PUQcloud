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

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class SchedulerService
{
    public static function createSchedules(): void
    {
        $default_scheduler = config('scheduler');
        foreach ($default_scheduler as $key => $value) {
            $schedule = Schedule::where('artisan', $value['artisan'])->first();
            if (! $schedule) {
                $schedule = new Schedule;
                $schedule->artisan = $value['artisan'];
                $schedule->cron = $value['cron'];
                $schedule->disable = $value['disable'];
                $schedule->save();
            }
        }
    }

    public static function getSchedules(): array
    {
        Artisan::call('list', [
            '--format' => 'json',
        ]);
        $commands = json_decode(Artisan::output(), true);

        Artisan::call('Schedule:listCommandsJson');
        $schedule_commands = json_decode(Artisan::output(), true);

        $default_scheduler = config('scheduler');
        $schedules = [];
        foreach ($default_scheduler as $key => $value) {
            $schedule = Schedule::where('artisan', $value['artisan'])->first();
            if (! $schedule) {
                $schedule = new Schedule;
                $schedule->artisan = $value['artisan'];
                $schedule->cron = $value['cron'];
                $schedule->disable = $value['disable'];
                $schedule->save();
                $schedule->refresh();
            }
            $schedule->description = '';
            $schedule->cron_default = $value['cron'];
            $schedule->disable_default = $value['disable'];
            $admin_online = app('admin');
            $urls = [];
            if ($admin_online->hasPermission('automation-scheduler-edit')) {
                $urls['put'] = route('admin.api.schedule.put', $schedule->uuid);
            }
            $schedule->urls = $urls;

            $schedule->description = '';
            foreach ($commands['commands'] as $command) {
                if (str_starts_with($command['name'], $value['artisan'])) {
                    $schedule->description = $command['description'];
                }
            }

            $schedule->next_run_at = null;
            foreach ($schedule_commands as $command) {
                if (str_starts_with($command['name'], $schedule->artisan)) {
                    $schedule->next_run_at = Carbon::parse($command['next_run_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
                }
            }

            $schedule->group = $value['group'];
            $schedules[] = $schedule;
        }

        return $schedules;

    }
}
