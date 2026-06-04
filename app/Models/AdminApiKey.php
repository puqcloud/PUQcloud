<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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

use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminApiKey extends Model
{
    use ConvertsTimezone;

    protected $table = 'admin_api_keys';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    protected $fillable = [
        'admin_uuid',
        'name',
        'hash',
        'description',
        'ips',
        'last_used_at',
    ];

    protected $casts = [
        'ips' => 'array',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'hash',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_uuid', 'uuid');
    }

    public function ipAllowed(?string $ip): bool
    {
        if (empty($this->ips)) {
            return true;
        }

        return in_array($ip, $this->ips, true);
    }
}
