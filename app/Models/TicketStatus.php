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
use App\Traits\ModelActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketStatus extends Model
{
    use ConvertsTimezone;
    use ModelActivityLogger;

    protected $table = 'ticket_statuses';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'title', 'color', 'include_in_active', 'include_in_awaiting_reply',
        'auto_close_days', 'sort_order', 'is_default',
    ];

    protected $casts = [
        'include_in_active' => 'boolean',
        'include_in_awaiting_reply' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_uuid', 'uuid');
    }
}
