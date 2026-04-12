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
        'upgrade_template',
        'proration_template',
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

    /**
     * Walks all products in the group, collects a product_option_group UUID from each product's
     * `module.product_data.{$productDataKey}`, then returns the union of options in those groups
     * with monthly pricing formatted in the client's currency.
     *
     * This is the generic version of the "GetLocations"/"GetImages" pattern used by puqVPNCP,
     * puqProxmox and any other module that has an order flow where the client first picks a
     * location / image / region / OS / etc. via a tile grid. Replaces the copy-pasted loops that
     * used to live in every module's OrderController.
     *
     * Example calls:
     *   $locations = $product_group->getProductDataOptions('location_product_option_group_uuid', $client);
     *   $images    = $product_group->getProductDataOptions('os_product_option_group_uuid', $client);
     *
     * Each item in the returned array:
     *   [
     *     'key'   => string,
     *     'name'  => string,         // in client locale
     *     'value' => string,         // lowercased (useful for URL matching)
     *     'icon'  => string|null,    // option icon image URL if set
     *     'price' => [               // monthly price in client currency, omitted parts if null
     *        'setup' => '$ 1.00 USD',
     *        'base'  => '$ 5.00 USD',
     *     ],
     *   ]
     *
     * Items are sorted by lowercased `value` for stable rendering.
     *
     * @param  string  $productDataKey  Key inside module product_data pointing to an option group UUID.
     * @param  Client  $client          Client providing currency + locale.
     * @param  string  $period          Price period to look up (default 'monthly').
     */
    /**
     * Return a list of orderable products in this group for the default order form.
     *
     * "Orderable" = not retired, not hidden, and has at least one billing period configured
     * in the client's currency. Products without any available billing cycle are skipped
     * because they would render as unclickable rows.
     *
     * Each item is shaped for a DataTables-compatible card/row render:
     *   [
     *     'uuid'             => string,
     *     'name'             => string,            // localised
     *     'short_description'=> string,
     *     'icon'             => string|null,       // product icon image URL
     *     'billing_mode'     => 'prepaid'|'invoice'|'both',
     *     'billing_periods'  => array,             // from Product::getAvailableBillingPeriods
     *     'default_period'   => string,            // 'monthly' if available, else first period key
     *     'price'            => float,             // numeric base price for default_period
     *     'price_string'     => string,            // formatted base_str for default_period
     *     'product_price_uuid' => string,          // price UUID for default_period
     *   ]
     *
     * Used by the default order template to render a universal product picker — no
     * location filter, no module-specific fields. Modules with custom order flows
     * (puqVPNCP, puqProxmox) have their own GetProducts endpoints and do NOT call this.
     *
     * @param  Client  $client  Client providing currency for price lookup.
     */
    public function getOrderableProducts(Client $client): array
    {
        // Localise product names so the order form displays them in the user's language.
        $locale = null;
        try {
            $user = app('user');
            $locale = $user?->language ?? null;
        } catch (\Throwable $e) {
            // Not bound (CLI/queue) — fall back to client locale.
        }
        if (! $locale) {
            $locale = $client->language ?? null;
        }

        $products = $this->products()
            ->where('retired', false)
            ->where('hidden', false)
            ->orderBy('product_x_product_group.order')
            ->get();

        $rows = [];
        foreach ($products as $product) {
            if ($locale) {
                $product->setLocale($locale);
            }

            $billingPeriods = $product->getAvailableBillingPeriods($client);
            if (empty($billingPeriods)) {
                // No prices in the client's currency — can't be ordered, skip.
                continue;
            }

            // Prefer monthly as the "starting" cycle (most common), else fall back to
            // the first period returned (which is the shortest available per PERIOD_ORDER).
            $defaultPeriod = null;
            foreach ($billingPeriods as $bp) {
                if ($bp['period'] === 'monthly') {
                    $defaultPeriod = $bp;
                    break;
                }
            }
            if ($defaultPeriod === null) {
                $defaultPeriod = $billingPeriods[0];
            }

            $rows[] = [
                'uuid' => $product->uuid,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'icon' => $product->images['icon'] ?? null,
                'billing_mode' => $product->billing_mode ?? 'prepaid',
                'billing_periods' => $billingPeriods,
                'default_period' => $defaultPeriod['period'],
                'price' => $defaultPeriod['base'],
                'price_string' => $defaultPeriod['base_str'],
                'product_price_uuid' => $defaultPeriod['price_uuid'],
            ];
        }

        return $rows;
    }

    public function getProductDataOptions(string $productDataKey, Client $client, string $period = 'monthly'): array
    {
        $currency = $client->currency;

        // Prefer the logged-in user's language over the client-level language — consistent with
        // every other shared helper (Product::getOptionGroupsForPrice etc.) which do the same.
        // Client zone binds the singleton `user` to the request's authenticated user.
        $locale = null;
        try {
            $user = app('user');
            $locale = $user?->language ?? null;
        } catch (\Throwable $e) {
            // app('user') may not be bound outside of a client-zone request — fall back to client language.
        }
        if (! $locale) {
            $locale = $client->language ?? null;
        }

        // Collect all distinct option_group UUIDs referenced by products in this group under $productDataKey.
        $groupUuids = [];
        foreach ($this->products as $product) {
            $uuid = data_get($product, 'module.module.product_data.'.$productDataKey);
            if ($uuid && ! in_array($uuid, $groupUuids, true)) {
                $groupUuids[] = $uuid;
            }
        }

        if (empty($groupUuids)) {
            return [];
        }

        $productOptions = ProductOption::query()
            ->whereIn('product_option_group_uuid', $groupUuids)
            ->orderBy('order')
            ->get();

        $prefix = $currency->prefix ?? '';
        $suffix = $currency->suffix ?? '';
        $formatMoney = static function ($amount) use ($prefix, $suffix) {
            return $prefix.' '.number_format((float) $amount, 2).' '.$suffix;
        };

        $items = [];
        foreach ($productOptions as $option) {
            if ($locale) {
                $option->setLocale($locale);
            }

            // Fetch ALL prices for this option in the client's currency, keyed by period so
            // the UI can switch between billing cycles on the client side without re-fetching.
            $allPrices = $option->prices()
                ->where('currency_uuid', $currency->uuid)
                ->get()
                ->keyBy('period');

            // Default-period price object (currently the monthly / $period passed in).
            // Kept for backward compatibility — existing consumers read `price.setup` / `price.base`.
            $price = [];
            $priceModel = $allPrices->get($period);
            if ($priceModel) {
                if ($priceModel->setup) {
                    $price['setup'] = $formatMoney($priceModel->setup);
                }
                if ($priceModel->base) {
                    $price['base'] = $formatMoney($priceModel->base);
                }
            }

            // Full per-period price map for frontends that let the user switch billing cycles.
            // Walks the canonical period config so the order is consistent with every billing
            // period selector across the app.
            $pricesByPeriod = [];
            foreach (array_keys(config('pricing.product', [])) as $periodKey) {
                $pm = $allPrices->get($periodKey);
                if (! $pm) {
                    continue;
                }
                $entry = [
                    'period' => $periodKey,
                    'label' => __('main.'.$periodKey),
                ];
                if ($pm->setup) {
                    $entry['setup'] = $formatMoney($pm->setup);
                }
                if ($pm->base) {
                    $entry['base'] = $formatMoney($pm->base);
                }
                $pricesByPeriod[$periodKey] = $entry;
            }

            $items[strtolower((string) $option->value)] = [
                'key' => $option->key,
                'name' => $option->name,
                'value' => strtolower((string) $option->value),
                'icon' => data_get($option, 'images.icon'),
                'price' => $price,
                'prices_by_period' => $pricesByPeriod,
            ];
        }

        ksort($items);

        return array_values($items);
    }
}
