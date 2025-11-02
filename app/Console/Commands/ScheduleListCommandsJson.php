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

use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;

class ScheduleListCommandsJson extends Command
{

    protected $signature = 'Schedule:listCommandsJson';
    protected $description = 'Get list of scheduled commands for the application';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle(Schedule $schedule)
    {
        $scheduledCommands = $schedule->events();
        $commandsData = [];
        foreach ($scheduledCommands as $event) {
            $commandParts = explode(' ', $event->command);
            $thirdElement = $commandParts[2] ?? null;
            $commandData = [
                'name' => $thirdElement,
                'expression' => $event->expression,
                'next_run_at' => $event->nextRunDate()->toISOString(),
            ];
            $commandsData[] = $commandData;
        }

        $this->info(json_encode($commandsData, JSON_PRETTY_PRINT));
    }
}
