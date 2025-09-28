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
use puqProxmoxClient;

class PuqPmAccessServer extends Model
{
    protected $table = 'puq_pm_access_servers';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'puq_pm_cluster_uuid',
        'description',
        'ssh_host',
        'ssh_username',
        'ssh_port',
        'ssh_error',
        'ssh_response_time',
        'api_host',
        'api_port',
        'api_token',
        'api_token_id',
        'api_error',
        'api_response_time',
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

    public function testConnection(): array
    {
        $client = new puqProxmoxClient($this->toArray());

        return $client->testConnection();
    }

}
