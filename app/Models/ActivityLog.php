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
 * @property string $uuid
 * @property string $admin_uuid
 * @property string $user_uuid
 * @property string $client_uuid
 * @property string $action
 * @property string $level
 * @property string $description
 * @property string $ip_address
 * @property Admin $admin
 */
class ActivityLog extends Model
{
    use ConvertsTimezone;

    protected $table = 'activity_logs';

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

    protected $fillable = [
        'admin_uuid',
        'user_uuid',
        'client_uuid',
        'level',
        'action',
        'description',
        'model_type',
        'model_uuid',
        'ip_address',
        'model_old_data',
        'model_new_data',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_uuid', 'uuid');
    }
}
