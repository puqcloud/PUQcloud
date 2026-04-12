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

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Services\HookService;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class TicketsSlaReminder extends Command
{
    protected $signature = 'Tickets:SlaReminder';
    protected $description = 'Send SLA breach reminders to department admins';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle(): void
    {
        $lock = Cache::lock('tickets:sla-reminder', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $sent = 0;

            Ticket::where('sla_reminder_sent', false)
                ->whereNotNull('last_reply_at')
                ->with(['department', 'status'])
                ->chunk(100, function ($tickets) use (&$sent) {
                    foreach ($tickets as $ticket) {
                        try {
                            $slaHours = $ticket->department?->sla_response_hours;

                            if (! $slaHours || ! $ticket->status?->include_in_awaiting_reply) {
                                continue;
                            }

                            $deadline = $ticket->last_reply_at->copy()->addHours($slaHours);

                            if (now()->lt($deadline)) {
                                continue;
                            }

                            app(HookService::class)->callHooks('TicketSlaBreached', ['ticket' => $ticket]);

                            $ticket->update(['sla_reminder_sent' => true]);

                            $sent++;
                        } catch (\Throwable $e) {
                            logActivity(
                                'error',
                                'Tickets:SlaReminder: failed for ticket #'.$ticket->number.' - '.$e->getMessage(),
                                'cron',
                                null,
                                null,
                                null,
                                $ticket->client_uuid
                            );
                        }
                    }
                });

            if ($sent > 0) {
                logActivity('info', 'Tickets:SlaReminder completed. Sent '.$sent.' SLA breach reminders', 'cron');
            }
        } finally {
            $lock->release();
        }
    }
}
