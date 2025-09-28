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

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PuqPmClusterGroup extends Model
{
    protected $table = 'puq_pm_cluster_groups';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'fill_type',
        'country_uuid',
        'region_uuid',
        'data_center',
        'local_private_network',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmClusters(): HasMany
    {
        return $this->hasMany(puqPmCluster::class, 'puq_pm_cluster_group_uuid', 'uuid');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_uuid', 'uuid');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_uuid', 'uuid');
    }

    public function puqPmLxcPresetClusterGroups(): HasMany
    {
        return $this->hasMany(PuqPmLxcPresetClusterGroup::class, 'puq_pm_cluster_group_uuid', 'uuid');
    }

    public function getLocation(): string
    {
        $country = $this->country;

        return mb_strtolower($country->code.'-'.$this->data_center);
    }

    public function getLocalPrivateNetworks(): Collection
    {
        $private_networks = collect();

        $clusters = $this->puqPmClusters;
        foreach ($clusters as $cluster) {
            $private_networks = $private_networks->merge($cluster->getLocalPrivateNetworks());
        }

        return $private_networks;
    }

    public function getGlobalPrivateNetworks(): Collection
    {
        $private_networks = collect();

        $clusters = $this->puqPmClusters;
        foreach ($clusters as $cluster) {
            $private_networks = $private_networks->merge($cluster->getGlobalPrivateNetworks());
        }

        return $private_networks;
    }

    public function getNewClientLocalPrivateNetwork(): ?array
    {
        $bridges = $this->getLocalPrivateNetworks()
            ->pluck('bridge')
            ->unique()
            ->values();

        foreach ($bridges as $bridge) {
            $used_vlan_tags = PuqPmClientPrivateNetwork::query()
                ->where('type', 'local_private')
                ->where('puq_pm_cluster_group_uuid', $this->uuid)
                ->where('bridge', $bridge)
                ->pluck('vlan_tag')
                ->toArray();

            $vlan_tag = 1;
            while ($vlan_tag < 4096) {
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

    public static function getByLocation(string $location): ?PuqPmClusterGroup
    {
        $location = mb_strtolower($location);
        $parts = array_filter(explode('-', $location));

        if (empty($parts[0]) || empty($parts[1])) {
            return null;
        }

        $country = Country::query()->where('code', $parts[0])->first();
        if (!$country) {
            return null;
        }

        return PuqPmClusterGroup::query()
            ->where('country_uuid', $country->uuid)
            ->whereRaw('LOWER(data_center) = ?', [$parts[1]])
            ->first();
    }

    public function getClusterPriorityUuids(): array
    {
        $clusters = $this->puqPmClusters()->where('disable', false)->get();

        if ($clusters->isEmpty()) {
            return [];
        }

        if ($this->fill_type === 'default') {
            $sorted = $clusters
                ->sortByDesc(fn($cluster) => $cluster->default ? 1 : 0)
                ->sortByDesc(fn($cluster) => $cluster->getUseAccounts());
        } elseif ($this->fill_type === 'lowest') {
            $sorted = $clusters->sortBy(fn($cluster) => $cluster->getUseAccounts());
        } else {
            return [];
        }

        return $sorted->pluck('uuid')->values()->toArray();

    }

    public function getNodesByTags(array $tags): Collection
    {
        $puq_pm_nodes = collect();
        $puq_pm_cluster_uuids = $this->getClusterPriorityUuids();

        foreach ($puq_pm_cluster_uuids as $puq_pm_cluster_uuid) {
            $puq_pm_cluster = $this->puqPmClusters()->where('uuid', $puq_pm_cluster_uuid)->first();
            if (!$puq_pm_cluster) {
                continue;
            }
            $puq_pm_nodes = $puq_pm_nodes->merge(
                $puq_pm_cluster->getNodesByTags($tags)
            );
        }

        return $puq_pm_nodes;
    }

    public function getStoragesByTags(array $tags): Collection
    {
        $puq_pm_storages = collect();
        $puq_pm_cluster_uuids = $this->getClusterPriorityUuids();

        foreach ($puq_pm_cluster_uuids as $puq_pm_cluster_uuid) {
            $puq_pm_cluster = $this->puqPmClusters()->where('uuid', $puq_pm_cluster_uuid)->first();
            if (!$puq_pm_cluster) {
                continue;
            }
            $puq_pm_storages = $puq_pm_storages->merge(
                $puq_pm_cluster->getStoragesByTags($tags)
            );
        }

        return $puq_pm_storages;
    }

    public function getPublicNetworksByTags(array $tags): Collection
    {
        $puq_pm_networks = collect();
        $puq_pm_cluster_uuids = $this->getClusterPriorityUuids();

        foreach ($puq_pm_cluster_uuids as $puq_pm_cluster_uuid) {
            $puq_pm_cluster = $this->puqPmClusters()->where('uuid', $puq_pm_cluster_uuid)->first();
            if (!$puq_pm_cluster) {
                continue;
            }
            $puq_pm_networks = $puq_pm_networks->merge(
                $puq_pm_cluster->getPublicNetworksByTags($tags)
            );
        }

        return $puq_pm_networks;
    }

    public function setDataCenterAttribute($value): void
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\-_.]+/u', '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        $value = trim($value, '-');
        $this->attributes['data_center'] = $value;
    }

    public function getAvailableResources(array $needs, array $tags): array
    {

        $memory = $needs['memory'];
        $rootfs_size = $needs['rootfs_size'] * 1024;
        $mp_size = $needs['mp_size'] * 1024;
        $backup_count = $needs['backup_count'];
        $ipv4_public_network = $needs['ipv4_public_network'];
        $ipv6_public_network = $needs['ipv6_public_network'];
        $local_private_network = $needs['local_private_network'];
        $global_private_network = $needs['global_private_network'];


        $node_tags = $tags['node']?->pluck('name')->toArray() ?? [];
        $puq_pm_nodes = $this->getNodesByTags($node_tags);
        $puq_pm_node_model = null;

        $rootfs_storage_tags = $tags['rootfs_storage']?->pluck('name')->toArray() ?? [];
        $rootfs_storages = $this->getStoragesByTags($rootfs_storage_tags);
        $rootfs_storage_model = null;

        $additional_storage_tags = $tags['additional_storage']?->pluck('name')->toArray() ?? [];
        $additional_storages = $this->getStoragesByTags($additional_storage_tags);
        $additional_storage_model = null;

        $backup_storage_tags = $tags['backup_storage']?->pluck('name')->toArray() ?? [];
        $backup_storages = $this->getStoragesByTags($backup_storage_tags);
        $backup_storage_model = null;

        $public_network_tags = $tags['public_network']?->pluck('name')->toArray() ?? [];
        $puq_pm_public_networks = $this->getPublicNetworksByTags($public_network_tags);
        $ipv4_public_network_model = null;
        $ipv6_public_network_model = null;


        $local_private_networks = $this->getLocalPrivateNetworks();
        $local_private_network_model = null;

        $global_private_networks = $this->getGlobalPrivateNetworks();
        $global_private_network_model = null;


        foreach ($puq_pm_nodes as $puq_pm_node) {

            // Get node uuid -------------------------------------------------------------------------------
            $puq_pm_node_model = null;
            if ($puq_pm_node->status !== 'online') {
                continue;
            }
            $free_mem = $puq_pm_node->maxmem - $puq_pm_node->mem;
            if ($free_mem < $memory) {
                continue;
            }
            $puq_pm_node_model = $puq_pm_node;

            // Get rootfs storage uuid ---------------------------------------------------------------------
            $rootfs_storage_model = $this->getAvailableStorage($puq_pm_node, $rootfs_storages, $rootfs_size);
            if (!$rootfs_storage_model) {
                continue;
            }

            // Get additional storage (only if needed) -----------------------------------------------------
            $additional_storage_model = null;
            if ($mp_size > 0) {
                $additional_storage_model = $this->getAvailableStorage($puq_pm_node, $additional_storages, $mp_size);
                if (!$additional_storage_model) {
                    continue;
                }
            }

            // backup storage (only if needed)
            $backup_storage_model = null;
            if ($backup_count > 0) {
                $backup_storage_model = $this->getAvailableStorage($puq_pm_node, $backup_storages);
                if (!$backup_storage_model) {
                    continue;
                }
            }

            // Get Public networks IPv4 and IPv6
            $ipv4_public_network_model = $ipv4_public_network
                ? $this->getAvailablePublicNetwork($puq_pm_node, $puq_pm_public_networks, 'ipv4')
                : null;

            if ($ipv4_public_network && !$ipv4_public_network_model) {
                continue;
            }

            $ipv6_public_network_model = $ipv6_public_network
                ? $this->getAvailablePublicNetwork($puq_pm_node, $puq_pm_public_networks, 'ipv6')
                : null;

            if ($ipv6_public_network && !$ipv6_public_network_model) {
                continue;
            }


            // Get Private networks Local and Global
            if ($local_private_network) {
                $local_private_network_model = $this->getAvailableLocalPrivateNetwork($puq_pm_node, $local_private_networks);
                if (!$local_private_network_model) {
                    continue;
                }
            }

            if ($global_private_network) {
                $global_private_network_model = $this->getAvailableGlobalPrivateNetwork($puq_pm_node, $global_private_networks);
                if (!$global_private_network_model) {
                    continue;
                }
            }

            return [
                'puq_pm_node' => $puq_pm_node_model,
                'rootfs_puq_pm_storage' => $rootfs_storage_model,
                'mp_puq_pm_storage' => $additional_storage_model,
                'backup_puq_pm_storage' => $backup_storage_model,
                'ipv4_public_network' => $ipv4_public_network_model,
                'ipv6_public_network' => $ipv6_public_network_model,
                'local_private_network' => $local_private_network_model,
                'global_private_network' => $global_private_network_model,
            ];
        }

        return [
            'puq_pm_node' => $puq_pm_node_model,
            'rootfs_puq_pm_storage' => $rootfs_storage_model,
            'mp_puq_pm_storage' => $additional_storage_model,
            'backup_puq_pm_storage' => $backup_storage_model,
            'ipv4_public_network' => $ipv4_public_network_model,
            'ipv6_public_network' => $ipv6_public_network_model,
            'local_private_network' => $local_private_network_model,
            'global_private_network' => $global_private_network_model,
        ];
    }

    public function getAvailableStorage($puq_pm_node, $storages, $min_size = 0)
    {
        foreach ($storages as $storage) {
            if ($storage->status !== 'available') {
                continue;
            }
            if ($storage->puq_pm_node_uuid != $puq_pm_node->uuid) {
                continue;
            }
            $free_space = $storage->maxdisk - $storage->disk;
            if ($free_space >= $min_size) {
                return $storage;
            }
        }
        return null;
    }

    public function getAvailableLocalPrivateNetwork($puq_pm_node, $local_private_networks)
    {
        foreach ($local_private_networks as $network) {
            if ($network->puq_pm_cluster_uuid != $puq_pm_node->puq_pm_cluster_uuid) {
                continue;
            }

            if (!$network->hasAvailableVlanTag()) {
                continue;
            }

            $puq_pm_mac_pool = $network->puqPmMacPool;
            if (!$puq_pm_mac_pool->hasAvailableMac()) {
                continue;
            }
            return $network;
        }

        return null;
    }

    public function getAvailableGlobalPrivateNetwork($puq_pm_node, $global_private_networks)
    {
        foreach ($global_private_networks as $network) {
            if ($network->puq_pm_cluster_uuid != $puq_pm_node->puq_pm_cluster_uuid) {
                continue;
            }

            if (!$network->hasAvailableVlanTag()) {
                continue;
            }

            $puq_pm_mac_pool = $network->puqPmMacPool;
            if (!$puq_pm_mac_pool->hasAvailableMac()) {
                continue;
            }
            return $network;
        }

        return null;
    }

    public function getAvailablePublicNetwork($puq_pm_node, $public_networks, string $type)
    {
        foreach ($public_networks as $network) {
            if ($network->puq_pm_cluster_uuid != $puq_pm_node->puq_pm_cluster_uuid) {
                continue;
            }

            $ip_pool = $network->puqPmIpPool;
            $mac_pool = $network->puqPmMacPool;

            if (!$ip_pool || !$mac_pool) {
                continue;
            }

            if ($ip_pool->type === $type && $ip_pool->hasAvailableIp() && $mac_pool->hasAvailableMac()) {
                return $network;
            }
        }
        return null;
    }
}
