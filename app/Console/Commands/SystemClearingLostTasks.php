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

class SystemClearingLostTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'System:clearingLostTasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lost "pending", "processing" set to "failed", "duplicate"-remove';

    public function handle()
    {
        Task::query()->whereIn('status', ['duplicate'])->delete();
        $tasks = Task::query()->whereIn('status', ['pending', 'processing', 'duplicate'])->get();
        foreach ($tasks as $task) {
            if ($task->getHorizonStatus() == 'deleted') {
                $task->status = 'failed';
                $task->save();
            }
        }
    }
}
