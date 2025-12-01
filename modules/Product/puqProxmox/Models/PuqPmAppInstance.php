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

use App\Models\SslCertificate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PuqPmAppInstance extends Model
{
    protected $table = 'puq_pm_app_instances';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'env_variables',
        'disk_status',
        'puq_pm_lxc_instance_uuid',
        'puq_pm_app_preset_uuid',
        'puq_pm_load_balancer_uuid',
        'puq_pm_dns_zone_uuid',
    ];

    protected $casts = [
        'env_variables' => 'array',
        'disk_status' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

    }

    public function puqPmLxcInstance(): BelongsTo
    {
        return $this->belongsTo(PuqPmLxcInstance::class, 'puq_pm_lxc_instance_uuid', 'uuid');
    }

    public function puqPmAppPreset(): BelongsTo
    {
        return $this->belongsTo(PuqPmAppPreset::class, 'puq_pm_app_preset_uuid', 'uuid');
    }

    public function puqPmLoadBalancer(): BelongsTo
    {
        return $this->belongsTo(PuqPmLoadBalancer::class, 'puq_pm_load_balancer_uuid', 'uuid');
    }

    public function puqPmDnsZone(): BelongsTo
    {
        return $this->belongsTo(PuqPmDnsZone::class, 'puq_pm_dns_zone_uuid', 'uuid');
    }

    // ----------------------------
    // DEPLOY FIELD METHODS
    // ----------------------------

    // Set deploy status
    public function setDeployStatus(string $status): void
    {
        if (!$this->uuid) {
            return;
        }
        self::where('uuid', $this->uuid)->update(['deploy_status' => $status]);
    }

    // Set deploy progress (0-100)
    public function setDeployProgress(int $progress): void
    {
        if (!$this->uuid) {
            return;
        }
        self::where('uuid', $this->uuid)->update(['deploy_progress' => $progress]);
    }

    // Set deploy start and finish times
    public function setDeployStarted(): void
    {
        if (!$this->uuid) {
            return;
        }
        self::where('uuid', $this->uuid)->update(['deploy_started_at' => now()]);
    }

    public function setDeployFinished(): void
    {
        if (!$this->uuid) {
            return;
        }
        self::where('uuid', $this->uuid)->update(['deploy_finished_at' => now()]);
    }

    // Set current deploy step
    public function setDeployStep(string $step): void
    {
        if (!$this->uuid) {
            return;
        }
        self::where('uuid', $this->uuid)->update(['deploy_current_step' => $step]);
    }

    // Set deploy error
    public function setDeployError(string $error): void
    {
        if (!$this->uuid) {
            return;
        }
        self::where('uuid', $this->uuid)->update(['deploy_error' => $error]);
    }

    // Append log line with timestamp safely
    public function appendDeployLog(string $line): void
    {
        if (!$this->uuid) {
            return;
        }

        $timestamp = now()->format('Y-m-d H:i:s');
        $newLogLine = "[$timestamp] $line\n";

        self::where('uuid', $this->uuid)->update([
            'deploy_logs' => \DB::raw("CONCAT(IFNULL(deploy_logs, ''), ".\DB::getPdo()->quote($newLogLine).")"),
        ]);
    }

    public function getDeployStatus(): array
    {
        $fresh = self::find($this->uuid);

        if (!$fresh) {
            return [
                'status' => null,
                'progress' => null,
                'started_at' => null,
                'finished_at' => null,
                'current_step' => null,
                'error' => null,
                'logs' => null,
            ];
        }

        return [
            'status' => $fresh->deploy_status,
            'progress' => $fresh->deploy_progress,
            'started_at' => $fresh->deploy_started_at,
            'finished_at' => $fresh->deploy_finished_at,
            'current_step' => $fresh->deploy_current_step,
            'error' => $fresh->deploy_error,
            'logs' => $fresh->deploy_logs,
        ];
    }

    //-------------------------------------------------------------------------

    public function getInfo(): array
    {
        $load_balancer = $this->puqPmLoadBalancer;
        $info = [
            'endpoints' => $this->getEndpoints(),
            'env_variables' => $this->env_variables,
            'load_balancer' => [
                'uuid' => $load_balancer->uuid,
                'name' => $load_balancer->name,
                'dns_record' => $load_balancer->getDnsRecord(),
            ],
        ];

        $ssl_certificate = $this->findSslCertificate();

        if (!empty($ssl_certificate)) {
            $info['ssl_certificate'] = [
                'status' => $ssl_certificate->status,
                'issued_at' => $ssl_certificate->issued_at,
                'expires_at' => $ssl_certificate->expires_at,
            ];
        }

        return $info;
    }

    public function getDomains(): array
    {
        $puq_pm_app_preset = $this->puqPmAppPreset;
        $puq_pm_app_endpoints = $puq_pm_app_preset->puqPmAppEndpoints;
        $main_domain = $this->getEndpointMainDomain();
        $domains = [];

        foreach ($puq_pm_app_endpoints as $puq_pm_app_endpoint) {
            $domain = $main_domain;
            if (!empty($puq_pm_app_endpoint->subdomain)) {
                $domain = $puq_pm_app_endpoint->subdomain.'.'.$domain;
            }
            $domains[$domain] = $domain;
        }

        return array_values($domains);
    }

    public function getEndpointMainDomain(): string
    {
        $puq_pm_dns_zone = $this->puqPmDnsZone;
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;

        return $puq_pm_lxc_instance->hostname.'.'.$puq_pm_dns_zone->name;
    }

    public function getLxcIP(): string
    {
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;

        return $puq_pm_lxc_instance->getIPv4();
    }

    public function getEndpoints(): array
    {
        $puq_pm_app_preset = $this->puqPmAppPreset;
        $puq_pm_app_endpoints = $puq_pm_app_preset->puqPmAppEndpoints;
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $main_domain = $this->getEndpointMainDomain();
        $lxc_instance_info = $puq_pm_lxc_instance->getInfo();
        $endpoints = [];

        foreach ($puq_pm_app_endpoints as $puq_pm_app_endpoint) {

            foreach ($puq_pm_app_endpoint->puqPmAppEndpointLocations as $puq_pm_app_endpoint_location) {

                $url = 'https://';
                if (!empty($puq_pm_app_endpoint->subdomain)) {
                    $url .= $puq_pm_app_endpoint->subdomain.'.';
                }

                $url .= $main_domain.'/'.ltrim($puq_pm_app_endpoint_location->path, '/');

                $path = $puq_pm_app_endpoint_location->proxy_path;

                if (!$path) {
                    $final_path = '';
                } elseif ($path === '/') {
                    $final_path = '/';
                } else {
                    $final_path = '/'.ltrim($path, '/');
                }

                $proxy_pass = $puq_pm_app_endpoint_location->proxy_protocol
                    .'://'
                    .$lxc_instance_info['ipv4']
                    .':'
                    .$puq_pm_app_endpoint_location->proxy_port
                    .$final_path;

                $endpoints[] = [
                    'url' => $url,
                    'proxy_pass' => $proxy_pass,
                    'show_to_client' => $puq_pm_app_endpoint_location->show_to_client,
                ];
            }
        }

        return $endpoints;
    }

    public function getNginxConfigData(): array
    {
        $puq_pm_app_preset = $this->puqPmAppPreset;
        $puq_pm_app_endpoints = $puq_pm_app_preset->puqPmAppEndpoints;
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $main_domain = $this->getEndpointMainDomain();
        $lxc_instance_info = $puq_pm_lxc_instance->getInfo();

        $ssl_certificate = $this->findSslCertificate();
        $endpoints = [];

        foreach ($puq_pm_app_endpoints as $puq_pm_app_endpoint) {
            $subdomain = '';
            if (!empty($puq_pm_app_endpoint->subdomain)) {
                $subdomain .= $puq_pm_app_endpoint->subdomain.'.';
            }
            $locations = [];
            foreach ($puq_pm_app_endpoint->puqPmAppEndpointLocations as $puq_pm_app_endpoint_location) {

                $path = $puq_pm_app_endpoint_location->proxy_path;

                if (!$path) {
                    $final_path = '';
                } elseif ($path === '/') {
                    $final_path = '/';
                } else {
                    $final_path = '/'.ltrim($path, '/');
                }

                $proxy_pass = $puq_pm_app_endpoint_location->proxy_protocol
                    .'://'
                    .$lxc_instance_info['ipv4']
                    .':'
                    .$puq_pm_app_endpoint_location->proxy_port
                    .$final_path;

                $locations[] = [
                    'path' => $puq_pm_app_endpoint_location->path,
                    'proxy_pass' => $proxy_pass,
                    'custom_config' => $puq_pm_app_endpoint_location->custom_config,
                ];
            }

            $endpoints[] = [
                'domain' => $subdomain.$main_domain,
                'server_custom_config_before' => $puq_pm_app_endpoint->server_custom_config_before,
                'server_custom_config' => $puq_pm_app_endpoint->server_custom_config,
                'server_custom_config_after' => $puq_pm_app_endpoint->server_custom_config_after,
                'ssl_certificate' => $ssl_certificate?->certificate_pem ?? '',
                'ssl_certificate_key' => $ssl_certificate?->private_key_pem ?? '',
                'locations' => $locations,
            ];
        }

        return $endpoints;
    }

    public function putDockerComposer(): array
    {
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $puq_pm_app_preset = $this->PuqPmAppPreset;

        $puq_pm_script = $puq_pm_app_preset
            ->puqPmScripts()
            ->where('type', 'docker_composer')
            ->first();

        $docker_composer = '';
        if (!empty($puq_pm_script)) {
            $docker_composer = $puq_pm_script->script;
        }

        $dockerBase64 = base64_encode($docker_composer);

        $script = <<<BASH
#!/bin/bash
set -euo pipefail

mkdir -p /etc/puqcloud 2>/dev/null
if ! echo "$dockerBase64" | base64 -d > /etc/puqcloud/docker-compose.yml; then
    echo "error: failed to write docker-compose.yml"
    exit 1
fi

if ! chmod 600 /etc/puqcloud/docker-compose.yml; then
    echo "error: failed to set permissions"
    exit 1
fi

echo "success"
BASH;

        return $puq_pm_lxc_instance->runSshScriptOnLxc($script);
    }

    public function putEnvironmentVariables(): array
    {
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $env_variables = $this->env_variables; // array: ['KEY' => 'value']

        $envText = '';
        foreach ($env_variables as $variable) {
            $envText .= $variable['key'].'='.$variable['value']."\n";
        }

        $envBase64 = base64_encode($envText);

        $script = <<<BASH
#!/bin/bash
set -euo pipefail

mkdir -p /etc/puqcloud 2>/dev/null
if ! echo "$envBase64" | base64 -d > /etc/puqcloud/env.list; then
    echo "error: failed to write env.list"
    exit 1
fi

if ! chmod 600 /etc/puqcloud/env.list; then
    echo "error: failed to set permissions"
    exit 1
fi

echo "success"
BASH;

        return $puq_pm_lxc_instance->runSshScriptOnLxc($script);
    }

    public function runInstallScript(): array
    {
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $puq_pm_app_preset = $this->PuqPmAppPreset;

        $puq_pm_script = $puq_pm_app_preset
            ->puqPmScripts()
            ->where('type', 'install_script')
            ->first();


        $script = '';
        if (!empty($puq_pm_script)) {
            $script = $puq_pm_script->script;
        }

        return $puq_pm_lxc_instance->runSshScriptOnLxc($script, $puq_pm_script);
    }

    public function getDiskStatus(bool $force = false): void
    {
        $cacheKey = 'disk_status_'.$this->uuid;

        if (!$force) {
            $disk_status = cache()->get($cacheKey);
            if ($disk_status) {
                $this->disk_status = $disk_status;

                return;
            }
        }

        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $puq_pm_lxc_preset = $puq_pm_lxc_instance->puqPmLxcPreset;
        $mp = $puq_pm_lxc_preset->mp;

        $script = <<<BASH
#!/bin/bash
df --block-size=1 --output=size,used,pcent $mp | tail -n1
BASH;

        $status = $puq_pm_lxc_instance->runSshScriptOnLxc($script);

        if ($status['status'] !== 'success' || empty($status['data'])) {
            return;
        }

        $raw = trim($status['data']);
        $parts = preg_split('/\s+/', $raw);

        $disk_status = [
            'size' => (int) $parts[0],
            'used' => (int) $parts[1],
            'percent' => (int) str_replace('%', '', $parts[2]),
        ];

        $this->disk_status = $disk_status;
        $this->save();

        cache()->put($cacheKey, $disk_status, 300);
    }


    protected function findSslCertificate(): ?SslCertificate
    {
        $domain = $this->getEndpointMainDomain() ?? '';
        $aliases = $this->getDomains();
        $aliases = array_values(array_filter($aliases, fn($d) => $d !== $domain));

        if (empty($aliases)) {
            $parts = explode('.', $domain);
            array_shift($parts);
            $wildcardDomain = implode('.', $parts);

            $wildcard = SslCertificate::query()
                ->where('domain', $wildcardDomain)
                ->where('status', 'active')
                ->orderByDesc('expires_at')
                ->first();

            if ($wildcard && !$wildcard->isExpired()) {
                return $wildcard;
            }
        }

        $certificates = SslCertificate::query()
            ->where('domain', $domain)
            ->where('status', 'active')
            ->orderByDesc('expires_at')
            ->get();

        foreach ($certificates as $ssl_certificate) {
            $certAliases = $ssl_certificate->aliases ?? [];
            if (!is_array($certAliases)) {
                $certAliases = json_decode($certAliases, true) ?? [];
            }

            if (!$ssl_certificate->isExpired() && empty(array_diff($aliases, $certAliases))) {
                return $ssl_certificate;
            }
        }

        return null;
    }

    public function createSslCertificates(): array
    {
        $puq_pm_app_preset = $this->PuqPmAppPreset;

        $domain = $this->getEndpointMainDomain() ?? '';
        $aliases = $this->getDomains();
        $aliases = array_values(array_filter($aliases, fn($d) => $d !== $domain));

        if (empty($aliases) && $domain === '') {
            return [
                'status' => 'error',
                'errors' => ['No domains found'],
            ];
        }

        $ssl_certificate = $this->findSslCertificate();

        if ($ssl_certificate) {
            return [
                'status' => 'success',
            ];
        }

        $ssl_certificate = new SslCertificate;
        $ssl_certificate->domain = $domain;
        $ssl_certificate->certificate_authority_uuid = $puq_pm_app_preset->certificate_authority_uuid;
        $ssl_certificate->aliases = $aliases;
        $ssl_certificate->configuration = json_encode([]);
        $ssl_certificate->save();
        $ssl_certificate->refresh();

        return $ssl_certificate->generateCsr();
    }

    public function createDnsRecords(): array
    {
        return $this->processDnsRecords(true);
    }

    public function deleteDnsRecords(): array
    {
        return $this->processDnsRecords(false);
    }

    public function deleteScriptLogs(): void
    {
        $this->puqPmLxcInstance->puqPmScriptLogs()->delete();
    }

    private function processDnsRecords(bool $createRecords): array
    {
        $dns_zone = $this->puqPmDnsZone->getDnsZone();

        if (empty($dns_zone)) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.No DNS Zone found')],
            ];
        }

        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $puq_pm_load_balancer = $this->puqPmLoadBalancer;
        $puq_pm_app_preset = $this->puqPmAppPreset;
        $puq_pm_app_endpoints = $puq_pm_app_preset->puqPmAppEndpoints;

        $domains[$puq_pm_lxc_instance->hostname] = $puq_pm_lxc_instance->hostname;
        foreach ($puq_pm_app_endpoints as $puq_pm_app_endpoint) {
            if (!empty($puq_pm_app_endpoint->subdomain)) {
                $domain = $puq_pm_app_endpoint->subdomain.'.'.$puq_pm_lxc_instance->hostname;
                $domains[$domain] = $domain;
            }
        }

        $record_names = array_values($domains);

        foreach ($record_names as $record) {
            $old_dns_records = $dns_zone->dnsRecords()->where('name', $record)->get();
            foreach ($old_dns_records as $old_dns_record) {
                $dns_zone->deleteRecord($old_dns_record->uuid);
            }

            if ($createRecords) {
                $data = [
                    'name' => $record,
                    'type' => 'CNAME',
                    'ttl' => $puq_pm_load_balancer->dns_record_ttl ?? 30,
                    'description' => 'Created by PUQ Proxmox APP',
                    'target' => $puq_pm_load_balancer->getDnsRecord(),
                ];
                $dns_zone->createUpdateRecord($data);
            }
        }

        return [
            'status' => 'success',
        ];
    }

    public function deployResourceConfig(): array
    {
        $puq_pm_load_balancer = $this->puqPmLoadBalancer;

        $configs = $this->getNginxConfigData();
        $lastIndex = array_key_last($configs);

        $errors = [];

        foreach ($configs as $i => $data) {

            $data['reload'] = ($i === $lastIndex);

            $deploy = $puq_pm_load_balancer->deployResourceConfig($data);

            if ($deploy['status'] === 'error') {
                foreach ($deploy['errors'] as $err) {
                    $errors[] = $data['domain'].': '.$err;
                }
            }
        }

        if ($errors) {
            return [
                'status' => 'error',
                'errors' => $errors,
            ];
        }

        return ['status' => 'success'];
    }


    public function removeResourceConfig(): array
    {
        $puq_pm_load_balancer = $this->puqPmLoadBalancer;

        $errors = [];
        foreach ($this->getNginxConfigData() as $data) {
            $remove = $puq_pm_load_balancer->removeResourceConfig($data);

            if ($remove['status'] === 'error') {
                foreach ($remove['errors'] as $err) {
                    $errors[] = $data['domain'].': '.$err;
                }
            }
        }

        if (!empty($errors)) {
            return [
                'status' => 'error',
                'errors' => $errors,
            ];
        }

        return [
            'status' => 'success',
        ];
    }

    public function webProxyDeploy(): array
    {
        // --- Config ---
        $maxAttempts = 200;     // how many attempts
        $sleepSeconds = 10;     // wait time between attempts

        for ($i = 0; $i < $maxAttempts; $i++) {

            $ssl_certificate = $this->findSslCertificate();

            if ($ssl_certificate) {
                return $this->deployResourceConfig();
            }

            if ($i < $maxAttempts - 1) {
                sleep($sleepSeconds);
            }
        }

        return [
            'status' => 'error',
            'errors' => [__('Product.puqProxmox.No SSL Certificate found')],
        ];
    }

    public function macroReplace(string $pattern): string
    {
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $puq_pm_lxc_preset = $puq_pm_lxc_instance->puqPmLxcPreset;
        $puq_pm_cluster = $puq_pm_lxc_instance->puqPmCluster;
        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;
        $country = $puq_pm_cluster_group->getCountry();

        $pattern = macroReplace($pattern, $country);

        $replacements = [
            '{MAIN_DOMAIN}' => $this->getEndpointMainDomain() ?? '',
            '{LXC_MOUNT_POINT}' => $puq_pm_lxc_preset->mp,
            '{LXC_IP}' => $this->getLxcIP() ?? '127.0.0.1',
        ];

        $result = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        return $result;
    }

    public function macroReplaceEnvVariables(): void
    {
        $env_variables = [];
        foreach ($this->env_variables as $item) {

            $env_variables[] = [
                'key' => $item['key'],
                'value' => $this->macroReplace($item['value']),
            ];
        }

        $this->env_variables = $env_variables;
    }

    // Client Area

    public function getAppInfoClientArea(): array
    {
        $puq_pm_lxc_instance = $this->puqPmLxcInstance;
        $lxc_info = $puq_pm_lxc_instance->getInfo();
        $lxc_location = $puq_pm_lxc_instance->getLocation();
        $lxc_status = $puq_pm_lxc_instance->getStatus();
        $lxc_disk_status = $this->disk_status;

        return [
            'cores' => $lxc_info['cores'] ?? 0,
            'cpu_used_percent' => ($lxc_status['cpu'] ?? 0) * 100,

            'ram' => $lxc_info['ram'] ?? 0,
            'memory_used_percent' => $lxc_status['memory'] ?? 0,
            'memory_total' => $lxc_status['memory_max'] ?? 0,
            'memory_used' => $lxc_status['memory_used'] ?? 0,
            'memory_free' => ($lxc_status['memory_max'] ?? 0) - ($lxc_status['memory_used'] ?? 0),

            'disk' => $lxc_info['addition_disk'] ?? 0,
            'disk_used_percent' => $lxc_disk_status['percent'] ?? 0,
            'disk_total' => $lxc_disk_status['size'] ?? 0,
            'disk_used' => $lxc_disk_status['used'] ?? 0,
            'disk_free' => ($lxc_disk_status['size'] ?? 0) - ($lxc_disk_status['used'] ?? 0),

            'backups' => $lxc_info['backups'] ?? 0,

            'location' => $lxc_location['name'] ?? '',
            'location_description' => $lxc_location['description'] ?? '',
            'location_short_description' => $lxc_location['short_description'] ?? '',
            'location_icon_url' => $lxc_location['icon_url'] ?? '',
            'location_background_url' => $lxc_location['background_url'] ?? '',
            'location_data_center' => $lxc_location['data_center'] ?? '',
        ];

    }

    private function getEnvVariablesClientArea(): array
    {
        $puq_pm_app_preset = $this->puqPmAppPreset;
        $preset_env_vars = $puq_pm_app_preset->env_variables;
        $instance_env_vars = $this->env_variables;
        $client_env_variables = [];

        foreach ($preset_env_vars as $preset) {
            if (!empty($preset['show_to_client']) && $preset['show_to_client'] === true) {
                $instance_value = null;
                foreach ($instance_env_vars as $instance) {
                    if ($instance['key'] === $preset['key']) {
                        $instance_value = $instance['value'];
                        break;
                    }
                }

                $client_env_variables[] = [
                    'key' => $preset['key'],
                    'value' => $instance_value ?? $preset['value'],
                    'custom_name' => $preset['custom_name'] ?? '',
                    'edit_by_client' => $preset['edit_by_client'] ?? false,
                ];
            }
        }

        return $client_env_variables;
    }

    private function getEndpointsClientArea(): array
    {
        $endpoints = [];
        foreach ($this->getEndpoints() as $endpoint) {
            if ($endpoint['show_to_client']) {
                $endpoints[] = [
                    'url' => $endpoint['url'],
                ];
            }
        }

        return $endpoints;
    }

    public function getAppControlClientArea(): array
    {
        return [
            'endpoints' => $this->getEndpointsClientArea(),
            'env_variables' => $this->getEnvVariablesClientArea(),
        ];
    }


}
