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
use Modules\Product\puqProxmox\Models\PuqPmCluster;

class puqProxmoxSyncClusters extends Command
{
    protected $signature = 'puqProxmox:SyncClusters';
    protected $description = 'Synchronize data from clusters to the system.';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $clusters = PuqPmCluster::where('disable', false)->get();

        $this->info(str_repeat('=', 70));
        $this->info(' Starting synchronization of Proxmox clusters ');
        $this->info(str_repeat('=', 70));
        $this->line('');

        if ($clusters->isEmpty()) {
            $this->warn('No active clusters found for synchronization.');

            return 0;
        }

        foreach ($clusters as $cluster) {
            $this->info("-> Syncing cluster: {$cluster->name} (UUID: {$cluster->uuid})");

            $data = [
                'module' => $cluster,
                'method' => 'getSyncResources',
                'tries' => 3,                   // Number of retry attempts if the job fails
                'backoff' => 30,                // Delay in seconds between retries
                'timeout' => 30,               // Max execution time for the job in seconds
                'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
                'params' => [],
            ];

            $tags = [
                'getClusterSyncResources',
            ];

            Task::add('ModuleJob', 'puqProxmox-Cluster', $data, $tags);



            $this->line('');
        }

        $this->info(str_repeat('=', 70));
        $this->info(' Synchronization finished.');
        $this->info(str_repeat('=', 70));

        return 0;
    }
}
