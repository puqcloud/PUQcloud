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

class PuqPmPublicNetwork extends Model
{
    protected $table = 'puq_pm_public_networks';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'puq_pm_cluster_uuid',
        'puq_pm_mac_pool_uuid',
        'puq_pm_ip_pool_uuid',
        'bridge',
        'vlan_tag',
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

    public function puqPmIpPool(): BelongsTo
    {
        return $this->belongsTo(puqPmIpPool::class, 'puq_pm_ip_pool_uuid', 'uuid');
    }

    public function puqPmTags(): BelongsToMany
    {
        return $this->belongsToMany(
            PuqPmTag::class,
            'puq_pm_public_network_x_tag',
            'puq_pm_public_network_uuid',
            'puq_pm_tag_uuid'
        );
    }


}
