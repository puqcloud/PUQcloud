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

use Illuminate\Console\Command;
use Modules\Product\puqProxmox\Models\PuqPmCluster;

class puqProxmoxSyncClusters extends Command
{
    protected $signature = 'puqProxmox:SyncClusters';

    protected $description = 'Synchronize data from clusters to the system.';

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

            $info = $cluster->getSyncClusterInfo();
            if ($info['status'] === 'success') {
                $cluster->getSyncResources($info['raw']);
                $this->info('  Cluster info synced successfully.');
            } else {
                $this->error('  Errors syncing cluster info:');
                foreach ($info['errors'] as $error) {
                    $this->error("    - {$error}");
                }
            }
            $this->line('');
        }

        $this->info(str_repeat('=', 70));
        $this->info(' Synchronization finished.');
        $this->info(str_repeat('=', 70));

        return 0;
    }
}
