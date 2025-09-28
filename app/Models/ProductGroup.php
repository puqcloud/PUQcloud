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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductGroup extends Model
{
    use AutoTranslatable;
    use ConvertsTimezone;
    use HasFiles;

    const IMAGES = [
        'icon' => ['label' => 'Icon image', 'order' => 1],
        'background' => ['label' => 'Background image', 'order' => 2],
    ];

    protected $table = 'product_groups';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->order = ProductGroup::max('order') + 1;
        });

    }

    protected $fillable = [
        'key',
        'notes',
        'order',
        'icon',
        'list_template',
        'order_template',
        'manage_template',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    protected $translatable = ['name', 'short_description', 'description'];

    public static function reorder()
    {

        $productGroups = self::orderBy('order')->get();
        $order = 1;

        foreach ($productGroups as $productGroup) {
            DB::table('product_groups')
                ->where('uuid', $productGroup->uuid)
                ->update(['order' => $order]);
            $order++;
        }
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_x_product_group', 'product_group_uuid', 'product_uuid')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function addProduct(Product $product): void
    {
        $lastOrder = $this->products()->max('order') ?? 0;
        $this->products()->attach($product->uuid, ['order' => $lastOrder + 1]);
    }

    public function removeProduct(Product $product): void
    {
        $pivotTable = 'product_x_product_group';
        $orderColumn = 'order';

        $this->products()->detach($product->uuid);

        $remainingProducts = DB::table($pivotTable)
            ->where('product_group_uuid', $this->uuid)
            ->orderBy($orderColumn)
            ->get();

        $newOrder = 1;
        foreach ($remainingProducts as $product) {
            DB::table($pivotTable)
                ->where('product_uuid', $product->product_uuid)
                ->where('product_group_uuid', $this->uuid)
                ->update([$orderColumn => $newOrder++]);
        }
    }
}
