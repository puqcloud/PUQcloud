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

    public function getDnsZones(): array
    {
        $dns_zones = [];
        $remote_zones = $this->executeModule('getZones');

        if ($remote_zones['status'] == 'error') {
            return $remote_zones;
        }
        $local_zones = DnsZone::query()->whereIn('name', $remote_zones['data'])->get();

        foreach ($remote_zones['data'] as $remote_zone) {

            $dns_zones[] = [
                'name' => $remote_zone,
                'local' => $local_zones->where('name', $remote_zone)->isNotEmpty(),
            ];
        }

        return [
            'status' => 'success',
            'data' => $dns_zones,
        ];
    }

    public function getDnsZoneRecords(string $zone_name): array
    {
        $remote_zone_records = $this->executeModule('getZoneRecords', $zone_name);

        if ($remote_zone_records['status'] == 'error') {
            return $remote_zone_records;
        }

        return [
            'status' => 'success',
            'data' => $remote_zone_records['data'],
        ];
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function importZone(string $zone_name, string $import_mode, string $dns_server_group_uuid): array
    {
        $dns_zone_records = $this->getDnsZoneRecords($zone_name);

        if ($dns_zone_records['status'] == 'error') {
            return $dns_zone_records;
        }

        $records = $dns_zone_records['data'];

        $dns_server_group = DnsServerGroup::query()->where('uuid', $dns_server_group_uuid)->first();
        if (!$dns_server_group) {
            return [
                'status' => 'error',
                'errors' => [__('error.DNS server group not found')],
            ];
        }

        if($import_mode == 'replace'){
            DnsZone::query()->where('name', $zone_name)->delete();
        }

        $dns_zone = DnsZone::query()->where('name', $zone_name)->first();
        if (!$dns_zone) {
            $dns_zone = new DnsZone();
            $dns_zone->name = $zone_name;
            $dns_zone->soa_admin_email = 'admin@'.$zone_name;
            $dns_zone->soa_ttl = 3600;
            $dns_zone->soa_refresh = 3600;
            $dns_zone->soa_retry = 1800;
            $dns_zone->soa_expire = 1209600;
            $dns_zone->soa_minimum = 3600;
            $dns_zone->dns_server_group_uuid = $dns_server_group_uuid;
        }
        $dns_zone->save();

        $success = 0;
        $errors = 0;

        foreach ($records as $record) {

            if ($record['type'] == 'SOA') {
                $parts = explode(' ', $record['content']);
                if(isset($parts[1])) {
                    $email_raw = rtrim($parts[1], '.');
                    $dns_zone->soa_admin_email = preg_replace('/\./', '@', $email_raw, 1);
                }
                if (isset($parts[2])) {
                    $dns_zone->soa_ttl = (int) $parts[2];
                }
                if (isset($parts[3])) {
                    $dns_zone->soa_refresh = (int) $parts[3];
                }
                if (isset($parts[4])) {
                    $dns_zone->soa_retry = (int) $parts[4];
                }
                if (isset($parts[5])) {
                    $dns_zone->soa_expire = (int) $parts[5];
                }
                if (isset($parts[6])) {
                    $dns_zone->soa_minimum = (int) $parts[6];
                }
                $dns_zone->save();
            }

            $status = $dns_zone->createUpdateRecord($record, null, true, true);
            if ($status['status'] == 'error') {
                $errors++;
            } else {
                $success++;
            }
        }

        $dns_zone->reloadZone();

        logActivity(
            'info',
            'Zone: '.$zone_name.'. Success: '.$success.'. Error: '.$errors,
            'DNS zone import',
        );

        return [
            'status' => 'success',
            'data' => [
                'success' => $success,
                'errors' => $errors,
            ],
        ];

    }
}
