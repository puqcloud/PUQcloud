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

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class PuqPmLxcPresetClusterGroup extends Model
{
    protected $table = 'puq_pm_lxc_preset_cluster_groups';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'puq_pm_lxc_preset_uuid',
        'puq_pm_cluster_group_uuid',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmTags(): BelongsToMany
    {
        return $this->belongsToMany(PuqPmTag::class, 'puq_pm_lxc_preset_cluster_group_x_tag',
            'puq_pm_lxc_preset_cluster_uuid', 'puq_pm_tag_uuid')
            ->withPivot('type')
            ->withTimestamps();
    }

    public function getTagsByType(string $type): Collection
    {
        return $this->belongsToMany(
            PuqPmTag::class,
            'puq_pm_lxc_preset_cluster_group_x_tag',
            'puq_pm_lxc_preset_cluster_uuid',
            'puq_pm_tag_uuid'
        )
            ->withPivot('type') // node, rootfs_storage, additional_storage, backup_storage, public_network, local_private_network, global_private_network
            ->wherePivot('type', $type)
            ->get();
    }

    public function puqPmClusterGroup(): BelongsTo
    {
        return $this->belongsTo(PuqPmClusterGroup::class, 'puq_pm_cluster_group_uuid', 'uuid');
    }

    protected function getItemsByTagType(string $tagType, string $relationMethod): array
    {
        $items = [];

        $tags = $this->getTagsByType($tagType);
        if ($tags->isEmpty()) {
            return [];
        }

        $cluster_group = $this->puqPmClusterGroup;
        if (!$cluster_group) {
            return [];
        }

        $cluster_uuids = $cluster_group->puqPmClusters()->pluck('uuid')->toArray();
        if (empty($cluster_uuids)) {
            return [];
        }

        foreach ($tags as $tag) {
            $tag_items = $tag->{$relationMethod}()->whereIn('puq_pm_cluster_uuid', $cluster_uuids)->get();
            if ($tag_items->isEmpty()) {
                continue;
            }


            foreach ($tag_items as $item) {
                $items[$item->uuid] = $item;
            }
        }

        return array_values($items);
    }

    public function getNodes(): array
    {
        return $this->getItemsByTagType('node', 'puqPmNodes');
    }

    public function getRootfsStorages(): array
    {
        return $this->getItemsByTagType('rootfs_storage', 'puqPmStorages');
    }

    public function getAdditionalStorages(): array
    {
        return $this->getItemsByTagType('additional_storage', 'puqPmStorages');
    }

    public function getBackupStorages(): array
    {
        return $this->getItemsByTagType('backup_storage', 'puqPmStorages');
    }

    public function getPublicNetworks(): array
    {
        return $this->getItemsByTagType('public_network', 'puqPmPublicNetworks');
    }

    public function getLocalPrivateNetworks(): array
    {
        $local_private_network = [];
        $private_networks = $this->getItemsByTagType('local_private_network', 'puqPmPrivateNetworks');
        foreach ($private_networks as $private_network) {
            if ($private_network->type == 'local_private') {
                $local_private_network[] = $private_network;
            }
        }

        return $local_private_network;
    }

    public function getGlobalPrivateNetworks(): array
    {
        $global_private_network = [];
        $private_networks = $this->getItemsByTagType('global_private_network', 'puqPmPrivateNetworks');
        foreach ($private_networks as $private_network) {
            if ($private_network->type == 'global_private') {
                $global_private_network[] = $private_network;
            }
        }

        return $global_private_network;
    }

}
