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

use App\Models\Service;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class FinanceChargeServices extends Command
{

    protected $signature = 'Finance:ChargeServices';
    protected $description = 'Charges clients active services and creates transaction records';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $lock = Cache::lock('billing:charge-services', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $processed = 0;
            Service::query()
                ->where('billing_mode', '!=', 'invoice')
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('billing_timestamp')
                        ->orWhere('billing_timestamp', '<=', now());
                })
                ->where('termination_request', false)
                ->chunk(100, function ($services) use (&$processed) {
                    foreach ($services as $service) {
                        $service->chargeJob();
                        $processed++;
                    }
                });

            if ($processed > 0) {
                logActivity('info', 'Finance:ChargeServices completed. Processed: '.$processed.' services', 'cron');
            }
        } finally {
            $lock->release();
        }
    }
}
