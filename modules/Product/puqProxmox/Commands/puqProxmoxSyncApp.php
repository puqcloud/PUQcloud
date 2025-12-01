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

namespace Modules\Product\puqProxmox\Commands;

use App\Models\Task;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Modules\Product\puqProxmox\Models\PuqPmAppInstance;

class puqProxmoxSyncApp extends Command
{
    protected $signature = 'puqProxmox:SyncApp';

    protected $description = 'Synchronize data from App to system.';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {

        $puq_pm_app_instances = PuqPmAppInstance::where('deploy_status', 'success')->get();

        $this->info(str_repeat('=', 70));
        $this->info('Starting synchronization...');
        $this->info(str_repeat('=', 70));

        foreach ($puq_pm_app_instances as $puq_pm_app_instance) {
            $this->info("getDiskStatus -> UUID: {$puq_pm_app_instance->uuid}");

            $data = [
                'module' => $puq_pm_app_instance,
                'method' => 'getDiskStatus',
                'tries' => 3,                   // Number of retry attempts if the job fails
                'backoff' => 30,                // Delay in seconds between retries
                'timeout' => 30,               // Max execution time for the job in seconds
                'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
                'params' => [true],
            ];

            $tags = [
                'getAppDiskStatus',
            ];

            Task::add('ModuleJob', 'puqProxmox-AppSync', $data, $tags);

            $this->line('');
        }

        $this->info(str_repeat('=', 70));
        $this->info('Synchronization finished.');
        $this->info(str_repeat('=', 70));

        return 0;
    }
}
