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

namespace Modules\Product\puqVPNCP\Models;

use App\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PuqVpncpServerGroup extends Model
{
    protected $table = 'puq_vpncp_server_groups';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'fill_type',
        'country_uuid',
        'region_uuid',
        'data_center',
        'description',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(
            PuqVpncpServer::class,
            'puq_vpncp_server_group_server',
            'server_group_uuid',
            'server_uuid',
            'uuid',
            'uuid'
        )->withPivot('selected_networks')->withTimestamps();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Country::class, 'country_uuid', 'uuid');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Region::class, 'region_uuid', 'uuid');
    }

    /**
     * Find a suitable server and network for deploying a new client.
     * Respects fill_type: fill_first (default), least_used, round_robin.
     *
     * @return array|null ['server' => PuqVpncpServer, 'network' => string] or null
     */
    public function getServerAndNetwork(): ?array
    {
        $pivotRows = DB::table('puq_vpncp_server_group_server')
            ->where('server_group_uuid', $this->uuid)
            ->get();

        if ($pivotRows->isEmpty()) {
            return null;
        }

        $serverUuids = $pivotRows->pluck('server_uuid')->toArray();
        $servers = PuqVpncpServer::whereIn('uuid', $serverUuids)->get()->keyBy('uuid');

        // Build candidates: only servers with free slots and networks
        $candidates = [];
        foreach ($pivotRows as $pivot) {
            $server = $servers->get($pivot->server_uuid);
            if (! $server) {
                continue;
            }

            if ($server->getFreeSlots() <= 0) {
                continue;
            }

            $selectedNetworks = json_decode($pivot->selected_networks, true) ?? [];
            if (empty($selectedNetworks)) {
                continue;
            }

            $candidates[] = [
                'server' => $server,
                'networks' => $selectedNetworks,
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        // Sort candidates based on fill_type.
        // 'default' and 'fill_first' both mean fill-first (keep pivot order).
        // 'lowest' is a legacy alias for 'least_used' — kept so existing rows keep working.
        $fillType = $this->fill_type ?? 'default';
        switch ($fillType) {
            case 'least_used':
            case 'lowest':
                usort($candidates, fn ($a, $b) => $a['server']->getActiveClientsCount() <=> $b['server']->getActiveClientsCount());
                break;
            case 'round_robin':
                shuffle($candidates);
                break;
            case 'default':
            case 'fill_first':
            default:
                // Fill first — keep original pivot order.
                break;
        }

        // Check each candidate's networks for available IPs
        foreach ($candidates as $candidate) {
            try {
                $vpncp = $candidate['server']->getApiClient();
                foreach ($candidate['networks'] as $networkName) {
                    $availableIPs = $vpncp->getAvailableIPs($networkName);
                    if ($availableIPs['status'] === 'success' && ! empty($availableIPs['data'])) {
                        return [
                            'server' => $candidate['server'],
                            'network' => $networkName,
                        ];
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    public function getLocation(): string
    {
        $country = $this->country;
        if (! $country || ! $country->code) {
            return '';
        }

        return mb_strtolower($country->code.'-'.($this->data_center ?? ''));
    }

    public static function getByLocation(string $location): ?PuqVpncpServerGroup
    {
        $location = trim($location);
        $dashPos = strpos($location, '-');

        if ($dashPos === false) {
            return null;
        }

        $countryCode = substr($location, 0, $dashPos);
        $dataCenter = substr($location, $dashPos + 1);

        if (empty($countryCode) || empty($dataCenter)) {
            return null;
        }

        $country = Country::query()->whereRaw('LOWER(code) = ?', [mb_strtolower($countryCode)])->first();
        if (! $country) {
            return null;
        }

        return PuqVpncpServerGroup::query()
            ->where('country_uuid', $country->uuid)
            ->whereRaw('LOWER(data_center) = ?', [mb_strtolower($dataCenter)])
            ->first();
    }

    public function hasServers(): bool
    {
        return $this->servers()->exists();
    }
}
