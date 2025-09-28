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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Product extends Model
{
    use AutoTranslatable;
    use ConvertsTimezone;
    use HasFactory;
    use HasFiles;

    const IMAGES = [
        'icon' => ['label' => 'Icon image', 'order' => 1],
        'background' => ['label' => 'Background image', 'order' => 2],
    ];

    protected $table = 'products';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::retrieved(function ($model) {
            if ($model->module) {
                $model->module->moduleExecute('getProductData', $model->configuration);
                $model->module->moduleExecute('setProductUuid', $model->uuid);
            }
        });
    }

    protected $fillable = [
        'key',
        'module_uuid',
        'welcome_email_uuid',
        'suspension_email_uuid',
        'unsuspension_email_uuid',
        'termination_email_uuid',
        'hidden',
        'retired',
        'stock_control',
        'quantity',
        'configuration',
        'termination_delay_hours',
        'notes',
    ];

    protected $casts = [
        'hidden' => 'boolean',
        'stock_control' => 'boolean',
        'retired' => 'boolean',
    ];

    protected $translatable = ['name', 'short_description', 'description'];

    public function getConfigurationAttribute($value): array
    {
        $configuration = json_decode($value, true);

        return is_array($configuration) ? $configuration : [];
    }

    public function setConfigurationAttribute($value): void
    {
        $this->attributes['configuration'] = json_encode($value);
    }

    public function productGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProductGroup::class, 'product_x_product_group', 'product_uuid',
            'product_group_uuid')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function prices(): BelongsToMany
    {
        return $this->belongsToMany(Price::class, 'product_x_price', 'product_uuid', 'price_uuid')
            ->withTimestamps();
    }

    public function productAttributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_x_product_attribute', 'product_uuid',
            'product_attribute_uuid')
            ->withTimestamps();
    }

    public function productOptionGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionGroup::class, 'product_x_product_option_group', 'product_uuid',
            'product_option_group_uuid')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'product_uuid', 'uuid');
    }

    public function getPrices(?string $period = null, ?string $currencyCode = null): array
    {
        $config_prices = config('pricing.product');
        $product_prices = $this->prices;
        $currencies = Currency::all();
        $all_price_types = collect($config_prices)->flatMap(fn($config) => $config['prices'])->unique();
        $prices = [];

        foreach ($config_prices as $config_period => $config_price) {
            if ($period && $config_period !== $period) {
                continue;
            }

            $period_prices = $product_prices->filter(fn($product_price) => $product_price->period === $config_period);

            if ($period_prices->isNotEmpty()) {
                $grouped_by_currency = $period_prices->groupBy('currency_uuid');

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
                        $product_price = $currency_prices->first(fn($item) => $item->type === $type);
                        $price[$type] = $product_price ? $product_price->amount : null;
                        $has_value = $has_value || $product_price !== null;
                    }

                    if ($has_value) {
                        $prices[] = $price;
                    }
                }
            }
        }

        return $prices;
    }

    public function addProductOptionGroup(ProductOptionGroup $product_option_group): void
    {
        $lastOrder = $this->productOptionGroups()->max('order') ?? 0;
        $this->productOptionGroups()->attach($product_option_group->uuid, ['order' => $lastOrder + 1]);
    }

    public function removeProductOptionGroup(ProductOptionGroup $product_option_group): void
    {
        $pivotTable = 'product_x_product_option_group';
        $orderColumn = 'order';

        $this->productOptionGroups()->detach($product_option_group->uuid);

        $remainingProductOptionGroup = DB::table($pivotTable)
            ->where('product_uuid', $this->uuid)
            ->orderBy($orderColumn)
            ->get();
        $newOrder = 1;
        foreach ($remainingProductOptionGroup as $productOptionGroup) {
            DB::table($pivotTable)
                ->where('product_uuid', $this->uuid)
                ->where('product_option_group_uuid', $productOptionGroup->product_option_group_uuid)
                ->update([$orderColumn => $newOrder++]);
        }
    }

    public function convertPrice():void
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

    public function getOptionGroupsWithPrices(Price $price): Collection
    {
        $currency = $price->currency;

        return $this->productOptionGroups->sortBy(function ($group) {
            return $group->pivot->order ?? 0;
        })->values()->map(function ($group) use ($price, $currency) {
            $options = $group->productOptions->sortBy(function ($option) {
                return $option->order ?? 0;
            })->map(function ($option) use ($price, $currency) {
                $label = $option->name ?: $option->key;

                $matched_price = $option->prices->first(function ($p) use ($price) {
                    return $p->currency_uuid === $price->currency_uuid &&
                        $p->period === $price->period;
                });

                if ($matched_price) {
                    $label .= ' - '.__('main.'.$matched_price->period).' - '.$matched_price->base.' '.$currency->code;
                }

                return [
                    'uuid' => $option->uuid,
                    'key' => $label,
                ];
            })->values();

            return [
                'uuid' => $group->uuid,
                'key' => $group->name ?: $group->key,
                'product_options' => $options,
            ];
        })->values();
    }

    // Module ----------------------------------------------------------------------------------------------------------
    public function getSettingsPage(): string
    {
        if (empty($this->module)) {
            return '<h1>'.__('error.The module is not available').'</h1>';
        }

        $data_array = $this->module->moduleExecute('getProductPage');

        if ($data_array['status'] == 'error') {
            return $data_array['message'];
        }

        return $data_array['data'];
    }

    public function saveModuleData(array $data = []): array
    {
        if (empty($this->module)) {
            return [
                'status' => 'error',
                'message' => [__('error.Module not found')],
                'code' => 404,
            ];
        }
        $data_array = $this->module->moduleExecute('saveProductData', $data);

        if ($data_array['status'] == 'error') {
            $data_array['code'] = $data_array['code'] ?? 500;
            return $data_array;
        }

        $this->configuration = $data_array['data'];

        return $data_array;
    }

    // -----------------------------------------------------------------------------------------------------------------
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_uuid', 'uuid');
    }

    public function hasProductOption(?string $productOptionGroupUuid, ?string $productOptionUuid): bool
    {

        if (!$productOptionGroupUuid or !$productOptionUuid) {
            return false;
        }

        $productOptionGroup = $this->productOptionGroups()
            ->where('uuid', $productOptionGroupUuid)
            ->first();

        if (!$productOptionGroup) {
            return false;
        }

        return $productOptionGroup->productOptions()
            ->where('uuid', $productOptionUuid)
            ->exists();
    }

    public function getFalseOption(?string $productOptionGroupUuid): ?string
    {
        if (!$productOptionGroupUuid) {
            return null;
        }

        $product_option_group = ProductOptionGroup::query()
            ->where('uuid', $productOptionGroupUuid)
            ->first();

        if ($product_option_group) {
            $product_option = $product_option_group->productOptions()->where('value', 0)->first();
            if ($product_option) {
                return $product_option->uuid;
            }
        }

        return null;
    }

    public function getTrueOption(?string $productOptionGroupUuid): ?string
    {
        if (!$productOptionGroupUuid) {
            return null;
        }

        $product_option_group = ProductOptionGroup::query()
            ->where('uuid', $productOptionGroupUuid)
            ->first();

        if ($product_option_group) {
            $product_option = $product_option_group->productOptions()->where('value', 1)->first();
            if ($product_option) {
                return $product_option->uuid;
            }
        }

        return null;
    }

}
