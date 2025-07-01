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
use Illuminate\Support\Str;

class Currency extends Model
{
    use ConvertsTimezone;

    protected $table = 'currencies';

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
        'code',
        'prefix',
        'suffix',
        'exchange_rate',
        'default',
        'format',
    ];

    public static function getDefaultCurrency(): ?self
    {
        return static::where('default', true)->first();
    }

    public function paymentGateways()
    {
        return $this->belongsToMany(PaymentGateway::class, 'payment_gateway_x_currency', 'currency_uuid', 'payment_gateway_uuid')
            ->withTimestamps();
    }
}
