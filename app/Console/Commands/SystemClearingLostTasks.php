<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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
use Illuminate\Support\Facades\Cache;

class SystemClearingLostTasks extends Command
{

    protected $signature = 'System:clearingLostTasks';
    protected $description = 'Lost "pending", "processing" set to "failed", "duplicate"-remove';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $lock = Cache::lock('system:clearing-lost-tasks', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            Task::query()->whereIn('status', ['duplicate'])->delete();
            $tasks = Task::query()->whereIn('status', ['pending', 'processing', 'duplicate'])->get();
            foreach ($tasks as $task) {
                if ($task->getHorizonStatus() == 'deleted') {
                    $task->status = 'failed';
                    $task->save();
                }
            }
        } finally {
            $lock->release();
        }
    }
}
