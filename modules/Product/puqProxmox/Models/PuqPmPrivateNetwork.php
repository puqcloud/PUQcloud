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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class PuqPmPrivateNetwork extends Model
{
    protected $table = 'puq_pm_private_networks';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'type',  //
        'puq_pm_cluster_uuid',
        'puq_pm_mac_pool_uuid',
        'bridge',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmCluster(): BelongsTo
    {
        return $this->belongsTo(puqPmCluster::class, 'puq_pm_cluster_uuid', 'uuid');
    }

    public function puqPmMacPool(): BelongsTo
    {
        return $this->belongsTo(puqPmMacPool::class, 'puq_pm_mac_pool_uuid', 'uuid');
    }

    public function puqPmTags(): BelongsToMany
    {
        return $this->belongsToMany(
            PuqPmTag::class,
            'puq_pm_private_network_x_tag',
            'puq_pm_private_network_uuid',
            'puq_pm_tag_uuid'
        );
    }

    public function hasAvailableVlanTag(): bool
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $query = PuqPmClientPrivateNetwork::query()
            ->where('type', $this->type)
            ->where('bridge', $this->bridge);

        if ($this->type === 'local_private') {
            $query->where('puq_pm_cluster_group_uuid', $puq_pm_cluster->uuid);
        }

        return $query->count() < 4094;
    }


    public function getLocalBridgeVlanTag($puq_pm_cluster_group_uuid): ?array
    {
        $used_vlan_tags = PuqPmClientPrivateNetwork::query()
            ->where('type', 'local_private')
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster_group_uuid)
            ->where('bridge', $this->bridge)
            ->pluck('vlan_tag')
            ->toArray();

        $vlan_tag = 10;
        while ($vlan_tag < 4090) {
            if (!in_array($vlan_tag, $used_vlan_tags)) {
                return [
                    'bridge' => $this->bridge,
                    'vlan_tag' => $vlan_tag,
                ];
            }
            $vlan_tag++;
        }
        return null;
    }

    public function getGlobalBridgeVlanTag(): ?array
    {
        $used_vlan_tags = PuqPmClientPrivateNetwork::query()
            ->where('type', 'global_private')
            ->where('bridge', $this->bridge)
            ->pluck('vlan_tag')
            ->toArray();

        $vlan_tag = 10;
        while ($vlan_tag < 4090) {
            if (!in_array($vlan_tag, $used_vlan_tags)) {
                return [
                    'bridge' => $this->bridge,
                    'vlan_tag' => $vlan_tag,
                ];
            }
            $vlan_tag++;
        }
        return null;
    }


}
