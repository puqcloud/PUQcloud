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
use App\Models\Schedule as ScheduleModel;
use App\Services\SchedulerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command('horizon:snapshot')->everyFiveMinutes();

try {
    SchedulerService::createSchedules();
    $schedulers = ScheduleModel::query()->where('disable', false)->get();
    foreach ($schedulers as $scheduler) {
        Schedule::command($scheduler->artisan)->cron($scheduler->cron)->after(function () use ($scheduler) {
            $scheduler->last_run_at = now();
            $scheduler->save();
        });

    }
} catch (\Exception $e) {
    Log::error('Error occurred while scheduling tasks: '.$e->getMessage());
}

Schedule::command('Cron:lastRun')->everyMinute();
