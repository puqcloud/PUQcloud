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

namespace App\Jobs;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClientNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 10;

    public $timeout = 600;

    public $maxExceptions = 2;

    public $data = [];

    public $tags = [];

    public function __construct($data, $tags)
    {
        $this->data = $data;
        $this->tags = $tags;
    }

    public function handle()
    {
        $jobId = $this->job->getJobId();
        $notification_service = new NotificationService;
        $to_client = $notification_service->toClient($this->data['client'], $this->data['category'], $this->data['notification'], $this->data['data']);
        $task = Task::where('job_id', $jobId)->firstOrFail();
        $msg = [
            'jobId' => $jobId,
            'to_client' => $to_client,
        ];
        $task->output_data = json_encode($msg);
        $task->save();
    }

    public function tags(): array
    {
        return $this->tags;
    }
}
