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

use App\Traits\AutoTranslatable;
use App\Traits\ConvertsTimezone;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductOptionGroup extends Model
{
    use AutoTranslatable;
    use ConvertsTimezone;
    use HasFactory;
    use HasFiles;

    const IMAGES = [
        'icon' => ['label' => 'Icon image', 'order' => 1],
        'background' => ['label' => 'Background image', 'order' => 2],
    ];

    protected $table = 'product_option_groups';

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
        'key',
        'type',
        'hidden',
        'notes',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    protected $translatable = ['name', 'short_description', 'description'];

    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class, 'product_option_group_uuid', 'uuid');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_x_product_option_group', 'product_option_group_uuid', 'product_uuid')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function convertPrice(): void
    {
        $product_options = $this->productOptions;
        foreach ($product_options as $product_option) {
            $product_option->convertPrice();
        }
    }
}
