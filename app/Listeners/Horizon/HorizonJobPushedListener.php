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

namespace App\Listeners\Horizon;

use App\Models\Task;
use Laravel\Horizon\Events\JobPushed;

class HorizonJobPushedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(JobPushed $event)
    {
        $jobId = $event->payload['id'];
        $tags = $event->payload['tags'];
        $attempts = $event->payload['attempts'];
        $maxTries = $event->payload['maxTries'];

        $taskId = 0;
        foreach ($tags as $tag) {
            $taskId = str_replace('task_id:', '', $tag);
        }
        $task = Task::find($taskId);
        if ($task) {
            $task->update([
                'job_id' => $jobId,
                'job_name' => $event->payload['displayName'],
                'status' => 'pending',
                'added_at' => now(),
                'attempts' => $attempts,
                'maxTries' => $maxTries,
            ]);
        } else {
            $task = new Task([
                'job_id' => $jobId,
                'job_name' => $event->payload['displayName'],
                'status' => 'pending',
                'added_at' => now(),
                'attempts' => $attempts,
                'maxTries' => $maxTries,
            ]);
            $task->save();
        }
    }
}
