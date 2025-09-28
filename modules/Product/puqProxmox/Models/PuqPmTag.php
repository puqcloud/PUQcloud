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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class PuqPmTag extends Model
{
    protected $table = 'puq_pm_tags';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmNodes(): BelongsToMany
    {
        return $this->belongsToMany(PuqPmNode::class,
            'puq_pm_node_x_tag',
            'puq_pm_tag_uuid',
            'puq_pm_node_uuid'
        );
    }

    public function puqPmStorages(): BelongsToMany
    {
        return $this->belongsToMany(PuqPmStorage::class,
            'puq_pm_storage_x_tag',
            'puq_pm_tag_uuid',
            'puq_pm_storage_uuid'
        );
    }

    public function puqPmPublicNetworks(): BelongsToMany
    {
        return $this->belongsToMany(PuqPmPublicNetwork::class,
            'puq_pm_public_network_x_tag',
            'puq_pm_tag_uuid',
            'puq_pm_public_network_uuid'
        );
    }

    public function puqPmPrivateNetworks(): BelongsToMany
    {
        return $this->belongsToMany(PuqPmPrivateNetwork::class,
            'puq_pm_private_network_x_tag',
            'puq_pm_tag_uuid',
            'puq_pm_private_network_uuid'
        );
    }

    public function safeDelete(): void
    {
        if (
            $this->puqPmNodes()->exists() ||
            $this->puqPmStorages()->exists()
        ) {
            return;
        }
        $this->delete();
    }
}
