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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Services\SchedulerService;
use Cron\CronExpression;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAutomationController extends Controller
{
    public function scheduler(): View
    {
        $title = __('main.Scheduler');

        return view_admin('scheduler.scheduler', compact('title'));
    }

    public function getScheduler(Request $request): JsonResponse
    {
        $scheduler = SchedulerService::getSchedules();

        return response()->json([
            'data' => $scheduler], 200);
    }

    public function putSchedule(Request $request, $uuid): JsonResponse
    {
        $schedule = Schedule::find($uuid);
        if (empty($schedule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($request->has('disable') && $request->disable === false) {
            $schedule->disable = false;
        } else {
            $schedule->disable = true;
        }

        if ($request->has('cron')) {
            $cron = $request->cron;

            if (! CronExpression::isValidExpression($cron)) {
                return response()->json([
                    'errors' => [__('error.Invalid cron expression')],
                ], 400);
            }

            $schedule->cron = $cron;
        }

        $schedule->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => '',
        ]);
    }
}
