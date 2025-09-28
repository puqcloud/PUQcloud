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

use App\Models\Service;
use Illuminate\Console\Command;

class ServiceCreateServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Service:CreateServices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create clients pending services if insufficient funds';

    public function handle()
    {
        Service::query()
            ->where('status', 'pending')
            ->chunk(100, function ($services) {
                foreach ($services as $service) {
                    $service->create();
                }
            });
    }
}
