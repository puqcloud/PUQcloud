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
 *   GET  scheduler                                -> scheduler()                [admin.web.scheduler]
 *
 * API Routes:
 *   GET  scheduler                                -> getScheduler()             [admin.api.scheduler.get]
 *   PUT  schedule/{uuid}                          -> putSchedule()              [admin.api.schedule.put]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Services\SchedulerService;
use Cron\CronExpression;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminAutomationController extends Controller
{
    public function scheduler(): View
    {
        $title = __('main.Scheduler');

        return view_admin('scheduler.scheduler', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.scheduler.get',
        description: 'Returns the list of scheduled tasks.',
        summary: 'Get scheduler list',
        security: [['apiKeyAuth' => []]],
        tags: ['Automation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Scheduler list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getScheduler(Request $request): JsonResponse
    {
        $scheduler = SchedulerService::getSchedules();

        return response()->json([
            'data' => $scheduler], 200);
    }

    #[OA\Put(
        path: 'admin.api.schedule.put',
        description: 'Updates a schedule entry, including enable/disable state and cron expression.',
        summary: 'Update schedule by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'disable', description: 'Enable or disable the schedule', type: 'boolean', example: false),
                        new OA\Property(property: 'cron', description: 'Cron expression', type: 'string', example: '*/5 * * * *'),
                    ]
                )
            )
        ),
        tags: ['Automation'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the schedule',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Schedule updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'string', example: ''),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
