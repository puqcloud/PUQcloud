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

use App\Services\HookService;
use App\Traits\ConvertsTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    }

    protected $fillable = [
        'server_uuid',
        'client_uuid',
        'product_uuid',
        'status',
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
        $client = Client::find($data['client'] ?? null);
        if (empty($client)) {
            return ['error' => __('error.Not found'), 'code' => 404];
        }

        $product = Product::find($data['product'] ?? null);
        if (empty($product)) {
            return ['error' => __('error.Not found'), 'code' => 404];
        }

        $product_price = $product->prices()->where('uuid', $data['product_price'] ?? null)->first();
        if (empty($product_price)) {
            return ['error' => __('error.Not found'), 'code' => 404];
        }

        if ($product_price->currency->uuid != $client->currency_uuid) {
            return ['error' => __('error.Not found'), 'code' => 404];
        }

        DB::beginTransaction();
        try {
            $service = new self;
            $service->server_uuid = $client->uuid;
            $service->client_uuid = $client->uuid;
            $service->product_uuid = $product->uuid;
            $service->price_uuid = $product_price->uuid;
            $service->order_date = now();
            $service->generateAdminLabel();
            $service->save();
            $service->refresh();
            $service->productOptions()->detach();

            $input_options = $data['option'] ?? [];
            $product_option_groups = $product->productOptionGroups;

            foreach ($product_option_groups as $group) {
                $group_options = $group->productOptions()->orderBy('order')->get();

                $selected_option = null;
                foreach ($group_options as $option) {
                    if (in_array($option->uuid, $input_options)) {
                        $selected_option = $option;
                        break;
                    }
                }

                if (! $selected_option && $group_options->count()) {
                    $selected_option = $group_options->first();
                }

                if ($selected_option) {
                    $service->productOptions()->attach($selected_option->uuid);
                }
            }

            DB::commit();

            app(HookService::class)->callHooks('PendingService', ['service' => $service]);

            return ['success' => true, 'service' => $service];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Service creation error: '.$e->getMessage());

            return ['error' => __('error.Something went wrong'), 'code' => 500];
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
        return $this->belongsToMany(ProductOption::class, 'service_x_product_option', 'service_uuid', 'product_option_uuid');
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
        $price = $this->price;
        $price_total = [
            'setup' => $price->setup ?? null,
            'base' => $price->base ?? null,
            'idle' => $price->idle ?? null,
            'switch_down' => $price->switch_down ?? null,
            'switch_up' => $price->switch_up ?? null,
            'uninstall' => $price->uninstall ?? null,
        ];

        $product_options = $this->productOptions;

        foreach ($product_options as $product_option) {
            $option_price = $product_option->prices()
                ->where('currency_uuid', $price->currency_uuid)
                ->where('period', $price->period)
                ->first();

            if (! $option_price) {
                continue;
            }

            foreach ($price_total as $key => $value) {
                $option_value = $option_price->$key ?? null;
                if (is_numeric($option_value)) {
                    $price_total[$key] = is_numeric($value) ? $value + $option_value : $option_value;
                }
            }
        }

        return $price_total;
    }

    public function getPriceDetailed(): array
    {
        $price = $this->price;
        $currency = $price->currency;
        $currency_uuid = $currency->uuid;
        $period = $price->period;
        $product = $this->product;

        $product_option_groups = $this->product->productOptionGroups;
        $service_price = [
            'setup' => $price->setup ?? 0,
            'base' => $price->base ?? 0,
            'idle' => $price->idle ?? 0,
            'switch_down' => $price->switch_down ?? 0,
            'switch_up' => $price->switch_up ?? 0,
            'uninstall' => $price->uninstall ?? 0,
        ];

        $price_total = $service_price;
        $option_prices = [];

        foreach ($this->productOptions as $product_option) {
            $option_price = $product_option->prices()
                ->where('currency_uuid', $currency_uuid)
                ->where('period', $period)
                ->first();

            if (! $option_price) {
                continue;
            }

            $product_option_product_option_group = $product_option->productOptionGroup;
            $group_key = $product_option_product_option_group->key;
            $option_key = $product_option->key;
            $order = 0;
            foreach ($product_option_groups as $product_option_group) {
                if ($product_option_group->uuid == $product_option_product_option_group->uuid) {
                    $order = $product_option_group->pivot->order;
                }
            }

            $price_data = [
                'setup' => $option_price->setup ?? 0,
                'base' => $option_price->base ?? 0,
                'idle' => $option_price->idle ?? 0,
                'switch_down' => $option_price->switch_down ?? 0,
                'switch_up' => $option_price->switch_up ?? 0,
                'uninstall' => $option_price->uninstall ?? 0,
            ];

            foreach ($price_total as $key => $val) {
                $price_total[$key] += $price_data[$key] ?? 0;
            }

            $option_prices[] = [
                'model' => $product_option,
                'product_option_group_key' => $group_key,
                'product_option_key' => $option_key,
                'order' => $order,
                'price' => $price_data,
            ];
        }

        usort($option_prices, fn ($a, $b) => $a['order'] <=> $b['order']);

        $hourly_billing = false;
        if ($product->hourly_billing and $period == 'monthly') {
            $hourly_billing = true;
        }

        return [
            'currency' => [
                // 'uuid' => $currency->uuid,
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

        if (! $product || ! $price) {
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

        if (! $product || ! $price) {
            return;
        }

        $optionGroups = $product->productOptionGroups()->with('productOptions')->get();
        $currentOptions = $this->productOptions()->with('productOptionGroup')->get();

        foreach ($optionGroups as $group) {
            $newOption = $group->productOptions->firstWhere(function ($option) use ($uuids) {
                return in_array($option->uuid, $uuids);
            });

            $currentOption = $currentOptions->firstWhere(
                fn ($opt) => $opt->productOptionGroup?->uuid === $group->uuid
            );

            if ($currentOption && $newOption && $currentOption->uuid === $newOption->uuid) {
                continue;
            }

            if ($currentOption && (! $newOption || $currentOption->uuid !== $newOption->uuid)) {
                $this->productOptions()->detach($currentOption->uuid);
            }

            if ($newOption && ! $this->productOptions->contains($newOption->uuid)) {
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
        if ($data_array['data']['status'] == 'error') {
            $data_array['code'] = $data_array['code'] ?? 500;

            return $data_array['data'];
        }

        $this->setProvisionData($data_array['data']['data']);

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

        if (! empty($this->module)) {
            $result = $this->module->moduleExecute('getClientAreaMenuConfig');
            if ($result['status'] === 'success') {
                $menu = $result['data'];
            }
        }

        foreach ($menu as $key => &$item) {
            if (! isset($item['template'])) {
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

        if (! empty($this->module)) {
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

        if (! empty($this->module)) {
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

        if (! empty($this->module)) {
            $result = $this->module->moduleExecute($action);

            return $result;
        }

        return [
            'status' => 'error',
            'errors' => [__('error.The module is not available')],
        ];
    }

    public function create(): array
    {
        app(HookService::class)->callHooks('CreateService', ['service' => $this]);

        if ($this->status != 'pending') {
            if ($this->create_error !== 'Status should be pending') {
                $this->create_error = 'Status should be pending';
                $this->save();
                app(HookService::class)->callHooks('CreateServiceError', ['service' => $this]);
            }

            return ['status' => 'error', 'errors' => ['Status should be pending']];
        }

        $priceTotal = $this->getPriceTotal();
        $setupAmount = $priceTotal['setup'] ?? 0;

        $chargeData = $this->calculateCharge();
        $totalCharge = abs($chargeData['amount']);

        $total = $totalCharge + abs($setupAmount);

        if (! $this->hasEnoughBalance($total)) {
            if ($this->create_error !== 'Insufficient funds') {
                logActivity(
                    'warning',
                    'Service:'.$this->uuid.'. Insufficient funds: cannot create service. Required: '.$total,
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

        if ($setupAmount > 0) {
            $this->createTransaction(
                -$setupAmount,
                now(),
                now(),
                null,
                null,
                'Setup'
            );
        }

        if (! empty($this->module)) {
            $this->status = 'deploying';
            $this->save();

            $result = $this->module->moduleExecute('create');
            if ($result['status'] === 'error') {
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

    public function suspend(): array
    {
        if ($this->status != 'active') {
            return ['status' => 'error', 'errors' => ['Status should be active']];
        }

        $chargeData = $this->calculateCharge();
        if ($chargeData['period'] == 'one-time') {
            return ['status' => 'error', 'errors' => ['Period is one-time']];
        }

        if ($this->billing_timestamp && Carbon::parse($this->billing_timestamp)->isFuture()) {
            return [];
        }

        if (! $this->hasEnoughBalance(abs($chargeData['amount']))) {
            app(HookService::class)->callHooks('SuspendService', ['service' => $this]);

            if (! empty($this->module)) {
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

    public function unsuspend(): array
    {
        if ($this->status !== 'suspended') {
            return ['status' => 'error', 'errors' => ['Status should be suspended']];
        }

        $chargeData = $this->calculateCharge();
        if ($chargeData['period'] == 'one-time') {
            return ['status' => 'error', 'errors' => ['Period is one-time']];
        }

        if ($this->hasEnoughBalance(abs($chargeData['amount']))) {
            app(HookService::class)->callHooks('UnsuspendService', ['service' => $this]);

            if (! empty($this->module)) {
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

    public function termination(): array
    {
        $termination_time = $this->getTerminationTime();

        if (is_null($termination_time['seconds_left']) || $termination_time['seconds_left'] > 0) {
            return [];
        }

        app(HookService::class)->callHooks('TerminationService', ['service' => $this]);

        if (! empty($this->module)) {
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
        $this->save();

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

    public function cancellation(): array
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

        if (! empty($this->module)) {
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
        $this->save();

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

    // Finance
    public function calculateCharge(): ?array
    {
        $priceTotal = $this->getPriceTotal();
        $chargeAmount = $this->idle ? $priceTotal['idle'] : $priceTotal['base'];
        $period = $this->price->period;
        $hourlyBilling = $this->product->hourly_billing;

        $now = now();
        $billingTimestamp = \Carbon\Carbon::parse($this->billing_timestamp);

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
            $hourlyRate = round($chargeAmount / $hoursInMonth, 4);
            $amountToCharge = round(-abs($hourlyRate) * $hoursToCharge, 4);

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

    private function hasEnoughBalance(float $amount): bool
    {
        $client = $this->client;
        $balance = $client->balance->balance ?? 0;
        $creditLimit = $client->credit_limit;

        return ($balance + $creditLimit) >= $amount;
    }

    protected function createTransaction(float $amount, $periodStart, $periodStop, ?int $hours = null, ?float $rate = null, $note = null): void
    {
        $description = "Service:{$this->uuid}";

        if ($note !== null) {
            $description .= ", {$note}";
        }

        if ($hours !== null && $rate !== null) {
            $description .= ", {$hours}h × {$rate} per hour";
        }

        Transaction::create([
            'client_uuid' => $this->client_uuid,
            'type' => 'charge',
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

    public function chargeJob(): void
    {
        if ($this->status != 'active') {
            return;
        }

        $chargeData = $this->calculateCharge();

        if (! $chargeData) {
            return;
        }

        if ($this->hasEnoughBalance(abs($chargeData['amount']))) {
            $this->createTransaction(
                $chargeData['amount'],
                $chargeData['from'],
                $chargeData['to'],
                $chargeData['hours'] ?? null,
                $chargeData['rate'] ?? null
            );
            $this->billing_timestamp = $chargeData['to'];
            $this->save();
        } else {
            logActivity(
                'warning',
                'Service:'.$this->uuid.'. Insufficient funds: cannot charge service. Required: '.abs($chargeData['amount']),
                'charge',
                null,
                null,
                null,
                $this->client_uuid
            );
        }
    }

    public function getTerminationTime(): array
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

        if (! $suspendedDate || ! $terminationDelayHours) {
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

    public function getCancellationTime(): array
    {
        $product = $this->product;
        $cancellationDelayHours = $product->cancellation_delay_hours;
        $orderDate = Carbon::parse($this->order_date);
        $activatedDate = $this->activated_date;
        $now = Carbon::now();

        if (! empty($activatedDate)) {
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
}
