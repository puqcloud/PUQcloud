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

use App\Models\CertificateAuthority;
use App\Traits\AutoTranslatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PuqPmAppPreset extends Model
{

    use AutoTranslatable;

    protected $table = 'puq_pm_app_presets';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'version',
        'description',
        'env_variables',
        'puq_pm_lxc_preset_uuid',
        'puq_pm_lxc_os_template_uuid',
        'puq_pm_dns_zone_uuid',
        'certificate_authority_uuid',
    ];

    protected $casts = [
        'env_variables' => 'array',
    ];

    public function getEnvironmentMacros(): array
    {
        $macros = getSystemMacros();
        $macros[] = ['name' => 'MAIN_DOMAIN', 'description' => 'Main Domain of APP'];
        $macros[] = ['name' => 'LXC_MOUNT_POINT', 'description' => 'Path to the mount point in the LXC container'];
        $macros[] = ['name' => 'LXC_IP', 'description' => 'Main IP address of LXC container'];

        return $macros;
    }

    protected $translatable = ['custom_page'];

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

    public function puqPmLxcPreset(): BelongsTo
    {
        return $this->belongsTo(PuqPmLxcPreset::class, 'puq_pm_lxc_preset_uuid', 'uuid');
    }

    public function puqPmLxcOsTemplate(): BelongsTo
    {
        return $this->belongsTo(PuqPmLxcOsTemplate::class, 'puq_pm_lxc_os_template_uuid', 'uuid');
    }

    public function puqPmDnsZone(): BelongsTo
    {
        return $this->belongsTo(PuqPmDnsZone::class, 'puq_pm_dns_zone_uuid', 'uuid');
    }

    public function CertificateAuthority(): BelongsTo
    {
        return $this->belongsTo(CertificateAuthority::class, 'certificate_authority_uuid', 'uuid');
    }

    public function puqPmScripts(): HasMany
    {
        return $this->hasMany(PuqPmScript::class, 'model_uuid', 'uuid')
            ->where('model', self::class);
    }

    public function puqPmAppEndpoints(): HasMany
    {
        return $this->hasMany(PuqPmAppEndpoint::class, 'puq_pm_app_preset_uuid', 'uuid');
    }

    public function createAppInstance($lxc_instance): array
    {
        $app_instance = new PuqPmAppInstance();

        $app_instance->env_variables = $this->env_variables;
        $app_instance->puq_pm_lxc_instance_uuid = $lxc_instance->uuid;
        $app_instance->puq_pm_app_preset_uuid = $this->uuid;
        $app_instance->puq_pm_dns_zone_uuid = $this->puq_pm_dns_zone_uuid;

        $puq_pm_cluster = $lxc_instance->puqPmCluster;
        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;
        $puq_pm_load_balancer = PuqPmLoadBalancer::query()->where('puq_pm_cluster_group_uuid',
            $puq_pm_cluster_group->uuid)->first();
        $app_instance->puq_pm_load_balancer_uuid = $puq_pm_load_balancer->uuid;
        $app_instance->save();
        $app_instance->refresh();
        $app_instance->macroReplaceEnvVariables();
        $app_instance->save();

        return [
            'status' => 'success',
            'data' => $app_instance,
        ];
    }


    // Export
    public function exportJson(): array
    {

        $puq_pm_scripts = $this->puqPmScripts;
        $docker_composer = $puq_pm_scripts->where('type', 'docker_composer')->first();
        $install_script = $puq_pm_scripts->where('type', 'install_script')->first();
        $update_script = $puq_pm_scripts->where('type', 'update_script')->first();
        $status_script = $puq_pm_scripts->where('type', 'status_script')->first();

        return [
            'status' => 'success',
            'data' => [
                'file_name' => $this->exportBuildFileName().'.json',
                'name' => $this->name,
                'version' => $this->version,
                'description' => $this->description,
                'env_variables' => $this->env_variables,
                'app_endpoints' => $this->exportBuildAppEndpoints(),
                'docker_composer' => $docker_composer?->script,
                'install_script' => $install_script?->script,
                'update_script' => $update_script?->script,
                'status_script' => $status_script?->script,
                'custom_page' => $this->custom_page
            ],
        ];
    }

    private function exportBuildFileName(): string
    {
        $clean = function (?string $v): string {
            if (!$v) {
                return '';
            }
            $v = str_replace(' ', '_', $v);
            $v = preg_replace('/[\/\\\\\?\%\*\:\|\"\<\>]/', '_', $v);

            return $v;
        };

        $parts = [
            $clean($this->name),
            $clean($this->version),
            $clean($this->uuid),
        ];

        return implode('-', array_filter($parts));
    }

    private function exportBuildAppEndpoints(): array
    {
        $app_endpoints = [];

        foreach ($this->puqPmAppEndpoints as $puq_pm_app_endpoint) {
            $app_endpoint_tmp = [
                'name' => $puq_pm_app_endpoint->name,
                'subdomain' => $puq_pm_app_endpoint->subdomain,
                'server_custom_config_before' => $puq_pm_app_endpoint->server_custom_config_before,
                'server_custom_config' => $puq_pm_app_endpoint->server_custom_config,
                'server_custom_config_after' => $puq_pm_app_endpoint->server_custom_config_after,
                'puq_pm_app_endpoint_location' => [],
            ];

            foreach ($puq_pm_app_endpoint->puqPmAppEndpointLocations as $puq_pm_app_endpoint_location) {
                $app_endpoint_location = [
                    'path' => $puq_pm_app_endpoint_location->path,
                    'show_to_client' => $puq_pm_app_endpoint_location->show_to_client,
                    'proxy_protocol' => $puq_pm_app_endpoint_location->proxy_protocol,
                    'proxy_port' => $puq_pm_app_endpoint_location->proxy_port,
                    'proxy_path' => $puq_pm_app_endpoint_location->proxy_path,
                    'custom_config' => $puq_pm_app_endpoint_location->custom_config,
                ];
                $app_endpoint_tmp['puq_pm_app_endpoint_location'][] = $app_endpoint_location;
            }

            $app_endpoints[] = $app_endpoint_tmp;
        }

        return $app_endpoints;

    }

    // Import
    public function importJson(array $data): array
    {
        // --- Update base fields ---
        $this->name = $data['name'] ?? $this->name;
        $this->version = $data['version'] ?? $this->version;
        $this->description = "imported: " . now()->format('Y-m-d H:i:s') . "\n"
            . ($data['description'] ?? $this->description);
        $this->env_variables = $data['env_variables'] ?? [];
        $this->custom_page = $data['custom_page'] ?? '';
        $this->save();

        // --- Import endpoints ---
        if (!empty($data['app_endpoints'])) {
            $this->importAppEndpoints($data['app_endpoints']);
        }

        // --- Import scripts ---
        $this->importScripts($data);

        return ['status' => 'success'];
    }

    private function importAppEndpoints(array $endpoints): void
    {
        // clear old
        $this->puqPmAppEndpoints()->delete();

        foreach ($endpoints as $ep) {
            $endpoint = $this->puqPmAppEndpoints()->create([
                'name' => $ep['name'] ?? '',
                'subdomain' => $ep['subdomain'] ?? '',
                'server_custom_config_before' => $ep['server_custom_config_before'] ?? '',
                'server_custom_config' => $ep['server_custom_config'] ?? '',
                'server_custom_config_after' => $ep['server_custom_config_after'] ?? '',
            ]);

            if (!empty($ep['puq_pm_app_endpoint_location'])) {

                foreach ($ep['puq_pm_app_endpoint_location'] as $loc) {
                    $endpoint->puqPmAppEndpointLocations()->create([
                        'path' => $loc['path'] ?? '/',
                        'show_to_client' => $loc['show_to_client'] ?? false,
                        'proxy_protocol' => $loc['proxy_protocol'] ?? 'http',
                        'proxy_port' => $loc['proxy_port'] ?? null,
                        'proxy_path' => $loc['proxy_path'] ?? '',
                        'custom_config' => $loc['custom_config'] ?? '',
                    ]);
                }
            }
        }
    }

    private function importScripts(array $data): void
    {
        $script_types = [
            'docker_composer' => 'docker_composer',
            'install_script' => 'install_script',
            'update_script' => 'update_script',
            'status_script' => 'status_script',
        ];

        foreach ($script_types as $key => $type) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $this->puqPmScripts()->updateOrCreate(
                ['type' => $type],
                [
                    'script' => $data[$key] ?? '',
                    'model' => self::class,
                ]
            );
        }
    }
}

