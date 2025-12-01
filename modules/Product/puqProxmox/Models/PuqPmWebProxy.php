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

namespace Modules\Product\puqProxmox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use puqWebProxyClient;

class PuqPmWebProxy extends Model
{
    protected $table = 'puq_pm_web_proxies';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'api_url',
        'api_key',
        'frontend_ips',
        'last_check_data',
        'thresholds',
        'disable',
        'puq_pm_load_balancer_uuid',
    ];

    protected $casts = [
        'frontend_ips' => 'array',
        'last_check_data' => 'array',
        'thresholds' => 'array',
        'disable' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::deleting(function ($model) {
            $model->puqPmScripts()->delete();
        });
    }

    public function puqPmLoadBalancer(): BelongsTo
    {
        return $this->belongsTo(PuqPmLoadBalancer::class, 'puq_pm_load_balancer_uuid', 'uuid');
    }

    public function puqPmScripts(): HasMany
    {
        return $this->hasMany(PuqPmScript::class, 'model_uuid', 'uuid')
            ->where('model', self::class);
    }


    // WebProxy

    function getSystemStatus(): array
    {
        if (empty($this->api_url) or empty($this->api_key)) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.API URL or API KEY is empty')],
            ];
        }

        $client = $this->getClient();
        $data = $client->getSystemStatus();
        $this->saveLastCheckData($data);

        return $data;
    }

    private function saveLastCheckData(array $data = []): void
    {
        if (empty($data)) {
            $client = $this->getClient();
            $data = $client->getSystemStatus();
        }

        $lastCheckData = $this->last_check_data ?? [];
        $lastCheckData['time'] = time();
        $lastCheckData['errors'] = [];

        if ($data['status'] === 'error') {
            $lastCheckData['errors'] = $data['errors'] ?? null;
            $lastCheckData['cpu_used_load1'] = null;
            $lastCheckData['cpu_used_load5'] = null;
            $lastCheckData['cpu_used_load15'] = null;
            $lastCheckData['memory_free_megabyte'] = null;
            $lastCheckData['memory_free_percent'] = null;
            $lastCheckData['uptime'] = null;

            $this->last_check_data = $lastCheckData;
            $this->save();

            return;
        }

        $data = $data['data'] ?? [];
        $cpu_threads = intval($data['cpu_threads'] ?? 1);

        $lastCheckData['cpu_used_load1'] = isset($data['cpu_used_load1'])
            ? min(round(floatval($data['cpu_used_load1']) / $cpu_threads * 100, 2), 100)
            : null;
        $lastCheckData['cpu_used_load5'] = isset($data['cpu_used_load5'])
            ? min(round(floatval($data['cpu_used_load5']) / $cpu_threads * 100, 2), 100)
            : null;
        $lastCheckData['cpu_used_load15'] = isset($data['cpu_used_load15'])
            ? min(round(floatval($data['cpu_used_load15']) / $cpu_threads * 100, 2), 100)
            : null;

        $memory_total = intval($data['memory_total'] ?? 0);
        $memory_free = intval($data['memory_free'] ?? 0);

        $lastCheckData['memory_free_megabyte'] = $memory_free ?: null;
        $lastCheckData['memory_free_percent'] = $memory_total > 0
            ? round($memory_free / $memory_total * 100, 2)
            : null;

        $lastCheckData['uptime'] = isset($data['uptime'])
            ? intval($data['uptime'])
            : null;

        $this->last_check_data = $lastCheckData;
        $this->save();
    }

    private function getClient(): puqWebProxyClient
    {
        $conf = [
            'api_url' => $this->api_url,
            'api_key' => $this->api_key,
        ];

        return new puqWebProxyClient($conf, 30);
    }


    public function getThresholdStatus(string $metric): array
    {
        $thresholds = $this->puqPmLoadBalancer->default_thresholds[$metric] ?? [];
        $enabled = $thresholds['enabled'] ?? false;
        $threshold = $thresholds['value'] ?? 99;
        $logic = $thresholds['logic'] ?? '>';
        $value = $this->last_check_data[$metric] ?? 0;

        $triggered = false;
        if ($enabled) {
            $triggered = match ($logic) {
                '>' => $value > $threshold,
                '<' => $value < $threshold,
                '>=' => $value >= $threshold,
                '<=' => $value <= $threshold,
                '==' => $value == $threshold,
                default => false,
            };
        }

        return [
            'enabled' => $enabled,
            'threshold' => $threshold,
            'logic' => $logic,
            'value' => $value,
            'triggered' => $triggered,
        ];
    }

    public function hasTriggeredThreshold(): bool
    {
        $thresholds = $this->puqPmLoadBalancer->default_thresholds;
        foreach ($thresholds as $metric => $threshold) {
            $tmp = $this->getThresholdStatus($metric);
            if ($tmp['enabled'] and $tmp['triggered']) {
                return true;
            }
        }

        return false;
    }

    public function getIpDnsRecords(): array
    {
        $dns_zone_records = [];
        $dns_zone = $this->puqPmLoadBalancer->puqPmDnsZone->getDnsZone();

        if (!empty($dns_zone)) {
            $records = $dns_zone->dnsRecords()->where('name', $this->puqPmLoadBalancer->subdomain)->get();
            foreach ($records as $record) {
                $dns_zone_records[compressIpv6($record->content)] = [
                    'type' => $record->type,
                    'content' => compressIpv6($record->content),
                    'source' => 'dns',
                ];
            }
        }

        $records = [];
        foreach ($this->frontend_ips as $frontendIp) {
            $records[] = [
                'ip' => compressIpv6($frontendIp),
                'propagated' => isset($dns_zone_records[compressIpv6($frontendIp)]),
            ];
        }

        return $records;
    }


    public function deployMainConfig(array $data): array
    {
        if (empty($this->api_url) or empty($this->api_key)) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.API URL or API KEY is empty')],
            ];
        }

        $data['config'] = base64_encode($data['config']);

        $client = $this->getClient();

        return $client->deployMainConfig($data);
    }

    public function deployServiceConfig(array $data): array
    {
        if (empty($this->api_url) or empty($this->api_key)) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.API URL or API KEY is empty')],
            ];
        }

        $client = $this->getClient();

        return $client->deployServiceConfig($data);
    }

    public function removeServiceConfig(array $data): array
    {
        if (empty($this->api_url) or empty($this->api_key)) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.API URL or API KEY is empty')],
            ];
        }

        $client = $this->getClient();

        return $client->removeServiceConfig($data);
    }

    public function removeServiceConfigs(array $data): array
    {
        if (empty($this->api_url) or empty($this->api_key)) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.API URL or API KEY is empty')],
            ];
        }

        $client = $this->getClient();

        return $client->removeServiceConfigs($data);
    }

}
