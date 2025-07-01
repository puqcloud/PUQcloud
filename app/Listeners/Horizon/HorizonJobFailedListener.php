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
use Laravel\Horizon\Events\JobFailed;

class HorizonJobFailedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(JobFailed $event)
    {
        $jobId = $event->payload['id'];
        $task = Task::where('job_id', $jobId)->first();

        if ($task) {
            $task->update([
                'job_name' => $event->payload['displayName'],
                'status' => 'failed',
                'completed_at' => now(),
            ]);
        } else {
            $task = new Task([
                'job_id' => $jobId,
                'job_name' => $event->payload['displayName'],
                'status' => 'failed',
                'completed_at' => now(),
            ]);
            $task->save();
        }
    }
}
