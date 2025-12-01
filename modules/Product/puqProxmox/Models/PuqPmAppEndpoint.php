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

class PuqPmAppEndpoint extends Model
{
    protected $table = 'puq_pm_app_endpoints';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'subdomain',
        'server_custom_config_before',
        'server_custom_config',
        'server_custom_config_after',
        'puq_pm_app_preset_uuid',
    ];

    protected $casts = [];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

    }

    public function puqPmAppPreset(): BelongsTo
    {
        return $this->belongsTo(PuqPmAppPreset::class, 'puq_pm_app_preset_uuid', 'uuid');
    }

    public function puqPmAppEndpointLocations(): HasMany
    {
        return $this->hasMany(PuqPmAppEndpointLocation::class, 'puq_pm_app_endpoint_uuid', 'uuid');
    }

}
