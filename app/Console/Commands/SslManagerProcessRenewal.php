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

use App\Models\SslCertificate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SslManagerProcessRenewal extends Command
{
    protected $signature = 'SslManager:ProcessRenewal';
    protected $description = 'Process all active certificates for renewal if needed';

    // =============================
    // Timing settings (minutes/seconds)
    // =============================
    private $commandLockSeconds = 600;       // Max time the whole command is locked (10 minutes)
    private $retryDelayIncrement = 1;        // Increment retry delay after each failed renewal (1 minute)
    private $retryDelayMax = 60;             // Maximum retry delay (1 hour)
    private $retryDelayCacheLifetime = 1440; // Cache lifetime for retry delay tracking (in minutes, 1 day)

    public function handle()
    {
        $lock = Cache::lock('ssl_manager_process_renewal', $this->commandLockSeconds);

        if (!$lock->get()) {
            $this->info('Another renewal process is already running. Exiting.');
            return;
        }

        try {
            $certificates = SslCertificate::query()
                ->where('status', 'active')
                ->where('auto_renew_days', '>', 0)
                ->where(function ($query) {
                    $query->whereNotNull('expires_at')
                        ->whereRaw('DATEDIFF(expires_at, NOW()) <= auto_renew_days');
                })
                ->get();

            if ($certificates->isEmpty()) {
                $this->info('No certificates found.');

                return;
            }

            foreach ($certificates as $certificate) {
                $certificate->refresh();
                if ($certificate->status != 'active') {
                    continue;
                }

                if (!$certificate->needsRenewal()) {
                    continue;
                }

                // Check retry delay
                $cacheKey = 'ssl_retry_'.$certificate->uuid;
                if (Cache::has($cacheKey)) {
                    $this->line("🔹 Skipping {$certificate->domain}, retry delay active.");
                    continue;
                }

                $this->line('');
                $this->line("🔹 Processing certificate UUID: <fg=yellow>{$certificate->uuid}</>");
                $this->line("🔹 Domain: <fg=green>{$certificate->domain}</>");
                if (!empty($certificate->aliases) && is_array($certificate->aliases)) {
                    $this->line("   Aliases:");
                    foreach ($certificate->aliases as $alias) {
                        $this->line("     • $alias");
                    }
                }

                try {

                    $renewal = $certificate->renewal();

                    if ($renewal['status'] === 'success') {
                        $this->line("<fg=green>✅ Renewal check passed or renewed successfully.</>");
                        Cache::forget($cacheKey);
                        Cache::forget($cacheKey.'_delay');
                    } else {
                        $this->line("<fg=red>❌ Renewal error:</>");
                        if (isset($renewal['errors']) && is_array($renewal['errors'])) {
                            foreach ($renewal['errors'] as $error) {
                                $this->line("   - $error");
                            }
                        } elseif (isset($renewal['error'])) {
                            $this->line("   - {$renewal['error']}");
                        }

                        if (isset($renewal['code'])) {
                            $this->line("   (Error code: {$renewal['code']})");
                        }

                        // Dynamic retry delay
                        $currentDelay = Cache::get($cacheKey.'_delay', $this->retryDelayIncrement);
                        $newDelay = min($currentDelay + $this->retryDelayIncrement, $this->retryDelayMax);
                        Cache::put($cacheKey, true, $newDelay * 60);
                        Cache::put($cacheKey.'_delay', $newDelay, $this->retryDelayCacheLifetime * 60);
                    }
                } catch (\Throwable $e) {
                    $this->line("<fg=red>❌ Exception:</> {$e->getMessage()}");

                    // Retry delay on exception
                    $currentDelay = Cache::get($cacheKey.'_delay', $this->retryDelayIncrement);
                    $newDelay = min($currentDelay + $this->retryDelayIncrement, $this->retryDelayMax);
                    Cache::put($cacheKey, true, $newDelay * 60);
                    Cache::put($cacheKey.'_delay', $newDelay, $this->retryDelayCacheLifetime * 60);
                }

                $this->line(str_repeat('-', 50));
            }
        } finally {
            $lock->release();
        }
    }
}
