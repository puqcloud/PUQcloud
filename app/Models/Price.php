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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Price extends Model
{
    use ConvertsTimezone;
    use HasFactory;

    protected $table = 'prices';

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
        'type',
        'currency_uuid',
        'period',
        'setup',
        'base',
        'idle',
        'switch_down',
        'switch_up',
        'uninstall',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'price_uuid', 'uuid');
    }

    public function product(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_x_price', 'price_uuid', 'product_uuid')
            ->withTimestamps();
    }

    public function productOption(): BelongsToMany
    {
        return $this->belongsToMany(ProductOption::class, 'product_option_x_price', 'price_uuid', 'product_option_uuid')
            ->withTimestamps();
    }

    public function getProductAttribute()
    {
        return $this->product()->first();
    }

    public function getProductOptionAttribute()
    {
        return $this->productOption()->first();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_uuid', 'uuid');
    }
}
