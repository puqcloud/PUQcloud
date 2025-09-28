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

namespace Modules\Product\puqProxmox\Models;

use App\Models\Client;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PuqPmClientPrivateNetwork extends Model
{
    protected $table = 'puq_pm_client_private_networks';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'type', // local_private, global_private
        'client_uuid',
        'puq_pm_cluster_group_uuid',
        'bridge',
        'vlan_tag',
        'ipv4_network',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function puqPmClusterGroup(): BelongsTo
    {
        return $this->belongsTo(PuqPmClusterGroup::class, 'puq_pm_cluster_group_uuid', 'uuid');
    }

    public function getLocation(): string
    {
        $puq_pm_cluster_group = $this->puqPmClusterGroup;

        return $puq_pm_cluster_group->getLocation();
    }

    static function getNewClientGlobalPrivateNetwork(): ?array
    {
        $bridges = PuqPmPrivateNetwork::query()
            ->where('type', 'global_private')
            ->pluck('bridge')
            ->unique()
            ->values();

        foreach ($bridges as $bridge) {
            $used_vlan_tags = PuqPmClientPrivateNetwork::query()
                ->where('type', 'global_private')
                ->where('bridge', $bridge)
                ->pluck('vlan_tag')
                ->toArray();

            $vlan_tag = 10;
            while ($vlan_tag < 4090) {
                if (!in_array($vlan_tag, $used_vlan_tags)) {
                    return [
                        'bridge' => $bridge,
                        'vlan_tag' => $vlan_tag,
                    ];
                }
                $vlan_tag++;
            }
        }

        return null;
    }

    static function getNewClientLocalPrivateNetwork($puq_pm_cluster_group_uuid): ?array
    {

        $puq_pm_cluster_uuids = PuqPmCluster::query()
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster_group_uuid)->pluck('uuid')->toArray();

        $bridges = PuqPmPrivateNetwork::query()
            ->where('type', 'local_private')
            ->whereIn('puq_pm_cluster_uuid', $puq_pm_cluster_uuids)
            ->pluck('bridge')
            ->unique()
            ->values();

        foreach ($bridges as $bridge) {
            $used_vlan_tags = PuqPmClientPrivateNetwork::query()
                ->where('type', 'local_private')
                ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster_group_uuid)
                ->where('bridge', $bridge)
                ->pluck('vlan_tag')
                ->toArray();

            $vlan_tag = 10;
            while ($vlan_tag < 4090) {
                if (!in_array($vlan_tag, $used_vlan_tags)) {
                    return [
                        'bridge' => $bridge,
                        'vlan_tag' => $vlan_tag,
                    ];
                }
                $vlan_tag++;
            }
        }

        return null;
    }

    static function createLocalPrivateNetwork(
        string $client_uuid,
        string $puq_pm_cluster_group_uuid,
        string $bridge,
        int $vlan_tag
    ): ?PuqPmClientPrivateNetwork {
        if (!Client::query()->where('uuid', $client_uuid)->exists()) {
            return null;
        }
        $puq_pm_cluster_group = PuqPmClusterGroup::query()->where('uuid', $puq_pm_cluster_group_uuid)->first();

        if (!$puq_pm_cluster_group) {
            return null;
        }

        $puq_pm_cluster_uuids = PuqPmCluster::query()
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster_group_uuid)->pluck('uuid')->toArray();

        $bridges = PuqPmPrivateNetwork::query()
            ->where('type', 'local_private')
            ->whereIn('puq_pm_cluster_uuid', $puq_pm_cluster_uuids)
            ->pluck('bridge')
            ->unique()
            ->toArray();


        if (!in_array($bridge, $bridges)) {
            return null;
        }

        if (PuqPmClientPrivateNetwork::query()
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster_group_uuid)->where('type', 'local_private')
            ->where('bridge', $bridge)->where('vlan_tag', $vlan_tag)
            ->exists()) {
            return null;
        }

        $puq_pm_client_private_network = new PuqPmClientPrivateNetwork();
        $puq_pm_client_private_network->name = 'default_local';
        $puq_pm_client_private_network->type = 'local_private';
        $puq_pm_client_private_network->client_uuid = $client_uuid;
        $puq_pm_client_private_network->puq_pm_cluster_group_uuid = $puq_pm_cluster_group_uuid;
        $puq_pm_client_private_network->bridge = $bridge;
        $puq_pm_client_private_network->vlan_tag = $vlan_tag;
        $puq_pm_client_private_network->ipv4_network = $puq_pm_cluster_group->local_private_network;
        $puq_pm_client_private_network->save();
        $puq_pm_client_private_network->refresh();

        return $puq_pm_client_private_network;
    }


    static function createGlobalPrivateNetwork(
        string $client_uuid,
        string $bridge,
        int $vlan_tag
    ): ?PuqPmClientPrivateNetwork {
        if (!Client::query()->where('uuid', $client_uuid)->exists()) {
            return null;
        }
        $bridges = PuqPmPrivateNetwork::query()
            ->where('type', 'global_private')
            ->pluck('bridge')
            ->unique()
            ->toArray();

        if (!in_array($bridge, $bridges)) {
            return null;
        }

        if (PuqPmClientPrivateNetwork::query()
            ->where('type', 'global_private')
            ->where('bridge', $bridge)->where('vlan_tag', $vlan_tag)
            ->exists()) {
            return null;
        }

        $puq_pm_client_private_network = new PuqPmClientPrivateNetwork();
        $puq_pm_client_private_network->name = 'default_global';
        $puq_pm_client_private_network->type = 'global_private';
        $puq_pm_client_private_network->client_uuid = $client_uuid;
        $puq_pm_client_private_network->bridge = $bridge;
        $puq_pm_client_private_network->vlan_tag = $vlan_tag;
        $puq_pm_client_private_network->ipv4_network = SettingService::get('Product.puqProxmox.global_private_network');
        $puq_pm_client_private_network->save();
        $puq_pm_client_private_network->refresh();

        return $puq_pm_client_private_network;
    }

    public function getIPv4(): ?string
    {
        if (!$this->ipv4_network) {
            return null;
        }

        // Example: 192.168.0.0/24
        [$network, $cidr] = explode('/', $this->ipv4_network);
        $cidr = (int) $cidr;

        $netLong = ip2long($network);
        if ($netLong === false) {
            return null;
        }

        $mask = -1 << (32 - $cidr);
        $networkLong = $netLong & $mask;
        $broadcastLong = $networkLong + (~$mask & 0xFFFFFFFF);

        // Reserved: network + broadcast
        $firstHost = $networkLong + 1;
        $lastHost = $broadcastLong - 1;

        // Get used IPs
        $service_uuids = $this->client->services()->pluck('uuid')->toArray();

        $query = PuqPmLxcInstance::query()
            ->whereIn('service_uuid', $service_uuids);

        // if local make filter by cluster group
        if ($this->type == 'local_private') {
            $puq_pm_cluster_uuids = $this->puqPmClusterGroup->puqPmClusters()->pluck('uuid')->toArray();
            $query->whereIn('puq_pm_cluster_uuid', $puq_pm_cluster_uuids);
        }

        $puq_pm_lxc_instance_uuids = $query->pluck('uuid')->toArray();

        $usedIps = puqPmLxcInstanceNet::query()
            ->where('type', $this->type)
            ->whereIn('puq_pm_lxc_instance_uuid', $puq_pm_lxc_instance_uuids)
            ->pluck('ipv4')
            ->toArray();

        $used = array_flip($usedIps) ?? [];

        // Loop through available range
        for ($ipLong = $firstHost; $ipLong <= $lastHost; $ipLong++) {
            $ip = long2ip($ipLong);
            if (!isset($used[$ip])) {
                return $ip.'/'.$cidr; // first free IP
            }
        }

        return null; // no free IP
    }


}
