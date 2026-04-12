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

use App\Models\Invoice;
use App\Models\Task;
use App\Services\NotificationService;
use App\Services\SettingService;
use App\Services\TranslationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class FinanceInvoiceReminders extends Command
{
    protected $signature = 'Finance:InvoiceReminders';
    protected $description = 'Sends payment reminders and overdue notices for unpaid service billing invoices';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $lock = Cache::lock('billing:invoice-reminders', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $reminderDays = (int) SettingService::get('finance.invoice_unpaid_reminder_days');
            $overdue1Days = (int) SettingService::get('finance.invoice_first_overdue_days');
            $overdue2Days = (int) SettingService::get('finance.invoice_second_overdue_days');
            $overdue3Days = (int) SettingService::get('finance.invoice_third_overdue_days');

            $processed = 0;

            Invoice::query()
                ->whereIn('billing_purpose', ['service_billing', 'standalone'])
                ->where('status', 'unpaid')
                ->chunk(100, function ($invoices) use ($reminderDays, $overdue1Days, $overdue2Days, $overdue3Days, &$processed) {
                    foreach ($invoices as $invoice) {
                        $now = now();
                        $dueDate = Carbon::parse($invoice->due_date);
                        $sent = false;

                        // Payment reminder (before due_date)
                        if ($reminderDays > 0
                            && is_null($invoice->reminder_sent_at)
                            && $now->gte($dueDate->copy()->subDays($reminderDays))
                            && $now->lt($dueDate)
                        ) {
                            $this->sendClientNotification($invoice, 'Client Invoice Payment Reminder');
                            $invoice->reminder_sent_at = $now;
                            $sent = true;
                        }

                        // First overdue (after due_date)
                        if ($overdue1Days > 0
                            && is_null($invoice->overdue_1_sent_at)
                            && $now->gte($dueDate->copy()->addDays($overdue1Days))
                        ) {
                            $this->sendClientNotification($invoice, 'Client Invoice First Overdue Notice');
                            $this->sendAdminNotification($invoice, 'Admin Invoice Overdue Notification');
                            $invoice->overdue_1_sent_at = $now;
                            $sent = true;
                        }

                        // Second overdue
                        if ($overdue2Days > 0
                            && is_null($invoice->overdue_2_sent_at)
                            && $now->gte($dueDate->copy()->addDays($overdue2Days))
                        ) {
                            $this->sendClientNotification($invoice, 'Client Invoice Second Overdue Notice');
                            $invoice->overdue_2_sent_at = $now;
                            $sent = true;
                        }

                        // Third overdue (final)
                        if ($overdue3Days > 0
                            && is_null($invoice->overdue_3_sent_at)
                            && $now->gte($dueDate->copy()->addDays($overdue3Days))
                        ) {
                            $this->sendClientNotification($invoice, 'Client Invoice Third Overdue Notice');
                            $invoice->overdue_3_sent_at = $now;
                            $sent = true;
                        }

                        if ($sent) {
                            $invoice->save();
                            $processed++;
                        }
                    }
                });

            if ($processed > 0) {
                logActivity('info', 'Finance:InvoiceReminders completed. Processed: '.$processed.' invoices', 'cron');
            }
        } finally {
            $lock->release();
        }
    }

    private function sendClientNotification(Invoice $invoice, string $notificationName): void
    {
        try {
            $client = $invoice->client;
            if (! $client) {
                return;
            }

            Task::add('ClientNotificationJob', 'ClientNotification', [
                'client_uuid' => $client->uuid,
                'category' => 'clientOperational',
                'notification' => $notificationName,
                'data' => ['invoice' => $invoice],
            ], ['ClientNotification']);
        } catch (\Throwable $e) {
            logActivity(
                'error',
                'InvoiceReminders: failed to send "'.$notificationName.'" for Invoice:'.$invoice->uuid.' - '.$e->getMessage(),
                'cron',
                null,
                null,
                null,
                $invoice->client_uuid
            );
        }
    }

    private function sendAdminNotification(Invoice $invoice, string $notificationName): void
    {
        try {
            Task::add('AllAdminNotificationJob', 'AdminNotification', [
                'category' => 'staffOperational',
                'notification' => $notificationName,
                'data' => ['invoice' => $invoice],
            ], ['AllAdminNotification']);
        } catch (\Throwable $e) {
            logActivity(
                'error',
                'InvoiceReminders: failed to send admin "'.$notificationName.'" for Invoice:'.$invoice->uuid.' - '.$e->getMessage(),
                'cron',
                null,
                null,
                null,
                $invoice->client_uuid
            );
        }
    }
}
