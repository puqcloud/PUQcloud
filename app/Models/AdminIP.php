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

use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @param  string  $uuid
 * @param  string  $admin_uuid
 * @param  string  $start_use
 * @param  string  $stop_use
 * @param  string  $ip_address
 * @return void This function does not return a value. It only saves the log entry to the database.
 */
class AdminIP extends Model
{
    use ConvertsTimezone;

    protected $table = 'admin_ips';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

    }

    protected $fillable = ['admin_uuid', 'ip_address', 'start_use', 'stop_use'];

    protected $casts = [
        'start_use' => 'datetime',
        'stop_use' => 'datetime',

    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_uuid', 'uuid');
    }
}
