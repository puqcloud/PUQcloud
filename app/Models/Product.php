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
        'billing_mode',
        'allow_upgrade',
        'allow_downgrade',
        'allow_proration_alignment',
        'notes',
    ];

    protected $casts = [
        'hidden' => 'boolean',
        'stock_control' => 'boolean',
        'retired' => 'boolean',
        'allow_upgrade' => 'boolean',
        'allow_downgrade' => 'boolean',
        'allow_proration_alignment' => 'boolean',
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

    /**
     * Standard period sort order for UI listings (from shortest to longest billing cycle).
     * Centralised here so every controller that lists prices renders them consistently.
     */
    public const PERIOD_ORDER = [
        'one-time',
        'hourly',
        'daily',
        'weekly',
        'bi-weekly',
        'monthly',
        'quarterly',
        'semi-annually',
        'annually',
        'biennially',
        'triennially',
        'four-yearly',
        'five-yearly',
        'decadally',
    ];

    /**
     * Fetch this product's prices in a specific client's currency, optionally restricted to one period,
     * sorted by canonical PERIOD_ORDER.
     *
     * This is the generic replacement for the inline query + sort block that every order/upgrade
     * controller used to copy-paste. Other modules can call this directly instead of re-implementing it.
     *
     * @param  Client        $client  The client whose currency should be used.
     * @param  string|null   $period  Optional period filter (e.g. 'monthly'). Omit for all periods.
     */
    public function getPricesForClient(Client $client, ?string $period = null): Collection
    {
        $query = $this->prices()->where('currency_uuid', $client->currency_uuid);

        if ($period !== null) {
            $query->where('period', $period);
        }

        $prices = $query->get();

        return $prices->sort(function ($a, $b) {
            return array_search($a->period, self::PERIOD_ORDER) <=> array_search($b->period, self::PERIOD_ORDER);
        })->values();
    }

    /**
     * Get product attributes belonging to a specific attribute group.
     *
     * Generic helper for modules that read "badge"-style attributes (protocols, features, flags, …)
     * out of a named attribute group whose UUID is stored in the module's product_data.
     */
    public function getAttributesByGroup(string $groupUuid): Collection
    {
        return $this->productAttributes()
            ->where('product_attribute_group_uuid', $groupUuid)
            ->get();
    }

    /**
     * Return all billing periods that this product has a price configured for, in the client's
     * currency. Used by any module that needs to render a "billing cycle" selector on its order
     * form so the client can pick monthly / quarterly / annually / etc.
     *
     * Canonical period list comes from `config('pricing.product')`; only periods that actually
     * have a price row in the client's currency are returned. Result is sorted by the config
     * priority (shortest → longest cycle) so the UI renders the cheapest → most expensive.
     *
     * Each entry in the returned array:
     *   [
     *     'period'        => string,   // machine key, e.g. 'monthly'
     *     'label'         => string,   // localized human label (e.g. 'Monthly')
     *     'price_uuid'    => string,   // price row UUID to submit with the order
     *     'base'          => float,    // base price (numeric)
     *     'base_str'      => string,   // formatted base price (currency prefix/suffix)
     *     'setup'         => float,    // setup fee (numeric)
     *     'setup_str'     => string,   // formatted setup fee
     *     'priority'      => int|null, // hours-in-period from config (null for one-time)
     *   ]
     *
     * @param  Client  $client  Client whose currency should be used for price lookup and formatting.
     */
    public function getAvailableBillingPeriods(Client $client): array
    {
        $currency = $client->currency;
        if (! $currency) {
            return [];
        }

        // Fetch all prices for this product in the client's currency, keyed by period for O(1) lookup.
        $prices = $this->prices()
            ->where('currency_uuid', $currency->uuid)
            ->get()
            ->keyBy('period');

        // Walk the canonical period config in order — this guarantees consistent sort order
        // across every module's order form and avoids hard-coding period lists per-controller.
        $periodConfig = config('pricing.product', []);
        $result = [];

        foreach ($periodConfig as $periodKey => $periodCfg) {
            $price = $prices->get($periodKey);
            // Only include periods that actually have a price configured. A period with only
            // setup fee and no base makes no sense for a billing cycle, so require `base`.
            if (! $price || $price->base === null || (float) $price->base <= 0) {
                continue;
            }

            $basePrefix = ($currency->prefix ?? '').' ';
            $baseSuffix = ' '.($currency->suffix ?? '');

            $result[] = [
                'period' => $periodKey,
                'label' => __('main.'.$periodKey),
                'price_uuid' => $price->uuid,
                'base' => (float) $price->base,
                'base_str' => $basePrefix.number_format((float) $price->base, 2).$baseSuffix,
                'setup' => (float) ($price->setup ?? 0),
                'setup_str' => $basePrefix.number_format((float) ($price->setup ?? 0), 2).$baseSuffix,
                'priority' => $periodCfg['priority'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Enhanced version of `getOptionGroupsWithPrices()` for client-facing order/upgrade views.
     *
     * Features:
     *   - sets locale from the current user (so labels render in the client's language)
     *   - formats labels using the currency prefix/suffix ("Label - $ 5.00 USD / Monthly")
     *   - exposes the raw option `value` + numeric `price` + formatted `price_string` so modules
     *     can use them directly in JS without re-querying
     *   - tags each group with a `type` derived from the product's module data
     *     (`'location'` is auto-detected from `location_product_option_group_uuid`; other types
     *     can be passed in via `$extraTypes`, or the group is tagged `'other'`)
     *   - attaches per-type metadata (`attributes`) to each matched group, so modules can pass
     *     things like CPU/RAM constraints to the frontend alongside the options
     *
     * Extra types format (used by modules like puqProxmox that need cpu/ram/disk type detection):
     *   [
     *     'cpu'          => ['uuid' => $cpuGroupUuid, 'attributes' => [...]],
     *     'ram'          => ['uuid' => $ramGroupUuid, 'attributes' => [...]],
     *     'system_disk'  => ['uuid' => $rootfsGroupUuid, 'attributes' => [...]],
     *     ...
     *   ]
     *
     * @param  Price  $price       Price context (used for currency + period matching on option prices).
     * @param  array  $extraTypes  Module-specific type mappings to merge with the default location detection.
     */
    public function getOptionGroupsForPrice(Price $price, array $extraTypes = []): Collection
    {
        $currency = $price->currency;
        $user = app('user');

        // Default type detection: location group from module product_data.
        // Module-specific extras are merged on top — they can override 'location' too if needed.
        $groupTypes = [];
        $productData = data_get($this, 'module.module.product_data');
        if (! empty($productData['location_product_option_group_uuid'])) {
            $groupTypes['location'] = [
                'uuid' => $productData['location_product_option_group_uuid'],
                'attributes' => [],
            ];
        }

        // Normalise $extraTypes: accept either `['type' => 'uuid-string']` (simple) or
        // `['type' => ['uuid' => '...', 'attributes' => [...]]]` (full).
        foreach ($extraTypes as $typeKey => $def) {
            if (is_string($def)) {
                $groupTypes[$typeKey] = ['uuid' => $def, 'attributes' => []];
            } elseif (is_array($def) && ! empty($def['uuid'])) {
                $groupTypes[$typeKey] = [
                    'uuid' => $def['uuid'],
                    'attributes' => $def['attributes'] ?? [],
                ];
            }
        }

        return $this->productOptionGroups->sortBy(function ($group) {
            return $group->pivot->order ?? 0;
        })->values()->map(function ($group) use ($price, $currency, $user, $groupTypes) {
            if ($user) {
                $group->setLocale($user->language);
            }

            $options = $group->productOptions->sortBy(function ($option) {
                return $option->order ?? 0;
            })->map(function ($option) use ($price, $currency, $user) {
                if ($user) {
                    $option->setLocale($user->language);
                }

                $matchedPrice = $option->prices->first(function ($p) use ($price) {
                    return $p->currency_uuid === $price->currency_uuid
                        && $p->period === $price->period;
                });

                $label = $option->name;
                $priceValue = null;
                $priceString = '';

                if ($matchedPrice) {
                    $priceValue = (float) $matchedPrice->base;
                    $priceString = ($currency->prefix ?? '').' '.$matchedPrice->base.' '.($currency->suffix ?? '');
                    $label .= ' - '.$priceString.' / '.__('main.'.$matchedPrice->period);
                }

                return [
                    'uuid' => $option->uuid,
                    'key' => $label,
                    'value' => $option->value,
                    'price' => $priceValue,
                    'price_string' => trim($priceString),
                ];
            })->values();

            // Resolve group type + its attached attributes.
            $type = 'other';
            $attributes = [];
            foreach ($groupTypes as $typeKey => $def) {
                if ($group->uuid === $def['uuid']) {
                    $type = $typeKey;
                    $attributes = $def['attributes'] ?? [];
                    break;
                }
            }

            return [
                'uuid' => $group->uuid,
                'type' => $type,
                'attributes' => $attributes,
                'key' => $group->key,
                'name' => $group->name,
                'short_description' => $group->short_description,
                'product_options' => $options,
            ];
        })->values();
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
