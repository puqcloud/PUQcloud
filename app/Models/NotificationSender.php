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

use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class NotificationSender extends Model
{
    use ConvertsTimezone;
    use HasFactory;

    protected $table = 'notification_senders';

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
        'name',
        'module_uuid',
        'configuration',
        'description',
    ];

    public function getConfigurationAttribute($value): array
    {
        $configuration = json_decode($value, true);

        return is_array($configuration) ? $configuration : [];
    }

    public function setConfigurationAttribute($value): void
    {
        $this->attributes['configuration'] = json_encode($value);
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

    public function send(array $data = []): array
    {
        if (empty($this->module)) {
            return [
                'status' => 'error',
                'error' => __('error.Module not found'),
                'to_email' => $data['to_email'] ?? '',
                'to_phone' => $data['to_phone'] ?? '',
                'attachments' => $data['attachments'] ?? [],
            ];
        }

        $data_array = $this->module->moduleExecute('getModuleData', $this->configuration);

        if ($data_array['status'] == 'error') {
            return $data_array;
        }
        $data = array_merge($data, $data_array['data']);
        $data_array = $this->module->moduleExecute('send', $data);

        if ($data_array['status'] == 'error') {
            return $data_array;
        }

        return $data_array['data'];
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function notificationRules(): BelongsToMany
    {
        return $this->belongsToMany(NotificationRule::class, 'notification_rule_x_notification_sender', 'notification_sender_uuid', 'notification_rule_uuid');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_uuid', 'uuid');
    }
}
