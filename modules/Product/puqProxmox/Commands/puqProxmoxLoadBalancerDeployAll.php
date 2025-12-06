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
use Modules\Product\puqProxmox\Models\PuqPmLoadBalancer;

class puqProxmoxLoadBalancerDeployAll extends Command
{
    protected $signature = 'puqProxmox:LoadBalancerDeployAll';

    protected $description = 'Deploy Web Proxy configuration on all load balancer nodes based on all successfully deployed app instances.';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $puq_pm_app_instances = PuqPmAppInstance::query()
            ->where('deploy_status', 'success')
            ->get();

        $puq_pm_load_balancers = PuqPmLoadBalancer::query()->get();

        $this->info(str_repeat('=', 70));
        $this->info('Starting Web Proxy deployment...');
        $this->info(str_repeat('=', 70));

        foreach ($puq_pm_load_balancers as $balancer) {

            $this->info("Deploying Web Proxy on LoadBalancer UUID: {$balancer->uuid}");

            $data = [
                'module'        => $balancer,
                'method'        => 'deployAll',          // main method inside the module
                'callback'      => 'deployAllCallback',  // callback after job is done
                'tries'         => 1,
                'backoff'       => 60,
                'timeout'       => 3600,
                'maxExceptions' => 1,
                'params'        => [$puq_pm_app_instances],
            ];

            $tags = ['deployAll'];

            Task::add('ModuleJob', 'puqProxmox-LoadBalancer', $data, $tags);

            $this->line('');
        }

        $this->info(str_repeat('=', 70));
        $this->info('Web Proxy deployment finished.');
        $this->info(str_repeat('=', 70));

        return 0;
    }
}
