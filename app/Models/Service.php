<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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

use App\Services\HookService;
use App\Traits\ConvertsTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Service extends Model
{
    use ConvertsTimezone;

    protected $table = 'services';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::retrieved(function (Service $model) {
            if (empty($model->client_label)) {
                $model->client_label = $model->admin_label;
            }
            $model->ensureProductOptions();

            if ($model->module) {
                $model->module->moduleExecute('getServiceData', $model->provision_data);
                $model->module->moduleExecute('setServiceUuid', $model->uuid);
            }
        });

        static::saving(function ($model) {
            unset($model->attributes['provision_status']);
            unset($model->attributes['provision_data']);
            unset($model->attributes['client_label']);
        });

        static::updating(function (Service $model) {
            // When termination_request changes to true, clean up unpaid invoice items
            if ($model->isDirty('termination_request') && $model->termination_request) {
                $model->removeFromUnpaidInvoices();
            }
        });
    }

    protected $fillable = [
        'server_uuid',
        'client_uuid',
        'product_uuid',
        'status',
        'billing_mode',
        'idle',
        'provision_status',
        'provision_data',

        'order_date',

        'activated_date',
        'create_error',

        'suspended_date',
        'suspended_reason',

        'terminated_date',
        'terminated_reason',

        'cancelled_date',
        'cancelled_reason',

        'billing_timestamp',

        'termination_request',

        'admin_label',
        'admin_notes',
        'client_label',
        'client_notes',
    ];

    protected $casts = [
        'idle' => 'boolean',
    ];

    public function setProvisionStatus(string $status): void
    {
        $this->provision_status = $status;
        $this->saveQuietly(['provision_status']);
    }

    public function setProvisionData(array $provision_data): void
    {
        $this->provision_data = $provision_data;
        $this->saveQuietly(['provision_data']);
    }

    public function setClientLabel(string $client_label): void
    {
        $this->client_label = $client_label;
        $this->saveQuietly(['client_label']);
    }

    public static function createFromArray(array $data): array
    {
        $client = Client::find($data['client_uuid'] ?? null);
        if (empty($client)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        $product = Product::query()->where('uuid', $data['product_uuid'] ?? null)
            ->where('retired', false)->first();

        if (empty($product)) {
            return [
                'status' => 'error',
                'errors' => [__('error.The product is not found')],
                'code' => 404,
            ];
        }

        if ($product->stock_control) {
            if ($product->quantity <= $product->services()->count()) {
                return [
                    'status' => 'error',
                    'errors' => [__('error.The product is not available for order')],
                    'code' => 500,
                ];
            }
        }

        $product_price = $product->prices()->where('uuid', $data['product_price_uuid'] ?? null)->first();
        if (empty($product_price)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        if ($product_price->currency->uuid != $client->currency_uuid) {
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        DB::beginTransaction();
        try {
            // Determine billing mode for this service
            $serviceBillingMode = match ($product->billing_mode) {
                'invoice' => 'invoice',
                'both' => in_array($data['billing_mode'] ?? '', ['prepaid', 'invoice']) ? $data['billing_mode'] : 'invoice',
                default => 'prepaid',
            };

            $service = new self;
            $service->server_uuid = $client->uuid; // Will be deleted
            $service->client_uuid = $client->uuid;
            $service->product_uuid = $product->uuid;
            $service->price_uuid = $product_price->uuid;
            $service->billing_mode = $serviceBillingMode;
            $service->order_date = now();
            $service->generateAdminLabel();
            $service->save();
            $service->refresh();
            $service->productOptions()->detach();

            $input_options = $data['option_uuids'] ?? [];
            $product_option_groups = $product->productOptionGroups;

            foreach ($product_option_groups as $product_option_group) {
                $selected_option = $input_options[$product_option_group->uuid] ?? null;
                if (!$product->hasProductOption($product_option_group->uuid, $selected_option)) {
                    $group_option = $product_option_group->productOptions()->orderBy('order')->first();
                    if ($group_option) {
                        $selected_option = $group_option->uuid;
                    }
                }
                if ($selected_option) {
                    $service->productOptions()->attach($selected_option);
                }
            }

            DB::commit();

            app(HookService::class)->callHooks('PendingService', ['service' => $service]);

            // For invoice-mode: generate first invoice immediately
            // (service stays pending until invoice is paid)
            if ($serviceBillingMode === 'invoice') {
                try {
                    $service->billing_timestamp = now();
                    $service->save();
                    $service->refresh();
                    $service->generateServiceInvoice();
                } catch (\Throwable $e) {
                    logActivity(
                        'error',
                        'Service:'.$service->uuid.' Failed to generate first invoice: '.$e->getMessage(),
                        'order',
                        null,
                        null,
                        null,
                        $client->uuid
                    );
                }
            }

            return [
                'status' => 'success',
                'data' => $service,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            logActivity(
                'error',
                'Service order error for client:'.$client->uuid.'. '.$e->getMessage(),
                'order',
                null,
                null,
                null,
                $client->uuid
            );

            return [
                'status' => 'error',
                'errors' => [__('error.Something went wrong')],
                'code' => 500,
            ];
        }
    }

    public function getProvisionDataAttribute($value): array
    {
        $provision_data = json_decode($value, true);

        return is_array($provision_data) ? $provision_data : [];
    }

    public function setProvisionDataAttribute($value): void
    {
        $this->attributes['provision_data'] = json_encode($value);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    public function productGroups()
    {
        return $this->product ? $this->product->productGroups() : collect();
    }

    public function productOptions(): BelongsToMany
    {
        return $this->belongsToMany(ProductOption::class, 'service_x_product_option', 'service_uuid',
            'product_option_uuid');
    }

    public function getProductGroupsAttribute()
    {
        return $this->product ? $this->product->productGroups : collect();
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class, 'price_uuid', 'uuid');
    }

    public function getPriceTotal(): array
    {
        $round = fn($v) => round((float) $v, 2);

        $price = $this->price;
        $price_total = [
            'setup' => $round($price->setup ?? 0),
            'base' => $round($price->base ?? 0),
            'idle' => $round($price->idle ?? 0),
            'switch_down' => $round($price->switch_down ?? 0),
            'switch_up' => $round($price->switch_up ?? 0),
            'uninstall' => $round($price->uninstall ?? 0),
        ];

        $product_options = $this->productOptions;
        $optionUuids = $product_options->pluck('uuid');

        if ($optionUuids->isNotEmpty()) {
            $optionPrices = DB::table('product_option_x_price')
                ->join('prices', 'prices.uuid', '=', 'product_option_x_price.price_uuid')
                ->whereIn('product_option_x_price.product_option_uuid', $optionUuids)
                ->where('prices.currency_uuid', $price->currency_uuid)
                ->where('prices.period', $price->period)
                ->select('product_option_x_price.product_option_uuid', 'prices.*')
                ->get()
                ->keyBy('product_option_uuid');

            foreach ($product_options as $product_option) {
                $option_price = $optionPrices->get($product_option->uuid);

                if (!$option_price) {
                    continue;
                }

                foreach ($price_total as $key => $value) {
                    $option_value = $option_price->$key ?? 0;
                    if (is_numeric($option_value)) {
                        $price_total[$key] = $round($value + $option_value);
                    }
                }
            }
        }

        return $price_total;
    }

    public function getPriceDetailed(): array
    {
        $round = fn($v) => round((float) $v, 2);

        $price = $this->price;
        $currency = $price->currency;
        $currency_uuid = $currency->uuid;
        $period = $price->period;
        $product = $this->product;

        $product_option_groups = $this->product->productOptionGroups;

        $service_price = [
            'setup' => $round($price->setup ?? 0),
            'base' => $round($price->base ?? 0),
            'idle' => $round($price->idle ?? 0),
            'switch_down' => $round($price->switch_down ?? 0),
            'switch_up' => $round($price->switch_up ?? 0),
            'uninstall' => $round($price->uninstall ?? 0),
        ];

        $price_total = $service_price;
        $option_prices = [];

        $productOptions = $this->productOptions;
        $optionUuids = $productOptions->pluck('uuid');

        $optionPricesMap = collect();
        if ($optionUuids->isNotEmpty()) {
            $optionPricesMap = DB::table('product_option_x_price')
                ->join('prices', 'prices.uuid', '=', 'product_option_x_price.price_uuid')
                ->whereIn('product_option_x_price.product_option_uuid', $optionUuids)
                ->where('prices.currency_uuid', $currency_uuid)
                ->where('prices.period', $period)
                ->select('product_option_x_price.product_option_uuid', 'prices.*')
                ->get()
                ->keyBy('product_option_uuid');
        }

        foreach ($productOptions as $product_option) {
            $option_price = $optionPricesMap->get($product_option->uuid);

            if (!$option_price) {
                continue;
            }

            $group = $product_option->productOptionGroup;
            $group_key = $group->name ?: $group->key;
            $option_key = $product_option->name ?: $product_option->key;

            $order = 0;
            foreach ($product_option_groups as $product_option_group) {
                if ($product_option_group->uuid == $group->uuid) {
                    $order = $product_option_group->pivot->order;
                }
            }

            $price_data = [
                'setup' => $round($option_price->setup ?? 0),
                'base' => $round($option_price->base ?? 0),
                'idle' => $round($option_price->idle ?? 0),
                'switch_down' => $round($option_price->switch_down ?? 0),
                'switch_up' => $round($option_price->switch_up ?? 0),
                'uninstall' => $round($option_price->uninstall ?? 0),
            ];

            foreach ($price_total as $key => $val) {
                $price_total[$key] = $round($val + ($price_data[$key] ?? 0));
            }

            $option_prices[] = [
                'model' => $product_option,
                'product_option_group_key' => $group_key,
                'product_option_key' => $option_key,
                'order' => $order,
                'price' => $price_data,
            ];
        }

        usort($option_prices, fn($a, $b) => $a['order'] <=> $b['order']);

        $hourly_billing = $product->hourly_billing && $period === 'monthly';

        return [
            'currency' => [
                'code' => $currency->code,
                'prefix' => $currency->prefix,
                'suffix' => $currency->suffix,
            ],
            'period' => $period,
            'hourly_billing' => $hourly_billing,
            'service' => $service_price,
            'total' => $price_total,
            'options' => $option_prices,
        ];
    }

    public function generateAdminLabel(): void
    {
        do {
            $label = Str::upper(Str::random(6));
        } while (self::where('admin_label', $label)->exists());

        $this->admin_label = $label;
    }

    public function ensureProductOptions(): void
    {
        $price = $this->price;
        $product = $this->product;

        if (!$product || !$price) {
            return;
        }

        $current_option_uuids = $this->productOptions()->pluck('product_options.uuid')->toArray() ?? [];

        $option_groups = $product->productOptionGroups;

        foreach ($option_groups as $group) {
            $options = $group->productOptions()->orderBy('order')->get();

            if ($options->pluck('uuid')->intersect($current_option_uuids)->isNotEmpty()) {
                continue;
            }

            foreach ($options as $option) {
                $option_price = $option->prices()
                    ->where('period', $price->period)
                    ->where('currency_uuid', $price->currency_uuid)
                    ->first();

                if ($option_price) {
                    $this->productOptions()->attach($option->uuid);
                    break;
                }
            }
        }
    }

    public function updateProductOptions(array $uuids): void
    {
        $product = $this->product;
        $price = $this->price;

        if (!$product || !$price) {
            return;
        }

        $optionGroups = $product->productOptionGroups()->with('productOptions')->get();
        $currentOptions = $this->productOptions()->with('productOptionGroup')->get();

        foreach ($optionGroups as $group) {
            $newOption = $group->productOptions->firstWhere(function ($option) use ($uuids) {
                return in_array($option->uuid, $uuids);
            });

            $currentOption = $currentOptions->firstWhere(
                fn($opt) => $opt->productOptionGroup?->uuid === $group->uuid
            );

            if ($currentOption && $newOption && $currentOption->uuid === $newOption->uuid) {
                continue;
            }

            if ($currentOption && (!$newOption || $currentOption->uuid !== $newOption->uuid)) {
                $this->productOptions()->detach($currentOption->uuid);
            }

            if ($newOption && !$this->productOptions->contains($newOption->uuid)) {
                $this->productOptions()->attach($newOption->uuid);
            }
        }
    }

    public static function clientServicesByGroup(Client $client, string $groupUuid)
    {
        return self::query()
            ->where('client_uuid', $client->uuid)
            ->whereHas('product.productGroups', function ($query) use ($groupUuid) {
                $query->where('uuid', $groupUuid);
            });
    }

    // Module ----------------------------------------------------------------------------------------------------------
    public function getAdminAreaPage(): string
    {
        if (empty($this->module)) {
            return '<h1>'.__('error.The module is not available').'</h1>';
        }

        $data_array = $this->module->moduleExecute('getServicePage');

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
                'errors' => [__('error.Module not found')],
                'code' => 404,
            ];
        }

        $data_array = $this->module->moduleExecute('saveServiceData', $data);

        if ($data_array['status'] == 'error') {
            $data_array['code'] = $data_array['code'] ?? 500;

            return $data_array;
        }

        $this->setProvisionData($data_array['data']);

        return $data_array;
    }

    public function getModuleAttribute()
    {
        if ($this->product?->module) {
            $this->product->module->module->service = $this;

            return $this->product->module;
        }

        return null;
    }

    public function getClientAreaMenuConfig(): array
    {
        if ($this->status != 'active') {
            return ['general' => ['name' => __('main.General')]];
        }

        $menu = [];

        if (!empty($this->module)) {
            $result = $this->module->moduleExecute('getClientAreaMenuConfig');
            if ($result['status'] === 'success') {
                $menu = $result['data'];
            }
        }

        foreach ($menu as $key => &$item) {
            if (!isset($item['template'])) {
                unset($menu[$key]);

                continue;
            }

            $template = str_replace('.', '/', $item['template']);
            $templatePath = base_path('modules').'/Product/'.$this->module->name.'/views/'.$template.'.blade.php';

            if (file_exists($templatePath)) {
                $item['template'] = $templatePath;
            } else {
                unset($menu[$key]);
            }
        }

        if (isset($menu['general'])) {
            $menu['general']['name'] = __('main.General');
        } else {
            $menu['general'] = ['name' => __('main.General')];
        }

        return $menu;
    }

    public function getModuleVariables($tab): array
    {
        if ($this->status != 'active') {
            return [];
        }

        if (!empty($this->module)) {
            $result = $this->module->moduleExecute('variables_'.$tab);
            if ($result['status'] === 'success') {
                return $result['data'];
            }
        }

        return [];
    }

    public function apiClientModuleController(Request $request, $method)
    {
        if ($this->status != 'active') {
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ];
        }

        if (!empty($this->module)) {
            return $this->module->moduleExecute('controllerClient_'.$method, $request);
        }

        return [
            'status' => 'error',
            'errors' => [__('error.The module is not available')],
        ];
    }

    // Actions ---------------------------------------------------------------------------------------------------------
    public function runAction(string $action): array
    {
        if ($this->status !== 'manual') {
            return ['status' => 'error', 'errors' => [__('main.Status should be Manual')]];
        }

        if (!empty($this->module)) {
            $result = $this->module->moduleExecute($action);

            return $result;
        }

        return [
            'status' => 'error',
            'errors' => [__('error.The module is not available')],
        ];
    }

    public function createAfterPayment(): array
    {
        if ($this->status != 'pending') {
            return ['status' => 'error', 'errors' => ['Status should be pending']];
        }

        app(HookService::class)->callHooks('CreateService', ['service' => $this]);

        $this->activated_date = null;
        $this->create_error = null;
        $this->suspended_date = null;
        $this->suspended_reason = null;
        $this->terminated_date = null;
        $this->terminated_reason = null;
        $this->cancelled_date = null;
        $this->cancelled_reason = null;
        $this->termination_request = false;

        DB::beginTransaction();
        try {
            if (!empty($this->module)) {
                $this->setProvisionStatus('deploying');
                $result = $this->module->moduleExecute('create');

                if ($result['status'] === 'error') {
                    DB::rollBack();
                    if ($this->create_error !== implode(', ', $result['errors'] ?? [])) {
                        logActivity('error', 'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []), 'create', null, null, null, $this->client_uuid);
                        $this->create_error = implode(', ', $result['errors'] ?? []);
                        $this->save();
                        app(HookService::class)->callHooks('CreateServiceError', ['service' => $this]);
                    }
                    return $result;
                }
            }

            $this->status = 'active';
            $this->activated_date = now();
            $this->create_error = null;
            // billing_timestamp should be set by advanceServiceBilling, fallback to now
            if (! $this->billing_timestamp) {
                $this->billing_timestamp = now();
            }
            $this->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        logActivity('info', 'Service:'.$this->uuid.' Success (invoice mode)', 'create', null, null, null, $this->client_uuid);
        app(HookService::class)->callHooks('CreateServiceSuccess', ['service' => $this]);

        return ['status' => 'success', 'message' => __('message.Action successfully completed')];
    }

    public function create(): array
    {
        // Invoice-mode services must use createAfterPayment() after invoice is paid
        if ($this->billing_mode === 'invoice') {
            return ['status' => 'error', 'errors' => ['Invoice-mode services are created after payment']];
        }

        app(HookService::class)->callHooks('CreateService', ['service' => $this]);

        if ($this->status != 'pending') {
            if ($this->create_error !== 'Status should be pending') {
                $this->create_error = 'Status should be pending';
                $this->save();
                app(HookService::class)->callHooks('CreateServiceError', ['service' => $this]);
            }

            return ['status' => 'error', 'errors' => ['Status should be pending']];
        }

        $this->activated_date = null;
        $this->create_error = null;

        $this->suspended_date = null;
        $this->suspended_reason = null;

        $this->terminated_date = null;
        $this->terminated_reason = null;

        $this->cancelled_date = null;
        $this->cancelled_reason = null;

        $this->billing_timestamp = now();
        $this->termination_request = false;

        $priceTotal = $this->getPriceTotal();
        $setupAmount = $priceTotal['setup'] ?? 0;

        $chargeData = $this->calculateCharge();
        if (! $chargeData) {
            $this->create_error = 'Cannot calculate charge';
            $this->save();

            return ['status' => 'error', 'errors' => ['Cannot calculate charge']];
        }
        $totalCharge = abs($chargeData['amount']);

        $total = $totalCharge + abs($setupAmount);

        if (!$this->hasEnoughBalance($total)) {
            if ($this->create_error !== 'Insufficient funds') {
                logActivity(
                    'warning',
                    'Service:'.$this->uuid.'. Insufficient funds: cannot create service. Required: '.$total.' (Setup: '.$setupAmount.', Charge: '.$totalCharge.')',
                    'create',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );
                $this->create_error = 'Insufficient funds';
                $this->save();
                app(HookService::class)->callHooks('CreateServiceError', ['service' => $this]);
            }

            return ['status' => 'error', 'errors' => ['Insufficient funds']];
        }

        DB::beginTransaction();
        try {
            if ($setupAmount > 0) {
                $this->createTransaction(
                    -$setupAmount,
                    now(),
                    now(),
                    null,
                    null,
                    'Setup fee'
                );
            }

            if (!empty($this->module)) {
                $this->setProvisionStatus('deploying');
                $result = $this->module->moduleExecute('create');

                if ($result['status'] === 'error') {
                    DB::rollBack();

                    if ($this->create_error !== implode(', ', $result['errors'] ?? [])) {
                        logActivity(
                            'error',
                            'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []),
                            'create',
                            null,
                            null,
                            null,
                            $this->client_uuid
                        );
                        $this->create_error = implode(', ', $result['errors'] ?? []);
                        $this->save();
                        app(HookService::class)->callHooks('CreateServiceError', ['service' => $this]);
                    }

                    return $result;
                }
            }

            $this->status = 'active';
            $this->activated_date = now();
            $this->create_error = null;
            $this->chargeJob();
            $this->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        logActivity(
            'info',
            'Service:'.$this->uuid.' Success',
            'create',
            null,
            null,
            null,
            $this->client_uuid
        );

        app(HookService::class)->callHooks('CreateServiceSuccess', ['service' => $this]);

        return [
            'status' => 'success',
            'message' => __('message.Action successfully completed'),
        ];
    }

    public function retry_deploy(): array
    {

        if ($this->status != 'active') {
            return ['status' => 'error', 'errors' => ['Status should be pending']];
        }

        if ($this->provision_status != 'error') {
            return ['status' => 'error', 'errors' => ['Provision Status should be error']];
        }

        $this->create_error = null;

        $this->suspended_date = null;
        $this->suspended_reason = null;

        $this->terminated_date = null;
        $this->terminated_reason = null;

        $this->cancelled_date = null;
        $this->cancelled_reason = null;

        $this->termination_request = false;

        $this->setProvisionStatus('deploying');
        $result = $this->module->moduleExecute('retry_deploy');

        if ($result['status'] === 'error') {
            if ($this->create_error !== implode(', ', $result['errors'] ?? [])) {
                logActivity(
                    'error',
                    'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []),
                    'retry_deploy',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );
                $this->create_error = implode(', ', $result['errors'] ?? []);
                $this->save();
            }

            return $result;
        }

        $this->create_error = null;
        $this->save();

        logActivity(
            'info',
            'Service:'.$this->uuid.' Success',
            'retry_deploy',
            null,
            null,
            null,
            $this->client_uuid
        );

        return [
            'status' => 'success',
            'message' => __('message.Action successfully completed'),
        ];
    }

    public
    function suspend(): array
    {
        if ($this->status != 'active') {
            return ['status' => 'error', 'errors' => ['Status should be active']];
        }

        // This method is for prepaid services only
        if ($this->billing_mode === 'invoice') {
            return [];
        }

        $chargeData = $this->calculateCharge();
        if ($chargeData['period'] == 'one-time') {
            return ['status' => 'error', 'errors' => ['Period is one-time']];
        }

        if ($this->billing_timestamp && Carbon::parse($this->billing_timestamp)->isFuture()) {
            return [];
        }

        if (!$this->hasEnoughBalance(abs($chargeData['amount']))) {
            app(HookService::class)->callHooks('SuspendService', ['service' => $this]);

            if (!empty($this->module)) {
                $result = $this->module->moduleExecute('suspend');
                if ($result['status'] === 'error') {
                    logActivity(
                        'error',
                        'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []),
                        'suspend',
                        null,
                        null,
                        null,
                        $this->client_uuid
                    );
                    app(HookService::class)->callHooks('SuspendServiceError', ['service' => $this]);

                    return $result;
                }
            }

            $this->suspended_reason = 'Insufficient funds';
            $this->create_error = null;

            $this->status = 'suspended';
            $this->suspended_date = now();
            $this->save();

            logActivity(
                'info',
                'Service:'.$this->uuid.'. Insufficient funds. Required: '.abs($chargeData['amount']),
                'suspend',
                null,
                null,
                null,
                $this->client_uuid
            );

            app(HookService::class)->callHooks('SuspendServiceSuccess', ['service' => $this]);

            return [
                'status' => 'success',
                'message' => __('message.Action successfully completed'),
            ];
        }

        return [];
    }

    public function suspendInvoiceMode(): array
    {
        if ($this->status != 'active') {
            return ['status' => 'error', 'errors' => ['Status should be active']];
        }

        app(HookService::class)->callHooks('SuspendService', ['service' => $this]);

        if (!empty($this->module)) {
            $result = $this->module->moduleExecute('suspend');
            if ($result['status'] === 'error') {
                logActivity(
                    'error',
                    'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []),
                    'suspend',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );
                app(HookService::class)->callHooks('SuspendServiceError', ['service' => $this]);

                return $result;
            }
        }

        $this->suspended_reason = 'Unpaid invoice';
        $this->create_error = null;
        $this->status = 'suspended';
        $this->suspended_date = now();
        $this->save();

        logActivity(
            'info',
            'Service:'.$this->uuid.'. Unpaid invoice',
            'suspend',
            null,
            null,
            null,
            $this->client_uuid
        );

        app(HookService::class)->callHooks('SuspendServiceSuccess', ['service' => $this]);

        return [
            'status' => 'success',
            'message' => __('message.Action successfully completed'),
        ];
    }

    public
    function unsuspend(): array
    {
        if ($this->status !== 'suspended') {
            return ['status' => 'error', 'errors' => ['Status should be suspended']];
        }

        // This method is for prepaid services only
        if ($this->billing_mode === 'invoice') {
            return [];
        }

        $chargeData = $this->calculateCharge();
        if ($chargeData['period'] == 'one-time') {
            return ['status' => 'error', 'errors' => ['Period is one-time']];
        }

        if ($this->hasEnoughBalance(abs($chargeData['amount']))) {
            app(HookService::class)->callHooks('UnsuspendService', ['service' => $this]);

            if (!empty($this->module)) {
                $result = $this->module->moduleExecute('unsuspend');
                if ($result['status'] === 'error') {
                    logActivity(
                        'error',
                        'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []),
                        'unsuspend',
                        null,
                        null,
                        null,
                        $this->client_uuid
                    );
                    app(HookService::class)->callHooks('UnsuspendServiceError', ['service' => $this]);

                    return $result;
                }
            }

            $this->status = 'active';
            $this->suspended_date = null;
            $this->suspended_reason = null;
            $this->create_error = null;

            $this->billing_timestamp = now();
            $this->save();

            logActivity(
                'info',
                'Service:'.$this->uuid.' Unsuspend Successfully',
                'unsuspend',
                null,
                null,
                null,
                $this->client_uuid
            );

            app(HookService::class)->callHooks('UnsuspendServiceSuccess', ['service' => $this]);

            return [
                'status' => 'success',
                'message' => __('message.Action successfully completed'),
            ];
        }

        return [];
    }

    public function unsuspendInvoiceMode(): array
    {
        if ($this->status !== 'suspended') {
            return ['status' => 'error', 'errors' => ['Status should be suspended']];
        }

        app(HookService::class)->callHooks('UnsuspendService', ['service' => $this]);

        if (!empty($this->module)) {
            $result = $this->module->moduleExecute('unsuspend');
            if ($result['status'] === 'error') {
                logActivity(
                    'error',
                    'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []),
                    'unsuspend',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );
                app(HookService::class)->callHooks('UnsuspendServiceError', ['service' => $this]);

                return $result;
            }
        }

        $this->status = 'active';
        $this->suspended_date = null;
        $this->suspended_reason = null;
        $this->create_error = null;

        // Get billing_timestamp from the paid invoice item
        $paidItem = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
            ->where('invoice_items.service_uuid', $this->uuid)
            ->where('invoices.billing_purpose', 'service_billing')
            ->whereIn('invoices.status', ['paid', 'invoiced'])
            ->where('invoice_items.period_stop', '>', now())
            ->orderBy('invoice_items.period_stop', 'desc')
            ->first();

        if ($paidItem && $paidItem->period_stop) {
            $this->billing_timestamp = $paidItem->period_stop;
        } elseif (! $this->billing_timestamp) {
            $this->billing_timestamp = now();
        }

        $this->save();

        logActivity(
            'info',
            'Service:'.$this->uuid.' Unsuspend Successfully (invoice mode)',
            'unsuspend',
            null,
            null,
            null,
            $this->client_uuid
        );

        app(HookService::class)->callHooks('UnsuspendServiceSuccess', ['service' => $this]);

        return [
            'status' => 'success',
            'message' => __('message.Action successfully completed'),
        ];
    }

    public
    function termination(): array
    {
        $termination_time = $this->getTerminationTime();

        if (is_null($termination_time['seconds_left']) || $termination_time['seconds_left'] > 0) {
            return [];
        }

        app(HookService::class)->callHooks('TerminationService', ['service' => $this]);

        if (!empty($this->module)) {
            $result = $this->module->moduleExecute('termination');

            if ($result['status'] === 'error') {
                logActivity(
                    'error',
                    'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []),
                    'termination',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );
                app(HookService::class)->callHooks('TerminationServiceError', ['service' => $this]);

                return $result;
            }
        }

        $this->terminated_reason = 'Insufficient funds';
        if ($this->termination_request) {
            $this->terminated_reason = 'Termination Request';
        }

        $this->status = 'terminated';
        $this->terminated_date = now();
        $this->create_error = null;
        $this->save();

        // Clean up future unpaid invoices for this service (past unpaid invoices remain as debt)
        $this->cleanupInvoicesOnTermination();

        logActivity(
            'info',
            'Service:'.$this->uuid.'. '.$this->terminated_reason,
            'termination',
            null,
            null,
            null,
            $this->client_uuid
        );

        app(HookService::class)->callHooks('TerminationServiceSuccess', ['service' => $this]);

        return [
            'status' => 'success',
            'message' => __('message.Action successfully completed'),
        ];
    }

    public
    function cancellation(): array
    {
        if ($this->termination_request) {
            $termination_time = $this->getTerminationTime();
            if (is_null($termination_time['seconds_left']) || $termination_time['seconds_left'] > 0) {
                return [];
            }
        } else {
            $cancellation_time = $this->getCancellationTime();
            if (is_null($cancellation_time['seconds_left']) || $cancellation_time['seconds_left'] > 0) {
                return [];
            }
        }

        app(HookService::class)->callHooks('CancellationService', ['service' => $this]);

        if (!empty($this->module)) {
            $result = $this->module->moduleExecute('cancellation');
            if ($result['status'] === 'error') {
                logActivity(
                    'error',
                    'Service:'.$this->uuid.' '.implode(', ', $result['errors'] ?? []),
                    'cancellation',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );
                app(HookService::class)->callHooks('CancellationServiceError', ['service' => $this]);

                return $result;
            }
        }

        $this->cancelled_reason = 'Insufficient funds';
        if ($this->termination_request) {
            $this->cancelled_reason = 'Termination Request';
        }
        $this->status = 'cancelled';
        $this->cancelled_date = now();
        $this->create_error = null;
        $this->save();

        // Service was never activated — strip its items from any unpaid order invoice.
        // If the invoice becomes empty, cancel it entirely.
        $this->cleanupInvoicesOnCancellation();

        logActivity(
            'info',
            'Service:'.$this->uuid.'. '.$this->cancelled_reason,
            'cancellation',
            null,
            null,
            null,
            $this->client_uuid
        );

        app(HookService::class)->callHooks('CancellationServiceSuccess', ['service' => $this]);

        return [
            'status' => 'success',
            'message' => __('message.Action successfully completed'),
        ];
    }

    /**
     * Remove this service's items from any unpaid/draft order invoice on cancellation.
     *
     * Called when a pending service is cancelled without ever being activated — there is
     * no coverage period to preserve, so every item belonging to this service can be
     * removed from its order invoice(s). If an invoice becomes empty after removal, it
     * is cancelled outright (unpaid → cancel(), draft → status='canceled' directly).
     *
     * Only touches `service_billing` invoices in `draft`/`unpaid` status. Does NOT touch
     * paid/invoiced/canceled invoices, standalone invoices, or proration/upgrade invoices
     * (the latter can't exist on a pending-cancelled service anyway).
     */
    private function cleanupInvoicesOnCancellation(): void
    {
        $items = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
            ->where('invoice_items.service_uuid', $this->uuid)
            ->where('invoices.billing_purpose', 'service_billing')
            ->whereIn('invoices.status', ['draft', 'unpaid'])
            ->select('invoice_items.*')
            ->get();

        $affectedInvoiceUuids = $items->pluck('invoice_uuid')->unique();

        foreach ($items as $item) {
            $item->delete();
        }

        foreach ($affectedInvoiceUuids as $invoiceUuid) {
            $invoice = Invoice::find($invoiceUuid);
            if (! $invoice || $invoice->invoiceItems()->count() !== 0) {
                continue;
            }

            if ($invoice->status === 'unpaid') {
                $invoice->cancel();
            } elseif ($invoice->status === 'draft') {
                $invoice->status = 'canceled';
                $invoice->canceled_date = now();
                $invoice->save();
            }
        }

        if ($affectedInvoiceUuids->isNotEmpty()) {
            logActivity(
                'info',
                'Service:'.$this->uuid.' removed from unpaid order invoices on cancellation',
                'cancellation',
                null,
                null,
                null,
                $this->client_uuid
            );
        }
    }

    private function cleanupInvoicesOnTermination(): void
    {
        // Remove this service's items from future unpaid invoices (period_stop >= terminated_date or NULL)
        // Past unpaid invoices (period_stop < terminated_date) remain as debt
        $futureItems = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
            ->where('invoice_items.service_uuid', $this->uuid)
            ->where('invoices.billing_purpose', 'service_billing')
            ->whereIn('invoices.status', ['draft', 'unpaid'])
            ->where(function ($q) {
                $q->where('invoice_items.period_stop', '>=', $this->terminated_date)
                    ->orWhereNull('invoice_items.period_stop');
            })
            ->select('invoice_items.*')
            ->get();

        $affectedInvoiceUuids = $futureItems->pluck('invoice_uuid')->unique();

        foreach ($futureItems as $item) {
            $item->delete();
        }

        foreach ($affectedInvoiceUuids as $invoiceUuid) {
            $invoice = Invoice::find($invoiceUuid);
            if ($invoice && $invoice->invoiceItems()->count() === 0) {
                $invoice->cancel();
            }
        }
    }

    public function removeFromUnpaidInvoices(): void
    {
        $items = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
            ->where('invoice_items.service_uuid', $this->uuid)
            ->where('invoices.billing_purpose', 'service_billing')
            ->whereIn('invoices.status', ['draft', 'unpaid'])
            ->select('invoice_items.*')
            ->get();

        $affectedInvoiceUuids = $items->pluck('invoice_uuid')->unique();

        foreach ($items as $item) {
            $item->delete();
        }

        // Check if affected invoices are now empty — cancel them
        foreach ($affectedInvoiceUuids as $invoiceUuid) {
            $invoice = Invoice::find($invoiceUuid);
            if ($invoice && $invoice->invoiceItems()->count() === 0) {
                $invoice->cancel();
            }
        }

        if ($affectedInvoiceUuids->isNotEmpty()) {
            logActivity(
                'info',
                'Service:'.$this->uuid.' removed from unpaid invoices on termination request',
                'termination_request',
                null,
                null,
                null,
                $this->client_uuid
            );
        }
    }

    public function switchBillingMode(string $newMode): array
    {
        // Validate
        if (! in_array($newMode, ['prepaid', 'invoice'])) {
            return ['status' => 'error', 'errors' => ['Invalid billing mode']];
        }

        if ($this->billing_mode === $newMode) {
            return ['status' => 'error', 'errors' => ['Service is already in '.$newMode.' mode']];
        }

        if ($this->status !== 'active') {
            return ['status' => 'error', 'errors' => ['Service must be active to switch billing mode']];
        }

        if ($this->product->billing_mode !== 'both') {
            return ['status' => 'error', 'errors' => ['Product does not allow billing mode switching']];
        }

        $oldMode = $this->billing_mode;

        // Invoice → Prepaid: cancel unpaid invoice items for this service
        if ($oldMode === 'invoice' && $newMode === 'prepaid') {
            try {
                $this->removeFromUnpaidInvoices();
            } catch (\Throwable $e) {
                logActivity(
                    'error',
                    'Service:'.$this->uuid.' Failed to remove from unpaid invoices: '.$e->getMessage(),
                    'billing_mode_switch',
                    null, null, null,
                    $this->client_uuid
                );
            }
        }

        $this->billing_mode = $newMode;
        $this->save();

        // Prepaid → Invoice: generate invoice if needed (cron will also handle it)
        if ($oldMode === 'prepaid' && $newMode === 'invoice') {
            try {
                $this->generateServiceInvoice();
            } catch (\Throwable $e) {
                logActivity(
                    'error',
                    'Service:'.$this->uuid.' Failed to generate invoice after switch: '.$e->getMessage(),
                    'billing_mode_switch',
                    null, null, null,
                    $this->client_uuid
                );
            }
        }

        logActivity(
            'info',
            'Service:'.$this->uuid.' billing mode switched from '.$oldMode.' to '.$newMode,
            'billing_mode_switch',
            null, null, null,
            $this->client_uuid
        );

        // Send notification
        try {
            \App\Models\Task::add('ClientNotificationJob', 'ClientNotification', [
                'client_uuid' => $this->client_uuid,
                'category' => 'clientOperational',
                'notification' => 'Client Billing Mode Changed',
                'data' => ['service' => $this, 'old_mode' => $oldMode, 'new_mode' => $newMode],
            ], ['ClientNotification']);
        } catch (\Throwable $e) {
            // Notification failure should not block the switch
        }

        app(HookService::class)->callHooks('ServiceBillingModeChanged', [
            'service' => $this,
            'old_mode' => $oldMode,
            'new_mode' => $newMode,
        ]);

        return [
            'status' => 'success',
            'message' => 'Billing mode switched to '.$newMode,
        ];
    }

    public function generateServiceInvoice(): ?Invoice
    {
        $client = $this->client;
        if (! $client) {
            return null;
        }

        $pricePeriod = $this->price->period ?? null;
        if (! $pricePeriod) {
            return null;
        }

        $homeCompany = $client->getHomeCompany();
        if (! $homeCompany) {
            return null;
        }

        $invoiceUuid = DB::transaction(function () use ($client, $pricePeriod, $homeCompany) {
            // Lock the service row to prevent duplicate invoice generation
            $locked = self::where('uuid', $this->uuid)->lockForUpdate()->first();
            if (! $locked) {
                return null;
            }

            // Check if unpaid invoice already exists (under lock)
            $alreadyInvoiced = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
                ->where('invoice_items.service_uuid', $this->uuid)
                ->where('invoices.billing_purpose', 'service_billing')
                ->whereIn('invoices.status', ['draft', 'unpaid'])
                ->exists();

            if ($alreadyInvoiced) {
                return null;
            }

            $chargeData = $this->calculateCharge();
            if (! $chargeData && $pricePeriod !== 'one-time') {
                return null;
            }

            $invoiceDueDays = (int) \App\Services\SettingService::get('finance.invoice_due_days');
            $taxes = $client->getTaxes();

            $invoice = new Invoice;
            $invoice->type = 'proforma';
            $invoice->status = 'draft';
            $invoice->billing_purpose = 'service_billing';
            $invoice->client_uuid = $client->uuid;
            $invoice->home_company_uuid = $homeCompany->uuid;

            $index = 1;
            foreach ($taxes as $tax) {
                if ($index > 3) {
                    break;
                }
                $invoice->{"tax_$index"} = $tax['rate'];
                $invoice->{"tax_{$index}_name"} = $tax['name'];
                $index++;
            }

            $invoice->save();
            $invoice->refresh();

            $priceTotal = $this->getPriceTotal();
            $chargeAmount = $this->idle ? $priceTotal['idle'] : $priceTotal['base'];
            $productName = $this->product->name;
            if (empty($productName)) {
                $productName = $this->product->key;
            }
            $productGroup = $this->product->productGroups()->first();
            $productGroupName = $productGroup ? $productGroup->name : '';
            if (empty($productGroupName)) {
                $productGroupName = '';
            }
            $clientLabel = $this->admin_label ?? '';

            // Build description: Group / Product Name (client label)
            $descParts = [];
            if ($productGroupName) {
                $descParts[] = $productGroupName;
            }
            $descParts[] = $productName;
            $description = implode(' / ', $descParts);
            if ($clientLabel) {
                $description .= ' ('.$clientLabel.')';
            }

            if ($pricePeriod === 'one-time') {
                $description .= PHP_EOL.'*-One-time';
                $item = new InvoiceItem;
                $item->invoice_uuid = $invoice->uuid;
                $item->description = $description;
                $item->taxed = true;
                $item->service_uuid = $this->uuid;
                $item->amount = abs($chargeAmount);
                $item->save();
            } else {
                $periodStart = \Carbon\Carbon::parse($this->billing_timestamp);
                $periodStop = $chargeData['to'];

                $description .= PHP_EOL.'*-'.$periodStart->format('d.m.Y').' - '.$periodStop->format('d.m.Y');
                $description .= ' ('.$pricePeriod.')';

                $item = new InvoiceItem;
                $item->invoice_uuid = $invoice->uuid;
                $item->description = $description;
                $item->taxed = true;
                $item->service_uuid = $this->uuid;
                $item->period_start = $periodStart;
                $item->period_stop = $periodStop;
                $item->amount = abs($chargeAmount);
                $item->save();
            }

            // Setup fee
            if (is_null($this->activated_date) && $priceTotal['setup'] > 0) {
                $setupDesc = implode(' / ', array_filter([$productGroupName, $productName]));
                if ($clientLabel) {
                    $setupDesc .= ' ('.$clientLabel.')';
                }
                $setupDesc .= PHP_EOL.'*-Setup Fee';

                $setupItem = new InvoiceItem;
                $setupItem->invoice_uuid = $invoice->uuid;
                $setupItem->description = $setupDesc;
                $setupItem->taxed = true;
                $setupItem->service_uuid = $this->uuid;
                $setupItem->amount = $priceTotal['setup'];
                $setupItem->save();
            }

            // Set dates
            $invoice->issue_date = now();
            $invoice->due_date = now()->addDays($invoiceDueDays);
            $invoice->save();

            return $invoice->uuid;
        });

        if (! $invoiceUuid) {
            return null;
        }

        // Publish outside transaction (setNumber uses lockForUpdate on HomeCompany)
        $invoice = Invoice::find($invoiceUuid);
        if ($invoice && $invoice->status === 'draft') {
            $result = $invoice->publish();
            if ($result['status'] === 'success') {
                $invoice->refresh();
                app(HookService::class)->callHooks('ServiceInvoiceCreated', ['invoice' => $invoice]);
            }
        }

        return $invoice;
    }

// Finance
    public
    function calculateCharge(): ?array
    {
        $priceTotal = $this->getPriceTotal();
        $chargeAmount = $this->idle ? $priceTotal['idle'] : $priceTotal['base'];
        $price = $this->price;
        if (! $price) {
            return null;
        }
        $period = $price->period;
        $hourlyBilling = $this->product->hourly_billing ?? false;

        $now = now();

        if ($period === 'one-time') {
            return [
                'period' => 'one-time',
                'amount' => -abs($chargeAmount),
                'from' => $now->copy()->subSecond(),
                'to' => $now,
                'hours' => null,
                'rate' => null,
            ];
        }

        if (! $this->billing_timestamp) {
            return null;
        }
        $billingTimestamp = \Carbon\Carbon::parse($this->billing_timestamp);

        if ($period === 'monthly' && $hourlyBilling) {
            $nextBillingTimestamp = $billingTimestamp->copy()->addHour();
            while ($nextBillingTimestamp->lessThanOrEqualTo($now)) {
                $billingTimestamp = $nextBillingTimestamp->copy();
                $nextBillingTimestamp->addHour();
            }

            $hoursToCharge = $this->billing_timestamp !== $billingTimestamp->toDateTimeString()
                ? \Carbon\Carbon::parse($this->billing_timestamp)->diffInHours($nextBillingTimestamp)
                : 1;

            $hoursInMonth = $billingTimestamp->daysInMonth * 24;
            $hourlyRate = round($chargeAmount / $hoursInMonth, 4, PHP_ROUND_HALF_UP);
            $amountToCharge = round(-abs($hourlyRate) * $hoursToCharge, 4, PHP_ROUND_HALF_UP);

            return [
                'period' => 'monthly',
                'amount' => $amountToCharge,
                'from' => \Carbon\Carbon::parse($this->billing_timestamp),
                'to' => $nextBillingTimestamp,
                'hours' => $hoursToCharge,
                'rate' => $hourlyRate,
            ];
        }

        $nextBillingTimestamp = match ($period) {
            'monthly' => $billingTimestamp->copy()->addMonth(),
            'quarterly' => $billingTimestamp->copy()->addMonths(3),
            'semi-annually' => $billingTimestamp->copy()->addMonths(6),
            'annually' => $billingTimestamp->copy()->addYear(),
            'biennially' => $billingTimestamp->copy()->addYears(2),
            'triennially' => $billingTimestamp->copy()->addYears(3),
            'weekly' => $billingTimestamp->copy()->addWeek(),
            'bi-weekly' => $billingTimestamp->copy()->addWeeks(2),
            'daily' => $billingTimestamp->copy()->addDay(),
            default => null
        };

        if ($nextBillingTimestamp) {
            return [
                'period' => $period,
                'amount' => -abs($chargeAmount),
                'from' => \Carbon\Carbon::parse($this->billing_timestamp),
                'to' => $nextBillingTimestamp,
                'hours' => null,
                'rate' => null,
            ];
        }

        return null;
    }

    private
    function hasEnoughBalance(
        float $amount
    ): bool {
        $client = $this->client;
        if (! $client) {
            return false;
        }
        $balance = $client->balance->balance ?? 0;
        $creditLimit = $client->credit_limit ?? 0;

        return ($balance + $creditLimit) >= $amount;
    }

    protected
    function createTransaction(
        float $amount,
        $periodStart,
        $periodStop,
        ?int $hours = null,
        ?float $rate = null,
        $note = null,
        $type = 'charge'
    ): void {
        $description = "Service:{$this->uuid}";

        if ($note !== null) {
            $description .= ", {$note}";
        }

        if ($hours !== null && $rate !== null) {
            if ($hours <= 0) {
                $description .= ", >1h × {$rate} per hour";
            } else {
                $description .= ", {$hours}h × {$rate} per hour";
            }
        }

        Transaction::create([
            'client_uuid' => $this->client_uuid,
            'type' => $type,
            'amount_net' => $amount,
            'amount_gross' => $amount,
            'fees' => 0.00,
            'description' => $description,
            'relation_model' => get_class($this),
            'relation_model_uuid' => $this->uuid,
            'transaction_date' => now(),
            'period_start' => $periodStart,
            'period_stop' => $periodStop,
        ]);

    }

    public
    function chargeJob(): void
    {
        if ($this->status != 'active') {
            return;
        }

        $chargeData = $this->calculateCharge();

        if (!$chargeData) {
            return;
        }

        DB::transaction(function () use ($chargeData) {
            $locked = self::where('uuid', $this->uuid)->lockForUpdate()->first();
            if (!$locked || $locked->status != 'active') {
                return;
            }

            if ($locked->billing_timestamp != $this->billing_timestamp) {
                return;
            }

            if ($locked->hasEnoughBalance(abs($chargeData['amount']))) {
                $locked->createTransaction(
                    $chargeData['amount'],
                    $chargeData['from'],
                    $chargeData['to'],
                    $chargeData['hours'] ?? null,
                    $chargeData['rate'] ?? null
                );
                $locked->billing_timestamp = $chargeData['to'];
                $locked->save();
                $this->billing_timestamp = $locked->billing_timestamp;

                logActivity(
                    'info',
                    'Service:'.$this->uuid.' charged. Amount: '.abs($chargeData['amount']).', Period: '.$chargeData['period'].', From: '.$chargeData['from'].' To: '.$chargeData['to'].($chargeData['hours'] ? ', Hours: '.$chargeData['hours'] : ''),
                    'charge',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );
            } else {
                logActivity(
                    'warning',
                    'Service:'.$this->uuid.'. Insufficient funds: cannot charge service. Required: '.abs($chargeData['amount']).', Period: '.$chargeData['period'].', From: '.$chargeData['from'].' To: '.$chargeData['to'],
                    'charge',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );
            }
        });
    }

    public
    function getTerminationTime(): array
    {
        $product = $this->product;
        $terminationDelayHours = $product->termination_delay_hours;
        $suspendedDate = $this->suspended_date;
        $now = Carbon::now();

        if ($this->termination_request) {
            $billingTimestamp = $this->billing_timestamp;
            if ($billingTimestamp) {
                $terminationAt = Carbon::parse($billingTimestamp);
                $secondsLeft = $terminationAt->greaterThan($now)
                    ? $now->diffInSeconds($terminationAt)
                    : 0;

                return [
                    'termination_at' => $terminationAt->toDateTimeString(),
                    'seconds_left' => (int) $secondsLeft,
                    'status' => 'scheduled',
                ];
            }

            return [
                'termination_at' => Carbon::now()->toDateTimeString(),
                'seconds_left' => 0,
                'status' => 'scheduled',
            ];
        }

        if (!$suspendedDate || !$terminationDelayHours) {
            return [
                'termination_at' => null,
                'seconds_left' => null,
                'status' => 'not_scheduled',
            ];
        }

        $terminationAt = Carbon::parse($suspendedDate)->addHours($terminationDelayHours);
        $secondsLeft = $terminationAt->greaterThan($now)
            ? $now->diffInSeconds($terminationAt)
            : 0;

        return [
            'termination_at' => $terminationAt->toDateTimeString(),
            'seconds_left' => (int) $secondsLeft,
            'status' => 'scheduled',
        ];
    }

    public
    function getCancellationTime(): array
    {
        $product = $this->product;
        $cancellationDelayHours = $product->cancellation_delay_hours;
        $orderDate = Carbon::parse($this->order_date);
        $activatedDate = $this->activated_date;
        $now = Carbon::now();

        if (!empty($activatedDate)) {
            return [
                'cancellation_at' => null,
                'seconds_left' => null,
                'status' => 'not_scheduled',
            ];
        }

        if ($orderDate->diffInMinutes($now) < 5) {
            return [
                'cancellation_at' => null,
                'seconds_left' => null,
                'status' => 'waiting_automation',
            ];
        }

        $cancellationAt = $orderDate->addHours($cancellationDelayHours);
        $secondsLeft = $cancellationAt->greaterThan($now)
            ? $now->diffInSeconds($cancellationAt)
            : 0;

        return [
            'cancellation_at' => $cancellationAt->toDateTimeString(),
            'seconds_left' => (int) $secondsLeft,
            'status' => 'scheduled',
        ];
    }

    public
    function getUpdateDowngradeProductOptions(): array
    {
        $price = $this->price;
        $client = $this->client;
        $currency = $client->currency;

        $product_options_by_product_option_group_uuid = [];
        $update_downgrade_product_options = [];
        $product_options = $this->productOptions;

        foreach ($product_options as $product_option) {
            $product_option->price = $product_option->getPrices($price->period, $currency->code);
            $product_options_by_product_option_group_uuid[$product_option->product_option_group_uuid]['current'] = $product_option;
        }

        $product_product_option_groups = $this->product
            ->productOptionGroups()
            ->orderBy('pivot_order')
            ->get();

        foreach ($product_product_option_groups as $product_product_option_group) {
            $tmp = [
                'product_option_group' => $product_product_option_group,
            ];
            $tmp['up'] = [];
            $tmp['down'] = [];

            if (!empty($product_options_by_product_option_group_uuid[$product_product_option_group->uuid])) {
                $current_product_option = $product_options_by_product_option_group_uuid[$product_product_option_group->uuid]['current'];
            } else {
                $current_product_option = $product_product_option_group
                    ->productOptions()
                    ->orderBy('order')
                    ->first();
                $current_product_option->price = $current_product_option->getPrices($price->period, $currency->code);
            }

            $tmp['current'] = $current_product_option;
            $all_product_options = $product_product_option_group
                ->productOptions()
                ->orderBy('order')
                ->get();

            foreach ($all_product_options as $product_option) {
                $product_option->price = $product_option->getPrices($price->period, $currency->code);
                if (!empty($product_option->price)) {
                    if ($product_option->order > $current_product_option->order) {
                        if (isset($product_option->price['switch_up'])) {
                            $tmp['up'][] = $product_option;
                        }
                    }

                    if ($product_option->order < $current_product_option->order) {
                        if (isset($product_option->price['switch_down'])) {
                            $tmp['down'][] = $product_option;
                        }
                    }

                }

            }
            $update_downgrade_product_options[] = $tmp;
        }

        return $update_downgrade_product_options;
    }

    public
    function setUpdateDowngradeProductOptions(
        array $new_product_options
    ): array {
        DB::beginTransaction();
        try {
            $price_total = $this->getPriceTotal();
            $old_price = $price_total['base'] ?? 0.00;
            $switch_fee = 0;

            $new_price = $old_price;
            $update_downgrade_product_options = $this->getUpdateDowngradeProductOptions();

            foreach ($update_downgrade_product_options as $update_downgrade_product_option) {

                $product_option_group_uuid = $update_downgrade_product_option['product_option_group']->uuid;
                if (empty($new_product_options[$product_option_group_uuid])) {
                    continue;
                }

                $new_product_option_uuid = $new_product_options[$product_option_group_uuid];
                $current_product_option = $update_downgrade_product_option['current'];

                if ($new_product_option_uuid == $current_product_option->uuid) {
                    continue;
                }

                foreach ($update_downgrade_product_option['up'] as $o_up) {
                    if ($o_up->uuid == $new_product_option_uuid) {
                        $new_price = $new_price - ($current_product_option->price['base'] ?? 0) + ($o_up->price['base'] ?? 0);
                        $switch_fee += $o_up->price['switch_up'] ?? 0;
                    }
                }

                foreach ($update_downgrade_product_option['down'] as $o_down) {
                    if ($o_down->uuid == $new_product_option_uuid) {
                        $new_price = $new_price - ($current_product_option->price['base'] ?? 0) + ($o_down->price['base'] ?? 0);
                        $switch_fee += $o_down->price['switch_down'] ?? 0;
                    }
                }
            }

            $correction = $this->calculateProductOptionsCorrection($old_price, $new_price);
            $total = $correction['correction'] + abs($switch_fee);

            if (!$this->hasEnoughBalance($total)) {
                logActivity(
                    'warning',
                    'Service:'.$this->uuid.'. Insufficient funds for Update/Downgrade. Required: '.$total.' (Correction: '.$correction['correction'].', Switch fee: '.$switch_fee.')',
                    'update_downgrade',
                    null,
                    null,
                    null,
                    $this->client_uuid
                );

                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['Insufficient funds for Update/Downgrade service'],
                ];
            }

            if ($switch_fee > 0) {
                $this->createTransaction(-$switch_fee, now(), now(), null, null, 'Switch fee');
            }

            if ($correction['correction'] != 0 && ($correction['remaining_hours'] ?? 0) > 0) {
                $hour_price = $correction['correction'] / $correction['remaining_hours'];
                $this->createTransaction(
                    -$correction['correction'],
                    $correction['now'] ?? now(),
                    $correction['end'] ?? now(),
                    $correction['remaining_hours'] ?? null,
                    $hour_price ?? null,
                    'Switch',
                    'adjustment'
                );
            }

            $product = $this->product;
            foreach ($new_product_options as $product_option_group_uuid => $new_product_option_uuid) {
                if ($product->hasProductOption($product_option_group_uuid, $new_product_option_uuid)) {
                    $current_options = $this->productOptions()->where('product_option_group_uuid',
                        $product_option_group_uuid)->pluck('uuid');
                    if ($current_options->isNotEmpty()) {
                        $this->productOptions()->detach($current_options);
                    }
                    $this->productOptions()->attach($new_product_option_uuid);
                }
            }

            DB::commit();

            logActivity(
                'info',
                'Service:'.$this->uuid.' options updated. Old price: '.$old_price.', New price: '.$new_price.', Correction: '.$correction['correction'].', Switch fee: '.$switch_fee,
                'update_downgrade',
                null,
                null,
                null,
                $this->client_uuid
            );

            app(HookService::class)->callHooks('ServiceOptionsUpdated', ['service' => $this]);

            return [
                'status' => 'success',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            logActivity(
                'error',
                'Service:'.$this->uuid.' options update error: '.$e->getMessage(),
                'update_downgrade',
                null,
                null,
                null,
                $this->client_uuid
            );

            return [
                'status' => 'error',
                'errors' => [__('error.Something went wrong')],
                'code' => 500,
            ];
        }
    }

    public
    function calculateProductOptionsCorrection(
        $old_price,
        $new_price
    ): array {
        $price = $this->price;
        $period = $price->period;
        $now = Carbon::now();
        $billing = $this->billing_timestamp;
        if ($billing instanceof Carbon) {
            $end = $billing->copy();
        } elseif ($billing instanceof \DateTimeInterface) {
            $end = Carbon::instance($billing);
        } elseif (is_numeric($billing)) {
            $billing = (string) $billing;
            if (strlen($billing) >= 13) {
                $end = Carbon::createFromTimestamp((int) floor((int) $billing / 1000));
            } else {
                $end = Carbon::createFromTimestamp((int) $billing);
            }
        } else {
            $end = Carbon::parse($billing);
        }

        $start = match ($period) {
            'daily' => $end->copy()->subDay(),
            'weekly' => $end->copy()->subWeek(),
            'bi-weekly' => $end->copy()->subWeeks(2),
            'monthly' => $end->copy()->subMonth(),
            'quarterly' => $end->copy()->subMonths(3),
            'semi-annually' => $end->copy()->subMonths(6),
            'annually' => $end->copy()->subYear(),
            'biennially' => $end->copy()->subYears(2),
            'triennially' => $end->copy()->subYears(3),
            default => null,
        };

        if (!$start) {
            return [
                'correction' => 0,
                'error' => 'Unknown period',
            ];
        }

        $total_hours = $start->diffInHours($end);
        $remaining_hours = $now->lt($end) ? $now->diffInHours($end) : 0;
        $used_hours = $total_hours - $remaining_hours;

        if ($total_hours <= 0 || $remaining_hours <= 0) {
            return [
                'correction' => 0,
                'total_hours' => $total_hours,
                'used_hours' => $used_hours,
                'remaining_hours' => $remaining_hours,
                'note' => 'Billing period finished',
            ];
        }

        $old_hour_price = $old_price / $total_hours;
        $new_hour_price = $new_price / $total_hours;

        $remaining_old = $old_hour_price * $remaining_hours;
        $remaining_new = $new_hour_price * $remaining_hours;

        return [
            'correction' => $remaining_new - $remaining_old,
            'total_hours' => $total_hours,
            'used_hours' => $used_hours,
            'remaining_hours' => $remaining_hours,
            'old_hour_price' => $old_hour_price,
            'new_hour_price' => $new_hour_price,
            'remaining_old' => $remaining_old,
            'remaining_new' => $remaining_new,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'now' => $now->toDateTimeString(),
        ];
    }

    /**
     * Advance a date by one billing period.
     */
    public static function advanceByPeriod(Carbon $date, string $period): Carbon
    {
        return match ($period) {
            'monthly' => $date->copy()->addMonth(),
            'quarterly' => $date->copy()->addMonths(3),
            'semi-annually' => $date->copy()->addMonths(6),
            'annually' => $date->copy()->addYear(),
            'biennially' => $date->copy()->addYears(2),
            'triennially' => $date->copy()->addYears(3),
            'weekly' => $date->copy()->addWeek(),
            'bi-weekly' => $date->copy()->addWeeks(2),
            'daily' => $date->copy()->addDay(),
            default => $date->copy(),
        };
    }

    /**
     * Subtract one billing period from a date.
     */
    public static function subtractPeriod(Carbon $date, string $period): Carbon
    {
        return match ($period) {
            'monthly' => $date->copy()->subMonth(),
            'quarterly' => $date->copy()->subMonths(3),
            'semi-annually' => $date->copy()->subMonths(6),
            'annually' => $date->copy()->subYear(),
            'biennially' => $date->copy()->subYears(2),
            'triennially' => $date->copy()->subYears(3),
            'weekly' => $date->copy()->subWeek(),
            'bi-weekly' => $date->copy()->subWeeks(2),
            'daily' => $date->copy()->subDay(),
            default => $date->copy(),
        };
    }

    /**
     * Get products available for upgrade/downgrade (same module, not retired).
     */
    public function getUpgradeableProducts(): \Illuminate\Support\Collection
    {
        $currentProduct = $this->product;
        if (! $currentProduct || ! $currentProduct->module_uuid) {
            return collect();
        }

        return Product::where('module_uuid', $currentProduct->module_uuid)
            ->where('uuid', '!=', $currentProduct->uuid)
            ->where('retired', false)
            ->where('hidden', false)
            ->get();
    }

    /**
     * Calculate proration for upgrade or downgrade to a new product/price.
     *
     * @param  array  $newOptionUuids  Selected option UUIDs for the new product
     * @return array Proration calculation details
     */
    public function calculateUpgradeProration(Product $newProduct, Price $newPrice, array $newOptionUuids = []): array
    {
        $currentPrice = $this->price;
        if (! $currentPrice) {
            return ['status' => 'error', 'errors' => ['Service has no valid price']];
        }

        if (! $this->billing_timestamp) {
            return ['status' => 'error', 'errors' => ['Service has no billing timestamp']];
        }

        $now = Carbon::now();
        $billingEnd = Carbon::parse($this->billing_timestamp);

        // Must have future billing_timestamp
        if ($billingEnd->lte($now)) {
            return [
                'status' => 'error',
                'errors' => ['Billing period has ended, cannot prorate'],
            ];
        }

        // Must be same period
        if ($currentPrice->period !== $newPrice->period) {
            return [
                'status' => 'error',
                'errors' => ['Cannot change between different billing periods'],
            ];
        }

        $period = $currentPrice->period;
        if ($period === 'one-time') {
            return [
                'status' => 'error',
                'errors' => ['Cannot upgrade one-time services'],
            ];
        }

        // Calculate period boundaries
        $billingStart = self::subtractPeriod($billingEnd, $period);
        $totalSeconds = $billingStart->diffInSeconds($billingEnd);
        $remainingSeconds = $now->diffInSeconds($billingEnd);
        $remainingFraction = $totalSeconds > 0 ? $remainingSeconds / $totalSeconds : 0;

        // Current total price (base + options)
        $currentTotal = $this->getPriceTotal();
        $currentBaseAmount = $this->idle ? $currentTotal['idle'] : $currentTotal['base'];

        // New total price (base + selected options)
        $newBaseAmount = (float) ($newPrice->base ?? 0);
        if (! empty($newOptionUuids)) {
            $optionPrices = DB::table('product_option_x_price')
                ->join('prices', 'prices.uuid', '=', 'product_option_x_price.price_uuid')
                ->whereIn('product_option_x_price.product_option_uuid', $newOptionUuids)
                ->where('prices.currency_uuid', $newPrice->currency_uuid)
                ->where('prices.period', $newPrice->period)
                ->sum('prices.base');
            $newBaseAmount += (float) $optionPrices;
        }

        $oldRemainingValue = round($currentBaseAmount * $remainingFraction, 2);

        if ($newBaseAmount > $currentBaseAmount) {
            // UPGRADE
            $newRemainingCost = round($newBaseAmount * $remainingFraction, 2);
            $difference = round($newRemainingCost - $oldRemainingValue, 2);
            $switchFee = $currentTotal['switch_up'] ?? 0;

            return [
                'status' => 'success',
                'direction' => 'upgrade',
                'old_price_amount' => $currentBaseAmount,
                'new_price_amount' => $newBaseAmount,
                'remaining_fraction' => round($remainingFraction, 6),
                'credit' => $oldRemainingValue,
                'new_remaining_cost' => $newRemainingCost,
                'difference' => $difference,
                'switch_fee' => (float) $switchFee,
                'total_to_pay' => round($difference + $switchFee, 2),
                'new_billing_timestamp' => $billingEnd->toDateTimeString(),
                'period' => $period,
                'period_start' => $billingStart->toDateTimeString(),
                'period_end' => $billingEnd->toDateTimeString(),
                'full_periods_covered' => 0,
            ];
        } else {
            // DOWNGRADE
            $credit = $oldRemainingValue;
            $newPeriodPrice = $newBaseAmount;
            $switchFee = (float) ($currentTotal['switch_down'] ?? 0);

            if ($newPeriodPrice <= 0) {
                return [
                    'status' => 'error',
                    'errors' => ['New price cannot be zero'],
                ];
            }

            $fullPeriods = (int) floor($credit / $newPeriodPrice);
            $remainingCredit = round($credit - ($fullPeriods * $newPeriodPrice), 2);
            $remainingCredit = min($remainingCredit, $newPeriodPrice); // guard against float rounding
            $partialPayment = round(max(0, $newPeriodPrice - $remainingCredit), 2);

            // Calculate new billing_timestamp
            $newBillingTimestamp = $now->copy();
            for ($i = 0; $i < $fullPeriods + 1; $i++) {
                $newBillingTimestamp = self::advanceByPeriod($newBillingTimestamp, $period);
            }

            return [
                'status' => 'success',
                'direction' => 'downgrade',
                'old_price_amount' => $currentBaseAmount,
                'new_price_amount' => $newBaseAmount,
                'remaining_fraction' => round($remainingFraction, 6),
                'credit' => $credit,
                'full_periods_covered' => $fullPeriods,
                'remaining_credit' => $remainingCredit,
                'partial_payment' => $partialPayment,
                'switch_fee' => $switchFee,
                'total_to_pay' => round($partialPayment + $switchFee, 2),
                'new_billing_timestamp' => $newBillingTimestamp->toDateTimeString(),
                'period' => $period,
                'period_start' => $billingStart->toDateTimeString(),
                'period_end' => $billingEnd->toDateTimeString(),
            ];
        }
    }

    /**
     * Calculate proration to align billing to a specific day of month.
     */
    public function calculateProrationAlignment(int $targetDay): array
    {
        if (! $this->price || ! $this->billing_timestamp) {
            return ['status' => 'error', 'errors' => ['Service has no valid price or billing date']];
        }

        $now = Carbon::now();
        $billingEnd = Carbon::parse($this->billing_timestamp);
        $period = $this->price->period;

        if ($period === 'one-time') {
            return ['status' => 'error', 'errors' => ['Cannot prorate one-time services']];
        }

        // Find next target date after billing_timestamp
        $targetDate = $billingEnd->copy()->day(min($targetDay, $billingEnd->daysInMonth));
        if ($targetDate->lte($billingEnd)) {
            $targetDate->addMonth();
            $targetDate->day(min($targetDay, $targetDate->daysInMonth));
        }

        // Calculate proration amount
        $periodStart = self::subtractPeriod($billingEnd, $period);
        $periodEnd = $billingEnd;
        $totalSeconds = $periodStart->diffInSeconds($periodEnd);
        $prorationSeconds = $billingEnd->diffInSeconds($targetDate);
        $prorationFraction = $totalSeconds > 0 ? $prorationSeconds / $totalSeconds : 0;

        $currentTotal = $this->getPriceTotal();
        $baseAmount = $this->idle ? $currentTotal['idle'] : $currentTotal['base'];
        $prorationAmount = round($baseAmount * $prorationFraction, 2);

        return [
            'status' => 'success',
            'from' => $billingEnd->toDateTimeString(),
            'to' => $targetDate->toDateTimeString(),
            'days' => $billingEnd->diffInDays($targetDate),
            'proration_fraction' => round($prorationFraction, 6),
            'amount' => $prorationAmount,
            'new_billing_timestamp' => $targetDate->toDateTimeString(),
            'period' => $period,
        ];
    }

    /**
     * Generate invoice for upgrade/downgrade (invoice billing mode).
     */
    public function generateChangeInvoice(Product $newProduct, Price $newPrice, array $newOptionUuids = []): ?Invoice
    {
        $proration = $this->calculateUpgradeProration($newProduct, $newPrice, $newOptionUuids);
        if ($proration['status'] !== 'success') {
            return null;
        }

        $client = $this->client;
        if (! $client) {
            return null;
        }
        $homeCompany = $client->getHomeCompany();
        if (! $homeCompany) {
            return null;
        }

        $billingPurpose = $proration['direction'] === 'upgrade' ? 'service_upgrade' : 'service_downgrade';

        $invoiceUuid = DB::transaction(function () use ($client, $homeCompany, $proration, $newProduct, $newPrice, $newOptionUuids, $billingPurpose) {
            $locked = self::where('uuid', $this->uuid)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'active' || $locked->termination_request) {
                return null;
            }

            // Cancel any existing pending change/proration invoices for this service
            $existingInvoiceUuids = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
                ->where('invoice_items.service_uuid', $this->uuid)
                ->whereIn('invoices.billing_purpose', ['service_upgrade', 'service_downgrade', 'service_proration'])
                ->whereIn('invoices.status', ['draft', 'unpaid'])
                ->pluck('invoices.uuid')
                ->unique();

            foreach ($existingInvoiceUuids as $existingUuid) {
                $existingInvoice = Invoice::find($existingUuid);
                if ($existingInvoice) {
                    if ($existingInvoice->status === 'unpaid') {
                        $existingInvoice->cancel();
                    } elseif ($existingInvoice->status === 'draft') {
                        $existingInvoice->status = 'canceled';
                        $existingInvoice->canceled_date = now();
                        $existingInvoice->save();
                    }
                }
            }

            $invoiceDueDays = (int) \App\Services\SettingService::get('finance.invoice_due_days');
            $taxes = $client->getTaxes();

            $invoice = new Invoice;
            $invoice->type = 'proforma';
            $invoice->status = 'draft';
            $invoice->billing_purpose = $billingPurpose;
            $invoice->client_uuid = $client->uuid;
            $invoice->home_company_uuid = $homeCompany->uuid;

            $index = 1;
            foreach ($taxes as $tax) {
                if ($index > 3) {
                    break;
                }
                $invoice->{"tax_$index"} = $tax['rate'];
                $invoice->{"tax_{$index}_name"} = $tax['name'];
                $index++;
            }

            $invoice->save();
            $invoice->refresh();

            // Build description
            $productGroup = $this->product->productGroups()->first();
            $groupName = $productGroup ? $productGroup->name : '';
            $oldName = $this->product->name ?: $this->product->key;
            $newName = $newProduct->name ?: $newProduct->key;
            $label = $this->admin_label ?? '';

            $directionLabel = $proration['direction'] === 'upgrade' ? 'Upgrade' : 'Downgrade';
            $description = ($groupName ? $groupName.' / ' : '').$directionLabel.': '.$oldName.' → '.$newName;
            if ($label) {
                $description .= ' ('.$label.')';
            }

            if (($proration['full_periods_covered'] ?? 0) > 0) {
                $description .= PHP_EOL.'*-Credit covers '.$proration['full_periods_covered'].' full period(s)';
            }

            $metadata = [
                'change_type' => $proration['direction'],
                'old_product_uuid' => $this->product_uuid,
                'old_price_uuid' => $this->price_uuid,
                'target_product_uuid' => $newProduct->uuid,
                'target_price_uuid' => $newPrice->uuid,
                'target_options' => $newOptionUuids,
                'proration' => $proration,
            ];

            $item = new InvoiceItem;
            $item->invoice_uuid = $invoice->uuid;
            $item->description = $description;
            $item->taxed = true;
            $item->service_uuid = $this->uuid;
            $item->period_start = Carbon::now();
            $item->period_stop = Carbon::parse($proration['new_billing_timestamp']);
            $item->amount = max(0, $proration['total_to_pay']);
            $item->metadata = $metadata;
            $item->save();

            $invoice->issue_date = now();
            $invoice->due_date = now()->addDays($invoiceDueDays);
            $invoice->save();

            return $invoice->uuid;
        });

        if (! $invoiceUuid) {
            return null;
        }

        $invoice = Invoice::find($invoiceUuid);
        if ($invoice && $invoice->status === 'draft') {
            $result = $invoice->publish();
            if ($result['status'] === 'success') {
                $invoice->refresh();
                app(\App\Services\HookService::class)->callHooks('ServiceChangeInvoiceCreated', ['invoice' => $invoice]);
            }
        }

        return $invoice;
    }

    /**
     * Generate proration invoice to align billing to a specific day.
     */
    public function generateProrationInvoice(int $targetDay): ?Invoice
    {
        $proration = $this->calculateProrationAlignment($targetDay);
        if ($proration['status'] !== 'success' || $proration['amount'] <= 0) {
            return null;
        }

        $client = $this->client;
        if (! $client) {
            return null;
        }
        $homeCompany = $client->getHomeCompany();
        if (! $homeCompany) {
            return null;
        }

        $invoiceUuid = DB::transaction(function () use ($client, $homeCompany, $proration) {
            $locked = self::where('uuid', $this->uuid)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'active' || $locked->termination_request) {
                return null;
            }

            // Cancel any existing pending change/proration invoices for this service
            $existingInvoiceUuids = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
                ->where('invoice_items.service_uuid', $this->uuid)
                ->whereIn('invoices.billing_purpose', ['service_upgrade', 'service_downgrade', 'service_proration'])
                ->whereIn('invoices.status', ['draft', 'unpaid'])
                ->pluck('invoices.uuid')
                ->unique();

            foreach ($existingInvoiceUuids as $existingUuid) {
                $existingInvoice = Invoice::find($existingUuid);
                if ($existingInvoice) {
                    if ($existingInvoice->status === 'unpaid') {
                        $existingInvoice->cancel();
                    } elseif ($existingInvoice->status === 'draft') {
                        $existingInvoice->status = 'canceled';
                        $existingInvoice->canceled_date = now();
                        $existingInvoice->save();
                    }
                }
            }

            $invoiceDueDays = (int) \App\Services\SettingService::get('finance.invoice_due_days');
            $taxes = $client->getTaxes();

            $invoice = new Invoice;
            $invoice->type = 'proforma';
            $invoice->status = 'draft';
            $invoice->billing_purpose = 'service_proration';
            $invoice->client_uuid = $client->uuid;
            $invoice->home_company_uuid = $homeCompany->uuid;

            $index = 1;
            foreach ($taxes as $tax) {
                if ($index > 3) {
                    break;
                }
                $invoice->{"tax_$index"} = $tax['rate'];
                $invoice->{"tax_{$index}_name"} = $tax['name'];
                $index++;
            }

            $invoice->save();
            $invoice->refresh();

            $productGroup = $this->product->productGroups()->first();
            $groupName = $productGroup ? $productGroup->name : '';
            $productName = $this->product->name ?: $this->product->key;
            $label = $this->admin_label ?? '';

            $from = Carbon::parse($proration['from']);
            $to = Carbon::parse($proration['to']);

            $description = ($groupName ? $groupName.' / ' : '').$productName;
            if ($label) {
                $description .= ' ('.$label.')';
            }
            $description .= PHP_EOL.'*-Proration: '.$from->format('d.m.Y').' - '.$to->format('d.m.Y');

            $metadata = [
                'change_type' => 'proration',
                'proration' => $proration,
            ];

            $item = new InvoiceItem;
            $item->invoice_uuid = $invoice->uuid;
            $item->description = $description;
            $item->taxed = true;
            $item->service_uuid = $this->uuid;
            $item->period_start = $from;
            $item->period_stop = $to;
            $item->amount = $proration['amount'];
            $item->metadata = $metadata;
            $item->save();

            $invoice->issue_date = now();
            $invoice->due_date = now()->addDays($invoiceDueDays);
            $invoice->save();

            return $invoice->uuid;
        });

        if (! $invoiceUuid) {
            return null;
        }

        $invoice = Invoice::find($invoiceUuid);
        if ($invoice && $invoice->status === 'draft') {
            $result = $invoice->publish();
            if ($result['status'] === 'success') {
                $invoice->refresh();
            }
        }

        return $invoice;
    }

    /**
     * Execute upgrade/downgrade immediately (prepaid mode).
     */
    public function executeImmediateChange(Product $newProduct, Price $newPrice, array $newOptionUuids = []): array
    {
        if ($this->status !== 'active' || $this->termination_request) {
            return ['status' => 'error', 'errors' => [__('error.Service is not active')]];
        }

        if ($this->billing_mode === 'invoice') {
            return ['status' => 'error', 'errors' => ['Use invoice flow for invoice-mode services']];
        }

        $proration = $this->calculateUpgradeProration($newProduct, $newPrice, $newOptionUuids);
        if ($proration['status'] !== 'success') {
            return $proration;
        }

        return DB::transaction(function () use ($newProduct, $newPrice, $newOptionUuids, $proration) {
            $locked = self::where('uuid', $this->uuid)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'active') {
                return ['status' => 'error', 'errors' => [__('error.Service is not active')]];
            }

            // Reload client inside transaction for accurate balance
            $client = \App\Models\Client::where('uuid', $locked->client_uuid)->lockForUpdate()->first();
            if (! $client) {
                return ['status' => 'error', 'errors' => [__('error.Not found')]];
            }

            $totalToPay = $proration['total_to_pay'];

            if ($totalToPay > 0) {
                // $client->balance is a HasOne relation to ClientBalance; numeric amount lives on the row.
                $availableFunds = (float) ($client->balance->balance ?? 0) + (float) ($client->credit_limit ?? 0);
                if ($availableFunds < $totalToPay) {
                    return ['status' => 'error', 'errors' => [__('error.Insufficient funds')]];
                }

                $note = ($proration['direction'] === 'upgrade' ? 'Upgrade' : 'Downgrade')
                    .': '.$locked->product->key.' → '.$newProduct->key;

                $periodStop = Carbon::parse($proration['new_billing_timestamp']);

                $locked->createTransaction(
                    -abs($totalToPay),
                    now(),
                    $periodStop,
                    null,
                    null,
                    $note
                );
            }

            // Downgrade: update billing_timestamp to extend service
            if ($proration['direction'] === 'downgrade') {
                $locked->billing_timestamp = $proration['new_billing_timestamp'];
            }

            // Apply changes
            $locked->product_uuid = $newProduct->uuid;
            $locked->price_uuid = $newPrice->uuid;
            $locked->save();

            // Update options
            if (! empty($newOptionUuids)) {
                $locked->productOptions()->sync($newOptionUuids);
            }

            // Call module change_package
            $locked->refresh();
            $locked->applyModuleChangePackage();

            logActivity(
                'info',
                'Service:'.$locked->uuid.' '.$proration['direction'].' executed. Old → '.$newProduct->key,
                $proration['direction'],
                null,
                null,
                null,
                $client->uuid
            );

            return ['status' => 'success', 'data' => $proration];
        });
    }

    // =====================================================================================
    // Service-view helpers (shared between default + module controllers)
    // =====================================================================================
    //
    // These helpers encapsulate logic that would otherwise be copy-pasted across every
    // module's service_views/{list,manage,order,upgrade,proration} controllers. Modules
    // should call these from their thin `controller_*` methods instead of re-implementing
    // billing, proration, tax, or state-guard logic.
    // =====================================================================================

    /**
     * Resolve the "phase" of this service for the manage-view template.
     *
     * Manage templates (default + every module) need to pick between several visual sub-views
     * based on the service's lifecycle state: normal "active" content, a deploying spinner
     * while provisioning, a suspended banner, a terminated message, etc. This method
     * centralises that decision so every module renders the same state machine without
     * copy-pasting `if ($service->status === '...')` chains across blades.
     *
     * Returned phases:
     *   - 'active'          — fully provisioned, render normal content
     *   - 'deploying'       — pending or mid-provision, show spinner + poll GetStatus
     *   - 'deploying_error' — provision crashed, show sanitized error + keep polling
     *                         (Insufficient funds is kept as-is, all other errors become
     *                          a generic message so internal details don't leak to clients)
     *   - 'suspended'       — service is paused, show suspended banner
     *   - 'terminated'      — terminated/cancelled/not_found, show terminated view
     *   - 'fraud'           — fraud-flagged, show restricted view
     *
     * Also indicates whether the client should poll `GetStatus` (for deploying phases), and
     * returns a sanitized error message safe to show to the end client. Modules can
     * override their controller_GetStatus to adjust specific edge cases (e.g. puqVPNCP
     * treats provision_data.client_name being empty as still-deploying even though
     * service.status is already 'active').
     *
     * @return array{
     *     phase: string,
     *     should_poll: bool,
     *     poll_interval: int,
     *     error_message: string|null,
     *     error_is_insufficient_funds: bool,
     *     show_active_content: bool,
     *     show_deploying: bool,
     *     show_suspended: bool,
     *     show_terminated: bool,
     *     show_fraud: bool,
     * }
     */
    public function getManageViewState(): array
    {
        $phase = 'active';
        $shouldPoll = false;
        $errorMessage = null;
        $errorIsInsufficientFunds = false;
        $unpaidInvoiceUuid = null;
        $unpaidInvoiceUrl = null;

        // 1. Fraud flag takes precedence — service is restricted regardless of other state.
        if ($this->status === 'fraud') {
            $phase = 'fraud';
        }
        // 2. Terminal states: terminated / cancelled / not_found.
        elseif (in_array($this->status, ['terminated', 'cancelled', 'not_found'], true)) {
            $phase = 'terminated';
        }
        // 3. Suspended — service paused (usually by admin or billing).
        elseif ($this->status === 'suspended') {
            $phase = 'suspended';
        }
        // 4. Pending — service created but not yet provisioned at all.
        elseif ($this->status === 'pending') {
            // Special case: invoice-mode service waiting for the client to pay. Instead of
            // the generic deploying spinner we show an "awaiting payment" screen with a
            // link to the invoice. Polling still runs — as soon as the invoice is paid the
            // lifecycle job flips status → active and the page reloads automatically.
            if ($this->billing_mode === 'invoice') {
                $unpaidInvoiceUuid = $this->findUnpaidInvoiceUuid();
                if ($unpaidInvoiceUuid) {
                    $phase = 'awaiting_payment';
                    $shouldPoll = true;
                    $unpaidInvoiceUrl = route('client.web.panel.client.invoice.payment', ['uuid' => $unpaidInvoiceUuid]);
                } else {
                    // No unpaid invoice — service is pending for some other reason, generic deploy.
                    $phase = 'deploying';
                    $shouldPoll = true;
                }
            } else {
                $phase = 'deploying';
                $shouldPoll = true;
            }
        }
        // 5. Active but provisioning still in progress (module-backed services).
        //    `provision_status` lifecycle: pending → processing → completed (or error).
        elseif ($this->status === 'active' && $this->provision_status !== 'completed') {
            $phase = 'deploying';
            $shouldPoll = true;
        }
        // 6. Active + completed, but a create_error is set — something crashed mid-deploy.
        //    The background job (module create hook) can retry, so we keep polling and
        //    surface a sanitized message to the client.
        elseif ($this->status === 'active' && ! empty($this->create_error)) {
            $phase = 'deploying_error';
            $shouldPoll = true;
            // Only "Insufficient funds" is safe to show verbatim — it's actionable by the client.
            // All other errors are internal and get a generic message so we don't leak details.
            if ($this->create_error === 'Insufficient funds') {
                $errorMessage = __('main.Insufficient funds');
                $errorIsInsufficientFunds = true;
            } else {
                $errorMessage = __('error.Something went wrong');
            }
        }

        return [
            'phase' => $phase,
            'should_poll' => $shouldPoll,
            'poll_interval' => 5,  // seconds between GetStatus checks
            'error_message' => $errorMessage,
            'error_is_insufficient_funds' => $errorIsInsufficientFunds,
            'unpaid_invoice_uuid' => $unpaidInvoiceUuid,
            'unpaid_invoice_url' => $unpaidInvoiceUrl,
            'show_active_content' => $phase === 'active',
            'show_deploying' => in_array($phase, ['deploying', 'deploying_error'], true),
            'show_awaiting_payment' => $phase === 'awaiting_payment',
            'show_suspended' => $phase === 'suspended',
            'show_terminated' => $phase === 'terminated',
            'show_fraud' => $phase === 'fraud',
        ];
    }

    /**
     * Find the UUID of any currently unpaid invoice tied to this service via its
     * service_billing invoice item. Returns null if no unpaid invoice exists.
     */
    protected function findUnpaidInvoiceUuid(): ?string
    {
        $invoiceItem = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
            ->where('invoice_items.service_uuid', $this->uuid)
            ->where('invoices.billing_purpose', 'service_billing')
            ->whereIn('invoices.status', ['unpaid'])
            ->select('invoices.uuid')
            ->first();

        return $invoiceItem?->uuid;
    }

    /**
     * Determine whether this service can currently be mutated (upgrade / downgrade / proration / billing-mode switch).
     *
     * Returns a localised error string when the service is in a non-mutable state, or `null` when it is OK to proceed.
     * Callers can use the returned string directly in an error response:
     *
     *     if ($err = $service->validateMutable(true)) {
     *         return response()->json(['errors' => [$err]], 422);
     *     }
     *
     * @param  bool  $requireProvisioned  When true, also require `provision_status === 'completed'`
     *                                    (useful to block operations mid-provisioning for module-backed services).
     * @param  bool  $requireInvoiceMode  When true, also require `billing_mode === 'invoice'`
     *                                    (used by proration alignment — prepaid has no meaning here).
     * @return string|null  Error message to surface, or `null` if the service is mutable.
     */
    public function validateMutable(bool $requireProvisioned = false, bool $requireInvoiceMode = false): ?string
    {
        if ($this->status !== 'active' || $this->termination_request) {
            return __('error.Service is not active');
        }

        if ($requireProvisioned && $this->provision_status !== 'completed') {
            return __('error.Operation not allowed while service is being provisioned');
        }

        if ($requireInvoiceMode && $this->billing_mode !== 'invoice') {
            return __('error.This operation is only available for invoice billing mode');
        }

        return null;
    }

    /**
     * Build ready-to-render tax lines for an arbitrary subtotal using this service's client tax rules.
     *
     * Produces the exact structure that every controller used to assemble by hand — so the blade
     * templates can render tax breakdown consistently across all modules without re-implementing
     * the client-tax lookup + percentage math + currency formatting.
     *
     * Returns:
     *   [
     *     'lines'           => [['name', 'rate', 'amount', 'amount_str'], ...],
     *     'tax_total'       => float,
     *     'tax_total_str'   => string (formatted in the service's currency),
     *     'gross_total'     => float,           // subtotal + tax_total
     *     'gross_total_str' => string,
     *   ]
     */
    public function calculateTaxLines(float $subtotal): array
    {
        $currency = $this->price?->currency;

        // Delegate to Client::calculateTaxLines() — single source of truth for tax math.
        if ($this->client) {
            return $this->client->calculateTaxLines($subtotal, $currency);
        }

        // Fallback if somehow no client is attached (shouldn't happen in normal flow).
        return [
            'lines' => [],
            'tax_total' => 0.0,
            'tax_total_str' => formatCurrency(0, $currency),
            'gross_total' => round($subtotal, 2),
            'gross_total_str' => formatCurrency($subtotal, $currency),
        ];
    }

    /**
     * Whether this service's client can afford a given amount from their balance + credit limit.
     * Thin wrapper around Client::hasSufficientBalance() for ergonomic call sites in controllers.
     */
    public function canAffordChange(float $amount): bool
    {
        return $this->client?->hasSufficientBalance($amount) ?? false;
    }

    /**
     * Determine whether switching to `$newPrice` is an upgrade or downgrade from the current price.
     *
     * @return string|null  'upgrade', 'downgrade', or null if the two prices are equal / unavailable.
     */
    public function getUpgradeDirection(Price $newPrice): ?string
    {
        $currentBase = (float) ($this->price->base ?? 0);
        $newBase = (float) ($newPrice->base ?? 0);

        if ($newBase > $currentBase) {
            return 'upgrade';
        }
        if ($newBase < $currentBase) {
            return 'downgrade';
        }

        return null;
    }

    /**
     * Build the list of products this service can be upgraded/downgraded to, filtered by:
     *   - same module as the current product
     *   - not the current product itself
     *   - not retired / hidden
     *   - has a price in the client's currency + (optionally) the same billing period
     *   - price differs from the current one (same-price entries are pointless)
     *   - the direction (upgrade/downgrade) is permitted by the current product's allow_* flags
     *
     * Each entry in the returned collection is a plain array with the chosen price and direction
     * already attached, so controllers can render a DataTable row without extra queries:
     *
     *   [
     *     'product'       => Product,        // the candidate product instance
     *     'price'         => Price,          // the matching price row
     *     'direction'     => 'upgrade'|'downgrade',
     *     'uuid'          => string,         // product uuid (convenience)
     *     'name'          => string,
     *     'price_base'    => float,
     *     'price_string'  => string,         // currency-formatted
     *   ]
     *
     * Modules that need extra metadata (bandwidth, protocols, …) can map over the result and
     * enrich each row from their own product_data helpers.
     *
     * @param  string|null  $period  Optional period filter (defaults to the current service's period).
     */
    public function getAvailableUpgradeProducts(?string $period = null): \Illuminate\Support\Collection
    {
        $currentProduct = $this->product;
        $currentPrice = $this->price;
        $client = $this->client;

        if (! $currentProduct || ! $currentPrice || ! $client || ! $currentProduct->module_uuid) {
            return collect();
        }

        $targetPeriod = $period ?? $currentPrice->period;
        $currencyUuid = $client->currency_uuid;

        $products = Product::where('module_uuid', $currentProduct->module_uuid)
            ->where('uuid', '!=', $currentProduct->uuid)
            ->where('retired', false)
            ->where('hidden', false)
            ->get();

        $rows = collect();
        foreach ($products as $product) {
            $product->setLocale(session('locale'));

            $price = $product->prices()
                ->where('period', $targetPeriod)
                ->where('currency_uuid', $currencyUuid)
                ->first();

            if (! $price) {
                continue;
            }

            $direction = $this->getUpgradeDirection($price);
            if ($direction === null) {
                // Same price — no point offering this as a "change".
                continue;
            }

            if ($direction === 'upgrade' && ! $currentProduct->allow_upgrade) {
                continue;
            }
            if ($direction === 'downgrade' && ! $currentProduct->allow_downgrade) {
                continue;
            }

            $rows->push([
                'product' => $product,
                'price' => $price,
                'direction' => $direction,
                'uuid' => $product->uuid,
                'name' => $product->name ?: $product->key,
                'price_base' => (float) $price->base,
                'price_string' => formatCurrency($price->base, $currentPrice->currency),
                'product_price_uuid' => $price->uuid,
            ]);
        }

        return $rows;
    }

    /**
     * Execute proration alignment end-to-end.
     *
     * This centralises the full `controller_Execute` flow from every Proration service_view:
     *   - validates the service is mutable
     *   - validates target_day is within 1..28
     *   - for invoice billing: generates the proration invoice and returns redirect data
     *   - for prepaid billing: locks the service, cancels stale draft/unpaid change+proration invoices,
     *     creates a negative balance transaction (if > 0), and shifts `billing_timestamp` forward
     *
     * The response shape mirrors the existing controller response so blade JS does not need to change:
     *   Success (invoice):
     *     ['status' => 'success', 'action' => 'invoice_created', 'invoice_uuid' => ..., 'redirect' => ..., 'message' => ...]
     *   Success (prepaid):
     *     ['status' => 'success', 'action' => 'changed', 'message' => ...]
     *   Error:
     *     ['status' => 'error', 'errors' => [...], 'code' => 400|422|500]
     *
     * @param  int   $targetDay            Day of month (1..28) to align billing to.
     * @param  bool  $requireProvisioned   When true, block on provision_status !== 'completed' (module-backed services).
     */
    public function applyProrationAlignment(int $targetDay, bool $requireProvisioned = false): array
    {
        // Proration alignment has no meaning for prepaid (see docs on decision) — but we let callers
        // decide whether to enforce invoice-only via the $requireInvoiceMode guard below.
        if ($err = $this->validateMutable($requireProvisioned)) {
            return ['status' => 'error', 'errors' => [$err], 'code' => 400];
        }

        if ($targetDay < 1 || $targetDay > 28) {
            return [
                'status' => 'error',
                'errors' => [__('error.Target day must be between 1 and 28')],
                'code' => 400,
            ];
        }

        // Invoice-mode: just generate the proration invoice and hand back the redirect.
        if ($this->billing_mode === 'invoice') {
            $invoice = $this->generateProrationInvoice($targetDay);
            if (! $invoice) {
                return [
                    'status' => 'error',
                    'errors' => [__('error.Failed to generate invoice')],
                    'code' => 500,
                ];
            }

            return [
                'status' => 'success',
                'action' => 'invoice_created',
                'invoice_uuid' => $invoice->uuid,
                'redirect' => route('client.web.panel.client.invoice.payment', ['uuid' => $invoice->uuid]),
                'message' => __('message.Proration invoice generated successfully'),
            ];
        }

        // Prepaid-mode: calculate, check balance, lock + apply in transaction.
        $proration = $this->calculateProrationAlignment($targetDay);
        if ($proration['status'] !== 'success') {
            return [
                'status' => 'error',
                'errors' => $proration['errors'] ?? [__('error.Something went wrong')],
                'code' => 500,
            ];
        }

        $amount = (float) ($proration['amount'] ?? 0);
        if (! $this->canAffordChange($amount)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Insufficient funds')],
                'code' => 400,
            ];
        }

        DB::transaction(function () use ($proration, $amount) {
            $locked = self::where('uuid', $this->uuid)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'active' || $locked->termination_request) {
                return;
            }

            // Cancel any still-outstanding change/proration invoices for this service. We do this
            // to avoid leaving stale unpaid invoices that reference an obsolete billing timestamp.
            $existingUuids = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
                ->where('invoice_items.service_uuid', $this->uuid)
                ->whereIn('invoices.billing_purpose', ['service_upgrade', 'service_downgrade', 'service_proration'])
                ->whereIn('invoices.status', ['draft', 'unpaid'])
                ->pluck('invoices.uuid')
                ->unique();

            foreach ($existingUuids as $existingUuid) {
                $existingInvoice = Invoice::find($existingUuid);
                if (! $existingInvoice) {
                    continue;
                }
                if ($existingInvoice->status === 'unpaid') {
                    $existingInvoice->cancel();
                } elseif ($existingInvoice->status === 'draft') {
                    $existingInvoice->status = 'canceled';
                    $existingInvoice->canceled_date = now();
                    $existingInvoice->save();
                }
            }

            if ($amount > 0) {
                $locked->createTransaction(
                    -abs($amount),
                    Carbon::parse($proration['from']),
                    Carbon::parse($proration['to']),
                    null,
                    null,
                    'Proration Alignment'
                );
                $locked->billing_timestamp = $proration['new_billing_timestamp'];
                $locked->save();
            }
        });

        return [
            'status' => 'success',
            'action' => 'changed',
            'message' => __('message.Billing date aligned successfully'),
        ];
    }

    /**
     * Apply module change_package hook.
     */
    public function applyModuleChangePackage(): void
    {
        $product = $this->product;
        if (! $product || ! $product->module) {
            return;
        }

        try {
            $module = $product->module;
            $module->moduleExecute('setServiceUuid', $this->uuid);
            $module->moduleExecute('setProductUuid', $product->uuid);
            $module->moduleExecute('getProductData', $product->configuration);
            // Service::retrieved does not fire on refresh(), so the freshly-reloaded module
            // instance never gets service_data populated via Service::retrieved's hook.
            // Without this explicit call, change_package would serialize an empty service_data
            // into the queue and the job would no-op because client_name is missing.
            $module->moduleExecute('getServiceData', $this->provision_data ?? []);
            $module->moduleExecute('change_package');
        } catch (\Throwable $e) {
            logActivity(
                'error',
                'Service:'.$this->uuid.' change_package failed: '.$e->getMessage(),
                'change_package',
                null,
                null,
                null,
                $this->client_uuid
            );
        }
    }
}
