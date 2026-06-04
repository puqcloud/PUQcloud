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

use App\Models\Invoice;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class FinanceCleanupEmptyInvoices extends Command
{
    protected $signature = 'Finance:CleanupEmptyInvoices';
    protected $description = 'Delete invoices that have zero invoice items';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $lock = Cache::lock('finance:cleanup-empty-invoices', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $deleted = 0;

            $invoices = Invoice::query()
                ->whereDoesntHave('invoiceItems')
                ->whereDoesntHave('transactions')
                ->get();

            foreach ($invoices as $invoice) {
                logActivity(
                    'info',
                    'Finance:CleanupEmptyInvoices deleting empty invoice '
                        .($invoice->type ?? 'unknown').' #'.($invoice->number ?? 'no-number')
                        .' (uuid:'.$invoice->uuid.')'
                        .' status:'.$invoice->status
                        .' purpose:'.($invoice->billing_purpose ?? '-')
                        .' client:'.$invoice->client_uuid,
                    'cleanup',
                    null,
                    null,
                    null,
                    $invoice->client_uuid
                );
                $invoice->delete();
                $deleted++;
            }

            if ($deleted > 0) {
                logActivity('info', 'Finance:CleanupEmptyInvoices completed. Deleted: '.$deleted.' empty invoices', 'cron');
            }
        } finally {
            $lock->release();
        }
    }
}
