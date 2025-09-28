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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentGateway extends Model
{
    use AutoTranslatable;
    use ConvertsTimezone;
    use HasFiles;

    const IMAGES = [
        'icon' => ['label' => 'Icon image', 'order' => 1],
    ];

    protected $table = 'payment_gateways';

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
                $model->module->moduleExecute('getModuleData', $model->configuration);
                $model->module->moduleExecute('setPaymentGatewayUuid', $model->uuid);
            }
        });
    }

    protected $fillable = [
        'key',
        'module_uuid',
        'home_company_uuid',
        'configuration',
    ];

    protected $translatable = ['name', 'description'];

    public function getConfigurationAttribute($value): array
    {
        $configuration = json_decode($value, true);

        return is_array($configuration) ? $configuration : [];
    }

    public function setConfigurationAttribute($value): void
    {
        $this->attributes['configuration'] = json_encode($value);
    }

    public function currencies()
    {
        return $this->belongsToMany(Currency::class, 'payment_gateway_x_currency', 'payment_gateway_uuid', 'currency_uuid')
            ->withTimestamps();
    }

    // Module ----------------------------------------------------------------------------------------------------------
    public function getModuleConfig(): array
    {
        if (empty($this->module)) {
            return [];
        }

        return $this->module->module_data;
    }

    public function getSettingsPage(): string
    {
        if (empty($this->module)) {
            return '<h1>'.__('error.The module is not available').'</h1>';
        }

        $data_array = $this->module->moduleExecute('getModuleData', $this->configuration);

        if ($data_array['status'] == 'error') {
            return $data_array['message'];
        }
        $data = $data_array['data'];
        $data['uuid'] = $this->uuid;

        $data_array = $this->module->moduleExecute('getSettingsPage', $data);

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

        $data = $this->module->moduleExecute('getModuleData', $data);
        if ($data['status'] == 'success') {
            $data = $data['data'];
        }

        $data_array = $this->module->moduleExecute('saveModuleData', $data);
        if ($data_array['status'] == 'error') {
            $data_array['code'] = $data_array['code'] ?? 500;

            return $data_array;
        }
        if ($data_array['data']['status'] == 'error') {
            $data_array['code'] = $data_array['code'] ?? 500;

            return $data_array['data'];
        }

        $this->configuration = $data_array['data']['data'];

        return $data_array;
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_uuid', 'uuid');
    }

    public function homeCompany(): BelongsTo
    {
        return $this->belongsTo(HomeCompany::class, 'homa_company_uuid', 'uuid');
    }

    public function getImgData(): string
    {
        $module = $this->module;
        if (! $module) {
            return '';
        }

        if (! empty($this->images['icon'])) {
            return $this->images['icon'];
        }

        $path = base_path('modules/Payment/'.$module->name.'/views/assets/pg.jpg');

        if (! file_exists($path)) {
            return '';
        }

        $type = mime_content_type($path);
        $data = base64_encode(file_get_contents($path));

        return 'data:'.$type.';base64,'.$data;
    }

    public static function reorder($home_company_uuid): void
    {
        $payment_gateways = self::orderBy('order')
            ->where('home_company_uuid', $home_company_uuid)
            ->get();
        $order = 1;

        foreach ($payment_gateways as $payment_gateway) {
            DB::table('payment_gateways')
                ->where('uuid', $payment_gateway->uuid)
                ->update(['order' => $order]);
            $order++;
        }
    }

    public function getClientAreaModuleHtml(Invoice $invoice): string
    {
        if (empty($this->module)) {
            return '<h1>'.__('error.The module is not available').'</h1>';
        }

        $data['uuid'] = $this->uuid;
        $data['invoice'] = $invoice;

        $data_array = $this->module->moduleExecute('getClientAreaHtml', $data);

        if ($data_array['status'] == 'error') {
            return $data_array['message'];
        }

        return $data_array['data'];
    }
}
