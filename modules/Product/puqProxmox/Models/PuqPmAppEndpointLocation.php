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
use Illuminate\Support\Str;

class PuqPmAppEndpointLocation extends Model
{
    protected $table = 'puq_pm_app_endpoint_locations';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'path',
        'show_to_client',
        'proxy_protocol',
        'proxy_port',
        'proxy_path',
        'custom_config',
        'puq_pm_app_endpoint_uuid',
    ];

    protected $casts = [
        'show_to_client' => 'boolean',
        'proxy_port' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmAppEndpoint(): BelongsTo
    {
        return $this->belongsTo(PuqPmAppEndpoint::class, 'puq_pm_app_endpoint_uuid', 'uuid');
    }
}
