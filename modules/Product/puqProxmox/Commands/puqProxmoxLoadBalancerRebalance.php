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

use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Modules\Product\puqProxmox\Models\PuqPmLoadBalancer;

class puqProxmoxLoadBalancerRebalance extends Command
{

    protected $signature = 'puqProxmox:LoadBalancerRebalance';
    protected $description = 'Rebalances all active Proxmox load balancers by redistributing assigned nodes and DNS records.';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle(): int
    {
        $this->info(str_repeat('=', 70));
        $this->info(' Starting Proxmox Load Balancer Rebalance ');
        $this->info(str_repeat('=', 70));
        $this->line('');

        $loadBalancers = PuqPmLoadBalancer::query()->get();

        if ($loadBalancers->isEmpty()) {
            $this->warn('No active Load Balancers found for rebalance.');
            return 0;
        }

        foreach ($loadBalancers as $loadBalancer) {
            $this->info("-> Rebalancing: {$loadBalancer->name} (UUID: {$loadBalancer->uuid})");

            $loadBalancer->getWebProxiesSystemStatus();

            $result = $loadBalancer->rebalance();

            if (($result['status'] ?? '') === 'success') {
                $this->info('   Rebalance completed successfully.');
            } else {
                $this->error('   Rebalance failed with the following errors:');
                foreach (($result['errors'] ?? []) as $error) {
                    $this->error("      - {$error}");
                }
            }

            $this->line('');
        }

        $this->info(str_repeat('=', 70));
        $this->info(' Rebalance process completed for all Load Balancers ');
        $this->info(str_repeat('=', 70));

        return 0;
    }
}
