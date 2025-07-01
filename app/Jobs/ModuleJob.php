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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ModuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries;

    public $backoff;

    public $timeout;

    public $maxExceptions;

    public $module;

    public $method;

    public $tags = [];

    public function __construct($data, $tags)
    {
        $this->module = $data['module'];
        $this->method = $data['method'];
        $this->tags = $tags;

        $this->tries = $data['tries'] ?? 3;
        $this->backoff = $data['backoff'] ?? 10;
        $this->timeout = $data['timeout'] ?? 600;
        $this->maxExceptions = $data['maxExceptions'] ?? 2;
    }

    public function handle()
    {
        $jobId = $this->job->getJobId();

        // run module function -----------------------------------------------------------------------------------------
        $method = $this->method;
        $result = $this->module->$method();
        // -------------------------------------------------------------------------------------------------------------

        $task = Task::where('job_id', $jobId)->firstOrFail();
        $msg = [
            'jobId' => $jobId,
            'result' => $result,
        ];
        $task->output_data = json_encode($msg);
        $task->save();
    }

    public function tags(): array
    {
        return $this->tags;
    }
}
