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

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Services\HookService;
use App\Services\SettingService;
use App\Services\TranslationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinanceGenerateServiceInvoices extends Command
{
    protected $signature = 'Finance:GenerateServiceInvoices';
    protected $description = 'Generates service billing invoices for invoice-mode products';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $lock = Cache::lock('billing:generate-service-invoices', 3600);
        if (! $lock->get()) {
            $this->info('Could not acquire lock, another instance is running');

            return;
        }

        try {
            $daysBefore = (int) SettingService::get('finance.invoice_generate_days_before');
            $generationMode = SettingService::get('finance.invoice_generation_mode');
            $continuousGeneration = (bool) SettingService::get('finance.continuous_invoice_generation');
            $invoiceDueDays = (int) SettingService::get('finance.invoice_due_days');

            $this->info("Settings: days_before={$daysBefore}, mode={$generationMode}, continuous={$continuousGeneration}, due_days={$invoiceDueDays}");

            $cutoffDate = now()->addDays($daysBefore);

            // Find all invoice-mode services that need invoicing
            $services = Service::query()
                ->join('prices', 'services.price_uuid', '=', 'prices.uuid')
                ->with(['product.productGroups', 'price'])
                ->where('services.billing_mode', 'invoice')
                ->where('services.termination_request', false)
                ->whereIn('services.status', ['active', 'pending'])
                ->where('prices.period', '!=', 'one-time')
                ->whereNotNull('services.billing_timestamp')
                ->where('services.billing_timestamp', '<=', $cutoffDate)
                ->select('services.*')
                ->get();

            $this->info('Found '.$services->count().' invoice-mode services needing invoicing');

            if ($services->isEmpty()) {
                return;
            }

            // Filter out services that already have unpaid invoices
            $beforeCount = $services->count();
            $services = $services->filter(function ($service) use ($continuousGeneration) {
                $hasUnpaid = $this->hasUnpaidInvoiceForService($service);
                if ($hasUnpaid && ! $continuousGeneration) {
                    $this->info("  Service:{$service->uuid} - SKIPPED (has unpaid invoice)");

                    return false;
                }
                if ($hasUnpaid && $continuousGeneration) {
                    $this->info("  Service:{$service->uuid} - continuous mode, generating despite unpaid");
                }

                return true;
            });
            $this->info('After filter: '.$services->count().' services (skipped '.($beforeCount - $services->count()).')');

            if ($services->isEmpty()) {
                return;
            }

            $processed = 0;

            if ($generationMode === 'aggregated') {
                $processed = $this->generateAggregated($services, $invoiceDueDays, $daysBefore);
            } else {
                $processed = $this->generatePerService($services, $invoiceDueDays);
            }

            if ($processed > 0) {
                $this->info("Generated {$processed} invoices");
                logActivity('info', 'Finance:GenerateServiceInvoices completed. Generated: '.$processed.' invoices', 'cron');
            } else {
                $this->info('No invoices generated');
            }
        } finally {
            $lock->release();
        }
    }

    private function generatePerService($services, int $invoiceDueDays): int
    {
        $processed = 0;

        foreach ($services as $service) {
            try {
                $invoiceUuid = $this->createInvoiceForService($service, $invoiceDueDays);
                if ($invoiceUuid) {
                    $processed++;
                }
            } catch (\Throwable $e) {
                logActivity(
                    'error',
                    'Finance:GenerateServiceInvoices error for Service:'.$service->uuid.' - '.$e->getMessage(),
                    'cron',
                    null,
                    null,
                    null,
                    $service->client_uuid
                );
                $this->error('Service:'.$service->uuid.' error: '.$e->getMessage());
            }
        }

        return $processed;
    }

    private function generateAggregated($services, int $invoiceDueDays, int $daysBefore): int
    {
        $processed = 0;

        // Group by client
        $byClient = $services->groupBy('client_uuid');

        foreach ($byClient as $clientUuid => $clientServices) {
            try {
                // Find the earliest billing_timestamp to determine the target month
                $earliest = $clientServices->min('billing_timestamp');
                $targetMonth = Carbon::parse($earliest);
                $monthStart = $targetMonth->copy()->startOfMonth();
                $monthEnd = $targetMonth->copy()->endOfMonth();

                // Find ALL invoice-mode services for this client with billing_timestamp in the same month
                $allMonthServices = Service::query()
                    ->join('prices', 'services.price_uuid', '=', 'prices.uuid')
                    ->with(['product.productGroups', 'price'])
                    ->where('services.billing_mode', 'invoice')
                    ->where('services.client_uuid', $clientUuid)
                    ->where('services.termination_request', false)
                    ->whereIn('services.status', ['active', 'pending'])
                    ->where('prices.period', '!=', 'one-time')
                    ->whereNotNull('services.billing_timestamp')
                    ->whereBetween('services.billing_timestamp', [$monthStart, $monthEnd])
                    ->select('services.*')
                    ->get();

                // Filter already invoiced
                $allMonthServices = $allMonthServices->filter(function ($service) {
                    return ! $this->hasUnpaidInvoiceForService($service);
                });

                if ($allMonthServices->isEmpty()) {
                    continue;
                }

                $client = Client::find($clientUuid);
                if (! $client) {
                    continue;
                }

                $invoiceUuid = $this->createAggregatedInvoice($client, $allMonthServices, $invoiceDueDays);
                if ($invoiceUuid) {
                    $processed++;
                }
            } catch (\Throwable $e) {
                logActivity(
                    'error',
                    'Finance:GenerateServiceInvoices aggregated error for Client:'.$clientUuid.' - '.$e->getMessage(),
                    'cron',
                    null,
                    null,
                    null,
                    $clientUuid
                );
                $this->error('Client:'.$clientUuid.' error: '.$e->getMessage());
            }
        }

        return $processed;
    }

    private function createInvoiceForService(Service $service, int $invoiceDueDays): ?string
    {
        $invoiceUuid = DB::transaction(function () use ($service, $invoiceDueDays) {
            // Lock service row to prevent race conditions
            $locked = Service::where('uuid', $service->uuid)->lockForUpdate()->first();
            if (! $locked || $locked->billing_mode !== 'invoice') {
                $this->info("  Service:{$service->uuid} - skipped (billing_mode changed)");

                return null;
            }

            // Double-check for existing unpaid invoice under lock
            if ($this->hasUnpaidInvoiceForService($service)) {
                $this->info("  Service:{$service->uuid} - skipped (unpaid invoice found under lock)");

                return null;
            }

            $chargeData = $service->calculateCharge();
            if (! $chargeData) {
                $this->info("  Service:{$service->uuid} - skipped (no charge data)");

                return null;
            }

            $client = $service->client;
            $invoice = $this->buildInvoice($client);

            $this->addServiceItem($invoice, $service, $chargeData);

            // Set dates
            $invoice->issue_date = now();
            $invoice->due_date = now()->addDays($invoiceDueDays);
            $invoice->save();

            $this->info("  Service:{$service->uuid} - invoice created: {$invoice->uuid}");

            return $invoice->uuid;
        });

        return $this->publishInvoice($invoiceUuid);
    }

    private function createAggregatedInvoice(Client $client, $services, int $invoiceDueDays): ?string
    {
        $invoiceUuid = DB::transaction(function () use ($client, $services, $invoiceDueDays) {
            $invoice = $this->buildInvoice($client);
            $itemsAdded = 0;

            foreach ($services as $service) {
                // Lock service row
                $locked = Service::where('uuid', $service->uuid)->lockForUpdate()->first();
                if (! $locked || $locked->billing_mode !== 'invoice') {
                    continue;
                }

                // Double-check under lock
                if ($this->hasUnpaidInvoiceForService($service)) {
                    continue;
                }

                $chargeData = $service->calculateCharge();
                if (! $chargeData) {
                    continue;
                }

                $this->addServiceItem($invoice, $service, $chargeData);
                $itemsAdded++;
            }

            if ($itemsAdded === 0) {
                $invoice->delete();

                return null;
            }

            $invoice->issue_date = now();
            $invoice->due_date = now()->addDays($invoiceDueDays);
            $invoice->save();

            return $invoice->uuid;
        });

        return $this->publishInvoice($invoiceUuid);
    }

    private function buildInvoice(Client $client): Invoice
    {
        $taxes = $client->getTaxes();
        $homeCompany = $client->getHomeCompany();

        $invoice = new Invoice;
        $invoice->type = 'proforma';
        $invoice->status = 'draft';
        $invoice->billing_purpose = 'service_billing';
        $invoice->client_uuid = $client->uuid;
        $invoice->home_company_uuid = $homeCompany->uuid;

        $index = 1;
        foreach ($taxes as $tax) {
            if ($index > 3) {
                break;
            }
            $invoice->{"tax_$index"} = $tax['rate'];
            $invoice->{"tax_{$index}_name"} = $tax['name'];
            $index++;
        }

        $invoice->save();
        $invoice->refresh();

        return $invoice;
    }

    private function addServiceItem(Invoice $invoice, Service $service, array $chargeData): void
    {
        $priceTotal = $service->getPriceTotal();
        $chargeAmount = $service->idle ? $priceTotal['idle'] : $priceTotal['base'];
        $periodStart = Carbon::parse($service->billing_timestamp);
        $periodStop = $chargeData['to'];

        $productName = $service->product->name;
        if (empty($productName)) {
            $productName = $service->product->key;
        }
        $pricePeriod = $service->price->period ?? '';
        $productGroup = $service->product->productGroups()->first();
        $productGroupName = $productGroup ? $productGroup->name : '';
        $clientLabel = $service->admin_label ?? '';

        $descParts = [];
        if ($productGroupName) {
            $descParts[] = $productGroupName;
        }
        $descParts[] = $productName;
        $description = implode(' / ', $descParts);
        if ($clientLabel) {
            $description .= ' ('.$clientLabel.')';
        }
        $description .= PHP_EOL.'*-'.$periodStart->format('d.m.Y').' - '.$periodStop->format('d.m.Y');
        $description .= ' ('.$pricePeriod.')';

        $item = new InvoiceItem;
        $item->invoice_uuid = $invoice->uuid;
        $item->description = $description;
        $item->taxed = true;
        $item->service_uuid = $service->uuid;
        $item->period_start = $periodStart;
        $item->period_stop = $periodStop;
        $item->amount = abs($chargeAmount);
        $item->save();

        // Setup fee for first invoice (service not yet activated)
        if (is_null($service->activated_date) && $priceTotal['setup'] > 0) {
            $setupDesc = implode(' / ', array_filter([$productGroupName, $productName]));
            if ($clientLabel) {
                $setupDesc .= ' ('.$clientLabel.')';
            }
            $setupDesc .= PHP_EOL.'*-Setup Fee';

            $setupItem = new InvoiceItem;
            $setupItem->invoice_uuid = $invoice->uuid;
            $setupItem->description = $setupDesc;
            $setupItem->taxed = true;
            $setupItem->service_uuid = $service->uuid;
            $setupItem->amount = $priceTotal['setup'];
            $setupItem->save();
        }
    }

    private function publishInvoice(?string $invoiceUuid): ?string
    {
        if (! $invoiceUuid) {
            return null;
        }

        $invoice = Invoice::find($invoiceUuid);
        if (! $invoice || $invoice->status !== 'draft') {
            $this->error("Invoice:{$invoiceUuid} - not found or not draft, cannot publish");

            return null;
        }

        // Verify billing_purpose was saved correctly
        $dbPurpose = DB::table('invoices')->where('uuid', $invoiceUuid)->value('billing_purpose');
        if ($dbPurpose !== 'service_billing') {
            $this->error("Invoice:{$invoiceUuid} - billing_purpose in DB is '{$dbPurpose}' instead of 'service_billing'!");
            // Fix it
            DB::table('invoices')->where('uuid', $invoiceUuid)->update(['billing_purpose' => 'service_billing']);
            $invoice->refresh();
        }

        // Verify invoice_items have service_uuid set
        $itemsWithoutService = DB::table('invoice_items')
            ->where('invoice_uuid', $invoiceUuid)
            ->whereNull('service_uuid')
            ->count();
        if ($itemsWithoutService > 0) {
            $this->error("Invoice:{$invoiceUuid} - {$itemsWithoutService} items have NULL service_uuid!");
        }

        $result = $invoice->publish();
        if ($result['status'] === 'success') {
            $this->info("  Invoice:{$invoiceUuid} published as {$invoice->number}");
            app(HookService::class)->callHooks('ServiceInvoiceCreated', ['invoice' => $invoice]);

            return $invoiceUuid;
        }

        $this->error("Invoice:{$invoiceUuid} publish failed: ".json_encode($result));

        return $invoiceUuid; // Still return UUID, invoice exists as draft
    }

    /**
     * Check if a service already has an unpaid/draft service_billing invoice.
     * Always checks regardless of continuous_invoice_generation setting.
     */
    private function hasUnpaidInvoiceForService(Service $service): bool
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
            ->where('invoice_items.service_uuid', $service->uuid)
            ->whereIn('invoices.billing_purpose', ['service_billing', 'service_upgrade', 'service_downgrade', 'service_proration'])
            ->whereIn('invoices.status', ['draft', 'unpaid'])
            ->exists();
    }
}
