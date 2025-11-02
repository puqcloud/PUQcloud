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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DnsServerGroup extends Model
{
    use HasFactory;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'dns_server_groups';

    protected $fillable = [
        'name',
        'description',
        'ns_ttl',
        'ns_domains',
    ];

    protected $casts = [
        'ns_domains' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function dnsZones(): HasMany
    {
        return $this->hasMany(DnsZone::class, 'dns_server_group_uuid', 'uuid');
    }

    public function dnsServers(): BelongsToMany
    {
        return $this->belongsToMany(
            DnsServer::class,
            'dns_server_x_dns_server_group',
            'dns_server_group_uuid',
            'dns_server_uuid'
        )->withTimestamps();
    }
}
