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
use Illuminate\Support\Facades\DB;

class ServiceSuspendServices extends Command
{
    protected $signature = 'Service:SuspendServices';
    protected $description = 'Suspend clients active services if insufficient funds';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $lock = Cache::lock('billing:suspend-services', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $processed = 0;

            // Prepaid services: existing logic
            Service::query()
                ->where('billing_mode', '!=', 'invoice')
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('billing_timestamp')
                        ->orWhere('billing_timestamp', '<=', now());
                })
                ->chunk(100, function ($services) use (&$processed) {
                    foreach ($services as $service) {
                        $service->suspend();
                        $processed++;
                    }
                });

            // Invoice-mode services: suspend if billing_timestamp <= now and no paid invoice covering the period
            Service::query()
                ->where('billing_mode', 'invoice')
                ->where('status', 'active')
                ->whereNotNull('billing_timestamp')
                ->where('billing_timestamp', '<=', now())
                ->chunk(100, function ($services) use (&$processed) {
                    foreach ($services as $service) {
                        $hasPaidInvoice = DB::table('invoice_items')
                            ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
                            ->where('invoice_items.service_uuid', $service->uuid)
                            ->where('invoices.billing_purpose', 'service_billing')
                            ->whereIn('invoices.status', ['paid', 'invoiced'])
                            ->where('invoice_items.period_stop', '>=', $service->billing_timestamp)
                            ->exists();

                        if (! $hasPaidInvoice) {
                            $service->suspendInvoiceMode();
                            $processed++;
                        }
                    }
                });

            if ($processed > 0) {
                logActivity('info', 'Service:SuspendServices completed. Processed: '.$processed.' services', 'cron');
            }
        } finally {
            $lock->release();
        }
    }
}
