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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PuqPmNode extends Model
{
    protected $table = 'puq_pm_nodes';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'puq_pm_cluster_uuid',
        'id',
        'name',
        'cpu',
        'level',
        'maxcpu',
        'maxmem',
        'mem',
        'status',
        'uptime',
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
        return $this->belongsTo(PuqPmCluster::class, 'puq_pm_cluster_uuid', 'uuid');
    }

    public function puqPmStorages(): HasMany
    {
        return $this->hasMany(PuqPmStorage::class, 'puq_pm_node_uuid', 'uuid');
    }

    public function puqPmTags(): BelongsToMany
    {
        return $this->belongsToMany(
            PuqPmTag::class,
            'puq_pm_node_x_tag',
            'puq_pm_node_uuid',
            'puq_pm_tag_uuid'
        );
    }
}
