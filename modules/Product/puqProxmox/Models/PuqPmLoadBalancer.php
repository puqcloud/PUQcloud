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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PuqPmLoadBalancer extends Model
{
    protected $table = 'puq_pm_load_balancers';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'subdomain',
        'dns_record_ttl',
        'default_thresholds',
        'strategy',
        'puq_pm_cluster_group_uuid',
        'puq_pm_dns_zone_uuid',
    ];

    protected $casts = [
        'default_thresholds' => 'array',
    ];

    protected array $scripts = [
        'nginx-conf' => [
            'variables' => [
                [
                    'name' => 'LOAD_BALANCER_DOMAIN',
                    'description' => 'Primary domain used by the load balancer to route incoming traffic.',
                ],
            ],

            'default' => <<<CONF
#######################################################################
# PUQcloud default nginx.conf
# v1.0
#######################################################################

load_module /etc/nginx/modules/ngx_http_vhost_traffic_status_module.so;

user www-data;
worker_processes auto;
pid /run/nginx.pid;
error_log /var/log/nginx/error.log;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 768;
}

http {
    sendfile on;
    tcp_nopush on;
    types_hash_max_size 2048;
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    ssl_protocols TLSv1 TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    access_log /var/log/nginx/access.log;
    gzip on;

    vhost_traffic_status on;
    vhost_traffic_status_zone;

    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
CONF
            ,
        ],

        'service-conf' => [
            'variables' => [
                [
                    'name' => 'LOAD_BALANCER_DOMAIN',
                    'description' => 'Primary domain used by the load balancer to route incoming traffic.',
                ],
            ],

            'default' => <<<CONF
#######################################################################
# PUQcloud default service.conf
# v1.0
#######################################################################
server {
    listen unix:/var/run/nginx.sock;

    location /nginx_status {
        stub_status;
    }

    location /vts_status {
        vhost_traffic_status_display;
        vhost_traffic_status_display_format json;
    }
}

server {
    listen 4555;
    server_name {LOAD_BALANCER_DOMAIN};

    location /nginx_status {
        stub_status;
    }

    location /status/html {
        vhost_traffic_status_display;
        vhost_traffic_status_display_format html;
    }

    location /status/json {
        vhost_traffic_status_display;
        vhost_traffic_status_display_format json;
    }
}

CONF
            ,
        ],

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

    public function puqPmClusterGroup(): BelongsTo
    {
        return $this->belongsTo(PuqPmClusterGroup::class, 'puq_pm_cluster_group_uuid', 'uuid');
    }

    public function puqPmDnsZone(): BelongsTo
    {
        return $this->belongsTo(PuqPmDnsZone::class, 'puq_pm_dns_zone_uuid', 'uuid');
    }

    public function puqPmScripts(): HasMany
    {
        return $this->hasMany(PuqPmScript::class, 'model_uuid', 'uuid')
            ->where('model', self::class);
    }

    public function puqPmWebProxies(): HasMany
    {
        return $this->hasMany(PuqPmWebProxy::class, 'puq_pm_load_balancer_uuid', 'uuid');
    }

    public function getDefaultThresholdsAttribute($value): array
    {
        $defaults = [
            'cpu_used_load1' => ['enabled' => false, 'logic' => '>', 'value' => 90],
            'cpu_used_load5' => ['enabled' => false, 'logic' => '>', 'value' => 90],
            'cpu_used_load15' => ['enabled' => false, 'logic' => '>', 'value' => 90],
            'memory_free_megabyte' => ['enabled' => false, 'logic' => '<', 'value' => 100],
            'memory_free_percent' => ['enabled' => false, 'logic' => '<', 'value' => 10],
            'uptime' => ['enabled' => false, 'logic' => '<', 'value' => 600],
        ];

        $data = is_array($value) ? $value : json_decode($value, true);

        return array_replace_recursive($defaults, $data ?? []);
    }

    public function getDnsRecord(): string
    {
        $dns_zone = $this->puqPmDnsZone;

        return $this->subdomain.'.'.$dns_zone->name;
    }

    public function getDnsRecords(): array
    {
        $dns_zone_records = [];
        $dns_zone = $this->puqPmDnsZone->getDnsZone();

        if (!empty($dns_zone)) {
            $records = $dns_zone->dnsRecords()->where('name', $this->subdomain)->get();
            foreach ($records as $record) {
                $dns_zone_records[$record->content] = [
                    'type' => $record->type,
                    'content' => $record->content,
                    'source' => 'dns',
                ];
            }
        }

        $puq_pm_web_proxy_ips = [];
        foreach ($this->puqPmWebProxies as $PuqPmWebProxy) {
            foreach ($PuqPmWebProxy->frontend_ips as $frontend_ip) {
                if (filter_var($frontend_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $type = 'A';
                } elseif (filter_var($frontend_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $type = 'AAAA';
                } else {
                    $type = 'UNKNOWN';
                }

                $puq_pm_web_proxy_ips[$frontend_ip] = [
                    'puq_pm_web_proxy' => $PuqPmWebProxy,
                    'type' => $type,
                    'content' => $frontend_ip,
                    'source' => 'proxy',
                ];
            }
        }

        $records = [];

        foreach ($puq_pm_web_proxy_ips as $ip => $proxy_record) {
            $puq_pm_web_proxy = $proxy_record['puq_pm_web_proxy'];
            $records[$ip] = [
                'ip' => $ip,
                'type' => $proxy_record['type'],
                'propagated' => isset($dns_zone_records[$ip]),
                'puq_pm_web_proxy' => [
                    'uuid' => $puq_pm_web_proxy->uuid,
                    'name' => $puq_pm_web_proxy->name,
                ],
            ];
        }

        foreach ($dns_zone_records as $ip => $dns_record) {
            if (!isset($records[$ip])) {
                $records[$ip] = [
                    'ip' => $ip,
                    'type' => $dns_record['type'],
                    'propagated' => true,
                    'puq_pm_web_proxy' => null,
                ];
            }
        }

        return array_values($records);
    }

    public function getWebProxiesSystemStatus(): void
    {
        foreach ($this->puqPmWebProxies as $puq_pm_web_proxy) {
            $puq_pm_web_proxy->getSystemStatus();
        }

    }

    public function rebalance(): array
    {
        $dns_zone = $this->puqPmDnsZone?->getDnsZone();
        if (empty($dns_zone)) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.No DNS Zone found')],
            ];
        }

        $puq_pm_web_proxies = $this->puqPmWebProxies()->where('disable', false)->get();

        if ($puq_pm_web_proxies->isEmpty()) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.No Enabled WEB Proxies found')],
            ];
        }

        $thresholds = 0;
        foreach ($puq_pm_web_proxies as $puq_pm_web_proxy) {
            if ($puq_pm_web_proxy->hasTriggeredThreshold()) {
                $thresholds++;
            }
        }
        if ($puq_pm_web_proxies->count() === $thresholds) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.All WEB Proxies has been threshold triggered')],
            ];
        }

        $old_dns_records = $dns_zone->dnsRecords()->where('name', $this->subdomain)->get();
        $keep_uuids = [];

        foreach ($puq_pm_web_proxies as $puq_pm_web_proxy) {

            if ($puq_pm_web_proxy->hasTriggeredThreshold()) {
                continue;
            }

            foreach ($puq_pm_web_proxy->frontend_ips as $frontend_ip) {
                $data = [
                    'name' => $this->subdomain,
                    'ttl' => $this->dns_record_ttl ?? 30,
                    'description' => 'Created by PUQ Proxmox Load Balancer',
                ];

                if (filter_var($frontend_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $data['type'] = 'AAAA';
                    $data['ipv6'] = compressIpv6($frontend_ip);

                    $existing = $old_dns_records->first(fn($r
                    ) => $r->type === 'AAAA' && compressIpv6($r->content) === $data['ipv6'] && $r->ttl === $data['ttl']);
                    if ($existing) {
                        $keep_uuids[] = $existing->uuid;
                        continue;
                    }

                } elseif (filter_var($frontend_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $data['type'] = 'A';
                    $data['ipv4'] = $frontend_ip;

                    $existing = $old_dns_records->first(fn($r
                    ) => $r->type === 'A' && $r->content === $data['ipv4'] && $r->ttl === $data['ttl']);
                    if ($existing) {
                        $keep_uuids[] = $existing->uuid;
                        continue;
                    }

                } else {
                    continue;
                }

                $dns_zone->createUpdateRecord($data);
            }
        }

        foreach ($old_dns_records as $old_dns_record) {
            if (!in_array($old_dns_record->uuid, $keep_uuids)) {
                $dns_zone->deleteRecord($old_dns_record->uuid);
            }
        }

        return ['status' => 'success'];
    }


    public function deployAll(): array
    {
        $errors = [];
        $data = [
            'reload' => false,
        ];

        $remove = $this->removeResourceConfigs($data);
        if ($remove['status'] === 'error') {
            $errors = array_merge($errors, $remove['errors']);
        }

        $deploy_main_config = $this->deployMainConfig();
        if ($deploy_main_config['status'] === 'error') {
            $errors = array_merge($errors, $deploy_main_config['errors']);
        }

        $puq_pm_app_instances = PuqPmAppInstance::query()->where('deploy_status', 'success')->get();

        foreach ($puq_pm_app_instances as $puq_pm_app_instance) {
            $configs = $puq_pm_app_instance->getNginxConfigData();
            foreach ($configs as $data) {

                $data['reload'] = false;

                $deploy = $this->deployResourceConfig($data);

                if ($deploy['status'] === 'error') {
                    foreach ($deploy['errors'] as $err) {
                        $errors[] = $data['domain'].': '.$err;
                    }
                }
            }

        }

        $deploy_service_config = $this->deployServiceConfig(true);
        if ($deploy_service_config['status'] === 'error') {
            $errors = array_merge($errors, $deploy_service_config['errors']);
        }

        if ($errors) {
            return [
                'status' => 'error',
                'errors' => $errors,
            ];
        }

        return ['status' => 'success'];
    }

    public
    function deployMainConfig(
        bool $reload = false
    ): array {
        $puq_pm_script = $this->puqPmScripts()->where('type', 'nginx-conf')->first();
        $config = $puq_pm_script?->script ?? '';

        $data = [
            'config' => $this->macroReplace($config),
            'reload' => $reload,
        ];

        $errors = [];
        foreach ($this->puqPmWebProxies as $puq_pm_web_proxy) {
            $deploy = $puq_pm_web_proxy->deployMainConfig($data);
            if ($deploy['status'] === 'error') {
                foreach ($deploy['errors'] as $err) {
                    $errors[] = $puq_pm_web_proxy->name.': '.$err;
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

    public
    function deployServiceConfig(
        bool $reload = false
    ): array {
        $puq_pm_script = $this->puqPmScripts()->where('type', 'service-conf')->first();
        $config = $puq_pm_script?->script ?? '';
        $config = $this->macroReplace($config);

        $ssl_certificate_model = $this->findSslCertificate();
        if ($ssl_certificate_model) {
            $ssl_certificate = base64_encode($ssl_certificate_model->certificate_pem);
            $ssl_certificate_key = base64_encode($ssl_certificate_model->private_key_pem);
        }

        $data = [
            'filename' => 'service.conf',
            'domain' => $this->getDnsRecord(),
            'config' => base64_encode($config),
            'ssl_certificate' => $ssl_certificate ?? '',
            'ssl_certificate_key' => $ssl_certificate_key ?? '',
            'reload' => $reload,
        ];

        $errors = [];
        foreach ($this->puqPmWebProxies as $puq_pm_web_proxy) {
            $deploy = $puq_pm_web_proxy->deployServiceConfig($data);
            if ($deploy['status'] === 'error') {
                foreach ($deploy['errors'] as $err) {
                    $errors[] = $puq_pm_web_proxy->name.': '.$err;
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

    public
    function getScriptVariables(
        string $type
    ): array {
        $system = getSystemMacros();

        return array_merge($system, $this->scripts[$type]['variables']);
    }

    public
    function loadDefaultScript(
        string $type
    ): array {
        if (!isset($this->scripts[$type])) {
            return [
                'status' => 'error',
                'errors' => ["Default script '$type' not found"],
            ];
        }

        $script = $this->scripts[$type]['default'];

        $scriptModel = $this->puqPmScripts()->firstOrNew([
            'type' => $type,
            'model' => self::class,
        ]);

        $scriptModel->script = $script;
        $scriptModel->save();

        return ['status' => 'success'];
    }

    public
    function loadAllDefaultScripts(): array
    {
        foreach ($this->scripts as $type => $script) {
            $this->loadDefaultScript($type);
        }

        return ['status' => 'success'];
    }

    protected
    function findSslCertificate(): ?SslCertificate
    {
        $domain = $this->getDnsRecord();
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

        $certificates = SslCertificate::query()
            ->where('domain', $domain)
            ->where('status', 'active')
            ->orderByDesc('expires_at')
            ->get();

        foreach ($certificates as $ssl_certificate) {
            if (!$ssl_certificate->isExpired()) {
                return $ssl_certificate;
            }
        }

        return null;
    }

    public
    function deployResourceConfig(
        array $data
    ): array {
        $config = $this->buildResourceConfig($data);
        $domain = $data['domain'];
        $config = $this->macroReplace($config);

        $ssl_certificate = base64_encode($data['ssl_certificate'] ?? '');
        $ssl_certificate_key = base64_encode($data['ssl_certificate_key'] ?? '');

        $data = [
            'filename' => $domain.'.conf',
            'domain' => $domain,
            'config' => base64_encode($config),
            'ssl_certificate' => $ssl_certificate ?? '',
            'ssl_certificate_key' => $ssl_certificate_key ?? '',
            'reload' => $data['reload'] ?? false,
        ];

        $errors = [];
        foreach ($this->puqPmWebProxies as $puq_pm_web_proxy) {
            $deploy = $puq_pm_web_proxy->deployServiceConfig($data);
            if ($deploy['status'] === 'error') {
                foreach ($deploy['errors'] as $err) {
                    $errors[] = $puq_pm_web_proxy->name.': '.$err;
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

    public
    function removeResourceConfig(
        array $data
    ): array {
        $domain = $data['domain'];
        $data = [
            'filename' => $domain.'.conf',
            'domain' => $domain,
            'reload' => $data['reload'] ?? false,
        ];

        $errors = [];
        foreach ($this->puqPmWebProxies as $puq_pm_web_proxy) {
            $remove = $puq_pm_web_proxy->removeServiceConfig($data);
            if ($remove['status'] === 'error') {
                foreach ($remove['errors'] as $err) {
                    $errors[] = $puq_pm_web_proxy->name.': '.$err;
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

    public
    function removeResourceConfigs(
        array $data
    ): array {
        $data = [
            'reload' => $data['reload'] ?? false,
        ];

        $errors = [];
        foreach ($this->puqPmWebProxies as $puq_pm_web_proxy) {
            $remove = $puq_pm_web_proxy->removeServiceConfigs($data);
            if ($remove['status'] === 'error') {
                foreach ($remove['errors'] as $err) {
                    $errors[] = $puq_pm_web_proxy->name.': '.$err;
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

    public
    function buildResourceConfig(
        array $data
    ): string {
        $domain = $data['domain'] ?? 'example.com';
        $locations = $data['locations'] ?? [];

        $config = "# PUQcloud app config\n";
        $config .= "# v1.0\n\n";

        if (!empty($data['server_custom_config_before'])) {
            $lines = explode("\n", $data['server_custom_config_before']);
            foreach ($lines as $line) {
                $config .= "    {$line}\n";
            }
        }

        // HTTP → HTTPS redirect
        $config .= "server {\n";
        $config .= "    listen 80;\n";
        $config .= "    server_name {$domain};\n";
        $config .= "    return 301 https://\$host\$request_uri;\n";
        $config .= "}\n\n";

        // HTTPS server
        $config .= "server {\n";
        $config .= "    listen 443 ssl http2;\n";
        $config .= "    server_name {$domain};\n\n";

        // SSL paths
        $config .= "    ssl_certificate /etc/nginx/ssl/{$domain}/fullchain.pem;\n";
        $config .= "    ssl_certificate_key /etc/nginx/ssl/{$domain}/privkey.pem;\n\n";

        // SSL
        $config .= "    ssl_protocols TLSv1.2 TLSv1.3;\n";
        $config .= "    ssl_ciphers HIGH:!aNULL:!MD5;\n\n";

        if (!empty($data['server_custom_config'])) {
            $lines = explode("\n", $data['server_custom_config']);
            foreach ($lines as $line) {
                $config .= "    {$line}\n";
            }
        }

        foreach ($locations as $loc) {
            $path = $loc['path'] ?? '/';
            $proxyPass = $loc['proxy_pass'] ?? '';
            $customConfig = $loc['custom_config'] ?? '';

            $config .= "\n    location {$path} {\n";
            if (!empty($proxyPass)) {
                $config .= "        proxy_pass {$proxyPass};\n";
            }
            if (!empty($customConfig)) {

                $lines = explode("\n", $customConfig);
                foreach ($lines as $line) {
                    $config .= "        {$line}\n";
                }
            }
            $config .= "    }\n";
        }

        $config .= "}\n\n";

        if (!empty($data['server_custom_config_after'])) {
            $lines = explode("\n", $data['server_custom_config_after']);
            foreach ($lines as $line) {
                $config .= "    {$line}\n";
            }
        }

        return $config;
    }

    public
    function macroReplace(
        string $pattern
    ): string {
        $puq_pm_cluster_group = $this->puqPmClusterGroup;
        $country = $puq_pm_cluster_group->getCountry();

        $pattern = macroReplace($pattern, $country);

        $replacements = [
            '{LOAD_BALANCER_DOMAIN}' => $this->getDnsRecord() ?? '',
        ];

        $result = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        return $result;
    }

}
