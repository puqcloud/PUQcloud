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

class SslManagerProcessPending extends Command
{
    protected $signature = 'SslManager:ProcessPending';
    protected $description = 'Process all pending certificates and start issuance';

    // =============================
    // Timing settings (minutes/seconds)
    // =============================
    private $commandLockSeconds = 600;         // Max time the whole command is locked (10 minutes)
    private $retryDelayIncrement = 1;          // Increment retry delay after each failure (1 minute)
    private $retryDelayMax = 10;               // Maximum retry delay (1 hour)
    private $retryDelayCacheLifetime = 1440;   // Cache lifetime for retry delay tracking (in minutes, 1 day)

    public function handle()
    {
        $lock = Cache::lock('ssl_manager_process_pending', $this->commandLockSeconds);

        if (!$lock->get()) {
            $this->info('Another SslManager process is already running. Exiting.');

            return;
        }

        try {
            $certificates = SslCertificate::query()
                ->where('status', 'pending')
                ->get();

            if ($certificates->isEmpty()) {
                $this->info('No pending certificates found.');

                return;
            }

            foreach ($certificates as $certificate) {
                $certificate->refresh();
                if ($certificate->status != 'pending') {
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

                    $issuance = $certificate->issuance();

                    if ($issuance['status'] === 'success') {
                        $this->line("<fg=green>✅ Success:</> Certificate issued successfully.");
                        Cache::forget($cacheKey);
                        Cache::forget($cacheKey.'_delay');
                    } else {
                        $this->line("<fg=red>❌ Error:</>");
                        if (isset($issuance['errors']) && is_array($issuance['errors'])) {
                            foreach ($issuance['errors'] as $error) {
                                $this->line("   - $error");
                            }
                        } elseif (isset($issuance['error'])) {
                            $this->line("   - {$issuance['error']}");
                        }

                        if (isset($issuance['code'])) {
                            $this->line("   (Error code: {$issuance['code']})");
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
