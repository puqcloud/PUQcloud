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

use App\Models\Client;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ViesVatNumberValidation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 10;

    public $timeout = 600;

    public $maxExceptions = 2;

    public $client_uuid = '';

    public $tags = [];

    public function __construct($data, $tags)
    {
        $this->client_uuid = $data['client_uuid'];
        $this->tags = $tags;
    }

    public function handle()
    {
        $jobId = $this->job->getJobId();

        $client = Client::find($this->client_uuid);
        $client->ViesVatNumberValidation();

        if ($client->viesValidation) {
            $msg['viesValidation'] = $client->viesValidation;
        } else {
            $msg['error'] = 'Something went wrong';
        }

        $task = Task::where('job_id', $jobId)->firstOrFail();
        $msg['jobId'] = $jobId;
        $msg['linkify'] = 'Client:'.$client->uuid;
        $task->output_data = json_encode($msg);
        $task->save();
    }

    public function tags(): array
    {
        return $this->tags;
    }
}
