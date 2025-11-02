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
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redis;

class SystemDeleteAllTasks extends Command
{

    protected $signature = 'System:DeleteAllTasks';
    protected $description = 'Runs tasks to delete all tasks';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $prefix = config('horizon.prefix');

        $cursor = null;
        do {
            [$cursor, $keys] = Redis::scan($cursor, 'MATCH', $prefix.'*', 'COUNT', 1000);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } while ($cursor != 0);

        $tasks = Task::all();
        foreach ($tasks as $task) {
            $task->deleteHorizonJob();
        }
        Task::truncate();
    }
}
