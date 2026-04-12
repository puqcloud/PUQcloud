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
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class ServiceCreateServices extends Command
{

    protected $signature = 'Service:CreateServices';
    protected $description = 'Create clients pending services if insufficient funds';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $lock = Cache::lock('billing:create-services', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $processed = 0;
            Service::query()
                ->where('status', 'pending')
                ->where('billing_mode', '!=', 'invoice')
                ->chunk(100, function ($services) use (&$processed) {
                    foreach ($services as $service) {
                        $service->create();
                        $processed++;
                    }
                });

            if ($processed > 0) {
                logActivity('info', 'Service:CreateServices completed. Processed: '.$processed.' services', 'cron');
            }
        } finally {
            $lock->release();
        }
    }
}
