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

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class SystemDeleteAllTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'System:DeleteAllTasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs tasks to delete all tasks';

    public function handle()
    {
        Redis::connection(name: 'horizon')->client()->flushAll();
        Task::truncate();
        $tasks = Task::all();
        foreach ($tasks as $task) {
            $task->deleteHorizonJob();
        }
    }
}
