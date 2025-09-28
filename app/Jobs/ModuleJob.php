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

    public $callback;

    public $tags = [];

    public $params = [];

    public function __construct($data, $tags)
    {
        $this->module = $data['module'];
        $this->method = $data['method'];
        $this->callback = $data['callback'] ?? null;

        $this->tags = $tags;

        $this->tries = $data['tries'] ?? 3;
        $this->backoff = $data['backoff'] ?? 10;
        $this->timeout = $data['timeout'] ?? 600;
        $this->maxExceptions = $data['maxExceptions'] ?? 2;

        $this->params = $data['params'] ?? [];
    }

    public function handle()
    {
        $jobId = $this->job->getJobId();
        $method = $this->method;

        $params = is_array($this->params) ? $this->params : [];

        $result = call_user_func_array([$this->module, $method], $params);

        if ($this->callback && method_exists($this->module, $this->callback)) {
            call_user_func([$this->module, $this->callback], $result, $jobId);
        }

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
