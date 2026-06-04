<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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
 *   GET  task_queue                                            -> taskQueue()           [admin.web.task_queue]
 *
 * API Routes:
 *   GET  tasks                                                 -> getTasks()            [admin.api.tasks.get]
 *   GET  task/{uuid}                                           -> getTask()             [admin.api.task.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminTaskController extends Controller
{
    public function taskQueue(): View
    {
        $title = __('main.Task Queue');

        return view_admin('task_queue.tasks', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.tasks.get',
        description: 'Returns a DataTable list of tasks.',
        summary: 'Get tasks list',
        security: [['apiKeyAuth' => []]],
        tags: ['Tasks'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tasks list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getTasks(Request $request): JsonResponse
    {
        $query = Task::query()
            ->select([
                'tasks.uuid',
                'tasks.job_name',
                'tasks.job_id',
                'tasks.queue',
                'tasks.tags',
                'tasks.status',
                'tasks.attempts',
                'tasks.maxTries',
                'tasks.added_at',
                'tasks.started_at',
                'tasks.filed_at',
                'tasks.completed_at',
            ]);
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->start_date);
            $endDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->end_date);
            $query->whereBetween('tasks.created_at', [$startDate, $endDate]);
        }

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('tasks.created_at', 'like', "%{$search}%")
                                ->orWhere('tasks.job_id', 'like', "%{$search}%")
                                ->orWhere('tasks.status', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($task) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('task-queue-view')) {
                        $urls['get'] = route('admin.api.task.get', $task->uuid);
                    }

                    return $urls;
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->order[0]['column'];
                        $dir = $request->order[0]['dir'];
                        if ($order == 0) {
                            $query->orderBy('tasks.created_at', $dir);
                        }
                    }
                    $query->orderByDesc('tasks.updated_at');
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.task.get',
        description: 'Returns detailed information about a task, including decoded input and output data.',
        summary: 'Get task details by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the task',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'job_name', type: 'string'),
                                new OA\Property(property: 'job_id', type: 'string'),
                                new OA\Property(property: 'queue', type: 'string'),
                                new OA\Property(property: 'tags', type: 'string', nullable: true),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'attempts', type: 'integer'),
                                new OA\Property(property: 'maxTries', type: 'integer'),
                                new OA\Property(property: 'input_data', type: 'string', nullable: true),
                                new OA\Property(property: 'output_data', type: 'string', nullable: true),
                                new OA\Property(property: 'added_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'started_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'filed_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getTask(Request $request, $uuid): JsonResponse
    {
        $task = Task::find($uuid);

        if (! $task) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $task->toArray();

        $responseData['input_data'] = $this->decodeData($task->input_data);
        $responseData['output_data'] = $this->decodeData($task->output_data);

        return response()->json([
            'data' => $responseData,
        ]);
    }

    private function decodeData($dataContent): string
    {
        if ($this->isJson($dataContent)) {
            $decodedData = json_decode($dataContent, true);
            $prettyJson = json_encode($decodedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return htmlspecialchars($prettyJson, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $decodedData = @unserialize($dataContent, ['allowed_classes' => false]);
        $output = ($decodedData !== false || $dataContent === 'b:0;')
            ? print_r($decodedData, true)
            : $dataContent;

        return htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function isJson(?string $string): bool
    {
        if (empty($string)) {
            return false;
        }
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }
}
