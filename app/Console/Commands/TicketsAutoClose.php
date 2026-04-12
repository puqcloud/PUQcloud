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
use App\Models\TicketReply;
use App\Models\TicketStatus;
use App\Services\HookService;
use App\Services\SettingService;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class TicketsAutoClose extends Command
{
    protected $signature = 'Tickets:AutoClose';
    protected $description = 'Auto-close tickets based on department and global auto-close settings';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle(): void
    {
        $lock = Cache::lock('tickets:auto-close', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $globalAutoCloseHours = (int) SettingService::get('support.ticket_auto_close_hours');

            $closedStatus = TicketStatus::where('title', 'Closed')->first();
            $answeredStatus = TicketStatus::where('title', 'Answered')->first();

            if (! $closedStatus || ! $answeredStatus) {
                logActivity(
                    'error',
                    'Tickets:AutoClose: "Closed" or "Answered" ticket status not found. Skipping.',
                    'cron'
                );

                return;
            }

            $closed = 0;

            Ticket::where('status_uuid', $answeredStatus->uuid)
                ->whereNotNull('last_reply_at')
                ->with('department')
                ->chunk(100, function ($tickets) use ($globalAutoCloseHours, $closedStatus, &$closed) {
                    foreach ($tickets as $ticket) {
                        try {
                            $effectiveHours = $ticket->department?->auto_close_hours ?? $globalAutoCloseHours;

                            if ($effectiveHours <= 0) {
                                continue;
                            }

                            $threshold = $ticket->last_reply_at->copy()->addHours($effectiveHours);

                            if (now()->lt($threshold)) {
                                continue;
                            }

                            $ticket->update([
                                'status_uuid' => $closedStatus->uuid,
                                'closed_at' => now(),
                            ]);

                            TicketReply::create([
                                'ticket_uuid' => $ticket->uuid,
                                'message' => 'Ticket automatically closed due to inactivity.',
                                'is_admin_note' => true,
                            ]);

                            app(HookService::class)->callHooks('TicketClosed', ['ticket' => $ticket]);

                            $closed++;
                        } catch (\Throwable $e) {
                            logActivity(
                                'error',
                                'Tickets:AutoClose: failed to close ticket #'.$ticket->number.' - '.$e->getMessage(),
                                'cron',
                                null,
                                null,
                                null,
                                $ticket->client_uuid
                            );
                        }
                    }
                });

            if ($closed > 0) {
                logActivity('info', 'Tickets:AutoClose completed. Auto-closed '.$closed.' tickets', 'cron');
            }
        } finally {
            $lock->release();
        }
    }
}
