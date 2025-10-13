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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class DnsServer extends Model
{
    use HasFactory;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'dns_servers';

    protected $fillable = [
        'name',
        'module_uuid',
        'description',
        'configuration',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function getConfigurationAttribute($value): array
    {
        $configuration = json_decode($value, true);

        return is_array($configuration) ? $configuration : [];
    }

    public function setConfigurationAttribute($value): void
    {
        $this->attributes['configuration'] = json_encode($value);
    }

    public function DnsServerGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            DnsServerGroup::class,
            'dns_server_x_dns_server_group',
            'dns_server_uuid',
            'dns_server_group_uuid'
        )->withTimestamps();
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_uuid', 'uuid');
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

        $this->configuration = $data_array['data'];

        return $data_array;
    }

    protected function executeModule(string $method, ...$params): array
    {
        if (empty($this->module)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Module not found')],
                'code' => 404,
            ];
        }

        $data_array = $this->module->moduleExecute('getModuleData', $this->configuration);
        if ($data_array['status'] === 'error') {
            return [
                'status' => 'error',
                'errors' => $data_array['errors'] ?? [],
                'code' => $data_array['code'] ?? 500,
            ];
        }

        $result = $this->module->moduleExecute($method, ...$params);
        if ($result['status'] === 'error') {
            return [
                'status' => 'error',
                'errors' => $result['errors'] ?? [],
                'code' => $result['code'] ?? 500,
            ];
        }

        return $result;
    }

    public function testConnection(): array
    {
        return $this->executeModule('testConnection');
    }

    public function createZone($uuid): array
    {
        return $this->executeModule('createZone', $uuid);
    }

    public function reloadZone($uuid): array
    {
        return $this->executeModule('reloadZone', $uuid);
    }

    public function deleteZone($name): array
    {
        return $this->executeModule('deleteZone', $name);
    }

    public function createRecord($uuid): array
    {
        return $this->executeModule('createRecord', $uuid);
    }

    public function updateRecord($uuid, $old_content): array
    {
        return $this->executeModule('updateRecord', $uuid, $old_content);
    }

    public function deleteRecord(string $zone_uuid, string $name, string $type, string $content): array
    {
        return $this->executeModule('deleteRecord', $zone_uuid, $name, $type, $content);
    }


    // -----------------------------------------------------------------------------------------------------------------
}
