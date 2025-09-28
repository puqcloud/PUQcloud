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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class puqPmLxcInstanceNet extends Model
{
    protected $table = 'puq_pm_lxc_instance_nets';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'type', // public, local_private, global_private
        'puq_pm_mac_pool_uuid',
        'mac',
        'puq_pm_ipv4_pool_uuid',
        'ipv4',
        'rdns_v4',
        'mask_v4',
        'puq_pm_ipv6_pool_uuid',
        'ipv6',
        'rdns_v6',
        'mask_v6',
        'puq_pm_lxc_instance_uuid',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmLxcInstance(): BelongsTo
    {
        return $this->belongsTo(PuqPmLxcInstance::class, 'puq_pm_lxc_instance_uuid', 'uuid');
    }

    public function PuqPmMacPool(): BelongsTo
    {
        return $this->belongsTo(PuqPmMacPool::class, 'puq_pm_mac_pool_uuid', 'uuid');
    }

    public function puqPmIpV4Pool(): BelongsTo
    {
        return $this->belongsTo(PuqPmIpPool::class, 'puq_pm_ipv4_pool_uuid', 'uuid');
    }

    public function puqPmIpV6Pool(): BelongsTo
    {
        return $this->belongsTo(PuqPmIpPool::class, 'puq_pm_ipv6_pool_uuid', 'uuid');
    }
}
