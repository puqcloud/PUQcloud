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

namespace Modules\Product\puqVPNCP\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\Product\puqVPNCP\Models\PuqVpncpServer;

class SyncServers extends Command
{
    protected $signature = 'puqVPNCP:SyncServers';
    protected $description = 'Sync puqVPNCP servers status, license and networks data';

    public function handle(): int
    {
        $lock = Cache::lock('puqvpncp:sync-servers', 250);
        if (! $lock->get()) {
            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;
        $failedServers = [];

        try {
            $servers = PuqVpncpServer::all();

            foreach ($servers as $server) {
                try {
                    $vpncp = $server->getApiClient();

                    $systemStatus = $vpncp->getSystemStatus();
                    $license = $vpncp->getLicense();
                    $networks = $vpncp->getNetworks();

                    // Treat an explicit API error as a sync failure, not just thrown exceptions.
                    if (
                        ($systemStatus['status'] ?? 'error') !== 'success'
                        || ($license['status'] ?? 'error') !== 'success'
                        || ($networks['status'] ?? 'error') !== 'success'
                    ) {
                        $failed++;
                        $failedServers[] = $server->name ?? $server->uuid;
                        logModule(
                            'Product',
                            'puqVPNCP',
                            'SyncServers',
                            'error',
                            ['server' => $server->uuid],
                            'API returned error: '.($systemStatus['error'] ?? $license['error'] ?? $networks['error'] ?? 'unknown')
                        );
                        continue;
                    }

                    $getData = fn ($response) => ($response['status'] === 'success' && is_array($response['data'] ?? null)) ? $response['data'] : [];

                    $networksData = $getData($networks);

                    // Aggregate protocol availability from networks
                    $protocols = ['wireguard' => false, 'openvpn' => false, 'ikev2' => false];
                    foreach ($networksData as $net) {
                        if (is_array($net) && isset($net['protocols'])) {
                            foreach ($protocols as $proto => $val) {
                                if (! empty($net['protocols'][$proto])) {
                                    $protocols[$proto] = true;
                                }
                            }
                        }
                    }

                    $server->description = [
                        'system' => $getData($systemStatus),
                        'license' => self::sanitizeLicenseData($getData($license)),
                        'networks' => $networksData,
                        'protocols' => $protocols,
                        'synced_at' => now()->toIso8601String(),
                    ];
                    $server->save();
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $failedServers[] = $server->name ?? $server->uuid;
                    logModule('Product', 'puqVPNCP', 'SyncServers', 'error', ['server' => $server->uuid], $e->getMessage());
                }
            }

            if ($processed > 0) {
                logActivity('info', 'puqVPNCP:SyncServers completed. Synced: '.$processed.' servers', 'cron');
            }

            if ($failed > 0) {
                logActivity(
                    'error',
                    'puqVPNCP:SyncServers had '.$failed.' failure(s): '.implode(', ', $failedServers),
                    'cron'
                );
            }
        } finally {
            $lock->release();
        }

        // Non-zero exit if any server failed so operators/monitoring can detect the problem.
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private static function sanitizeLicenseData(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        unset($data['key']);

        return $data;
    }
}
