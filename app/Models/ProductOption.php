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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductOption extends Model
{
    use AutoTranslatable;
    use ConvertsTimezone;
    use HasFactory;
    use HasFiles;

    const IMAGES = [
        'icon' => ['label' => 'Icon image', 'order' => 1],
        'background' => ['label' => 'Background image', 'order' => 2],
    ];

    protected $table = 'product_options';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
        //        static::retrieved(function ($model) {});
        //        static::saving(function ($model) {});
    }

    protected $fillable = [
        'key',
        'product_option_group_uuid',
        'value',
        'hidden',
        'order',
        'notes',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    protected $translatable = ['name', 'short_description', 'description'];

    public static function reorder($product_option_group_uuid): void
    {
        $product_options = self::orderBy('order')
            ->where('product_option_group_uuid', $product_option_group_uuid)
            ->get();
        $order = 1;

        foreach ($product_options as $product_option) {
            DB::table('product_options')
                ->where('uuid', $product_option->uuid)
                ->update(['order' => $order]);
            $order++;
        }
    }

    public function productOptionGroup(): BelongsTo
    {
        return $this->belongsTo(ProductOptionGroup::class, 'product_option_group_uuid', 'uuid');
    }

    public function prices(): BelongsToMany
    {
        return $this->belongsToMany(Price::class, 'product_option_x_price', 'product_option_uuid', 'price_uuid')
            ->withTimestamps();
    }

    public function getPrices(?string $period = null, ?string $currencyCode = null): array
    {
        $config_prices = config('pricing.product');
        $product_option_prices = $this->prices;
        $currencies = Currency::all();
        $all_price_types = collect($config_prices)->flatMap(fn($config) => $config['prices'])->unique();
        $prices = [];

        foreach ($config_prices as $config_period => $config_price) {
            if ($period && $config_period !== $period) {
                continue;
            }

            $period_option_prices = $product_option_prices->filter(fn($product_option_price
            ) => $product_option_price->period === $config_period);

            if ($period_option_prices->isNotEmpty()) {
                $grouped_by_currency = $period_option_prices->groupBy('currency_uuid');
                foreach ($grouped_by_currency as $currency_uuid => $currency_prices) {
                    $currency = $currencies->find($currency_uuid);

                    if ($currencyCode && (!$currency || $currency->code !== $currencyCode)) {
                        continue;
                    }

                    $price = [
                        'period' => $config_period,
                        'currency' => $currency ? $currency->code : null,
                    ];

                    $has_value = false;

                    foreach ($all_price_types as $type) {
                        $product_option_price = $currency_prices->first();
                        if (!empty($product_option_price->$type)) {
                            $price[$type] = $product_option_price->$type;
                            $has_value = $has_value || $product_option_price !== null;
                        }
                    }

                    if ($has_value) {
                        $prices = $price;
                    }
                }

            }
        }

        return $prices;
    }

    public function convertPrice(): void
    {
        $currencies = Currency::where('default', false)->get();
        $defaultCurrency = Currency::where('default', true)->first();
        $defaultPrices = $this->prices()->where('currency_uuid', $defaultCurrency->uuid)->get();

        foreach ($defaultPrices as $defaultPrice) {
            foreach ($currencies as $currency) {
                $rate = $currency->exchange_rate;

                $price = $this->prices()
                    ->where('currency_uuid', $currency->uuid)
                    ->where('period', $defaultPrice->period)
                    ->first();

                $data = [
                    'currency_uuid' => $currency->uuid,
                    'period' => $defaultPrice->period,
                    'setup' => is_null($defaultPrice->setup) ? null : round($defaultPrice->setup * $rate, 4,PHP_ROUND_HALF_UP),
                    'base' => is_null($defaultPrice->base) ? null : round($defaultPrice->base * $rate, 4,PHP_ROUND_HALF_UP),
                    'idle' => is_null($defaultPrice->idle) ? null : round($defaultPrice->idle * $rate, 4,PHP_ROUND_HALF_UP),
                    'switch_down' => is_null($defaultPrice->switch_down) ? null : round($defaultPrice->switch_down * $rate,
                        4,PHP_ROUND_HALF_UP),
                    'switch_up' => is_null($defaultPrice->switch_up) ? null : round($defaultPrice->switch_up * $rate,
                        4,PHP_ROUND_HALF_UP),
                    'uninstall' => is_null($defaultPrice->uninstall) ? null : round($defaultPrice->uninstall * $rate,
                        4,PHP_ROUND_HALF_UP),
                ];

                if ($price) {
                    $price->update($data);
                } else {
                    $this->prices()->create($data);
                }
            }
        }
    }
}
