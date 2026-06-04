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
use Illuminate\Support\Facades\DB;

class ServiceUnsuspendServices extends Command
{

    protected $signature = 'Service:UnsuspendServices';
    protected $description = 'Unsuspend customer services when funds are sufficient';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $lock = Cache::lock('billing:unsuspend-services', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $processed = 0;

            // Prepaid services: existing logic (unsuspend when balance sufficient)
            Service::query()
                ->where('billing_mode', '!=', 'invoice')
                ->where('status', 'suspended')
                ->chunk(100, function ($services) use (&$processed) {
                    foreach ($services as $service) {
                        $service->unsuspend();
                        $processed++;
                    }
                });

            // Invoice-mode services: unsuspend if paid invoice covers the current period (fallback for handleSuccessfulPayment)
            Service::query()
                ->where('billing_mode', 'invoice')
                ->where('status', 'suspended')
                ->chunk(100, function ($services) use (&$processed) {
                    foreach ($services as $service) {
                        $hasPaidInvoice = DB::table('invoice_items')
                            ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
                            ->where('invoice_items.service_uuid', $service->uuid)
                            ->where('invoices.billing_purpose', 'service_billing')
                            ->whereIn('invoices.status', ['paid', 'invoiced'])
                            ->where('invoice_items.period_stop', '>', now())
                            ->exists();

                        if ($hasPaidInvoice) {
                            $service->unsuspendInvoiceMode();
                            $processed++;
                        }
                    }
                });

            if ($processed > 0) {
                logActivity('info', 'Service:UnsuspendServices completed. Processed: '.$processed.' services', 'cron');
            }
        } finally {
            $lock->release();
        }
    }
}
