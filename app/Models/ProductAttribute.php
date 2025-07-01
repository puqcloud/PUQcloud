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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    use AutoTranslatable;
    use ConvertsTimezone;
    use HasFactory;
    use HasFiles;

    const IMAGES = [
        'icon' => ['label' => 'Icon image', 'order' => 1],
        'background' => ['label' => 'Background image', 'order' => 2],
    ];

    protected $table = 'product_attributes';

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
        'product_attribute_group_uuid',
        'hidden',
        'order',
        'notes',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    protected $translatable = ['name', 'value', 'short_description', 'description'];

    public function productAttributeGroup(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeGroup::class, 'product_attribute_group_uuid', 'uuid');
    }
}
