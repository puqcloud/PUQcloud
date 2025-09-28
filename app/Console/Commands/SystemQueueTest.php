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

class SystemQueueTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'System:queueTest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs test tasks in all queues for 30 seconds';

    public function handle()
    {
        $horizonConfig = config('horizon');
        $productionEnvironmentConfig = $horizonConfig['queue'];
        foreach ($productionEnvironmentConfig as $queue) {
            $data = [
                'test' => $queue,
            ];
            $tags = [
                'test',
            ];
            Task::add('TestJob', $queue, $data, $tags);
        }
    }
}
