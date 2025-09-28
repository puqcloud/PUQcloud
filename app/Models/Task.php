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

namespace App\Models;

use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\JobRepository;

class Task extends Model
{
    use ConvertsTimezone;

    protected $table = 'tasks';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    protected $fillable = [
        'job_name',
        'job_id',
        'queue',
        'input_data',
        'output_data',
        'tags',
        'status',
        'attempts',
        'maxTries',
        'added_at',
        'started_at',
        'filed_at',
        'completed_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'started_at' => 'datetime',
        'filed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function add($jobName, $queue, $inputData = [], $tags = []): string
    {
        $jobClass = "\\App\\Jobs\\$jobName";
        $jobClass2 = "App\\Jobs\\$jobName";
        ksort($inputData);
        sort($tags);

        $existingTask = self::where('job_name', $jobClass2)
            ->where('queue', $queue)
            ->where('input_data', json_encode($inputData))
            ->where('tags', json_encode($tags))
            ->whereIn('status', ['pending', 'processing'])
            ->first();
        // Log::info($existingTask);
        if (! $existingTask) {
            $task = new self([
                'job_name' => $jobClass2,
                'job_id' => '',
                'queue' => $queue,
                'input_data' => json_encode($inputData),
                'tags' => json_encode($tags),
                'status' => 'queued',
                'attempts' => 0,
                'maxTries' => 0,
                'added_at' => now(),
            ]);
            $task->save();
            $tags[] = 'task_id:'.$task->uuid;
            $jobClass::dispatch($inputData, $tags)->onQueue($queue);
        } else {
            $task = new self([
                'job_name' => $jobClass2,
                'job_id' => '',
                'queue' => $queue,
                'input_data' => json_encode($inputData),
                'tags' => json_encode($tags),
                'status' => 'duplicate',
                'attempts' => 0,
                'maxTries' => 0,
                'added_at' => now(),
                'filed_at' => now(),
            ]);
            $task->save();

            return 'duplicate';
        }

        return 'success';
    }

    public function getHorizonStatus()
    {
        $jobRepository = app(JobRepository::class);
        $job = $jobRepository->getJobs([$this->job_id]);
        if ($job) {
            $job_array = json_decode($job, true);
            if (count($job_array) > 0) {
                return $job_array[0]['status'];
            }
        }

        return 'deleted';
    }

    public static function trimMonitoredJobs()
    {
        $jobRepository = app(JobRepository::class);
        $jobRepository->trimMonitoredJobs();
    }
}
