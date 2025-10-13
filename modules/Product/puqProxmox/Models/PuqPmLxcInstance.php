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

use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PuqPmLxcInstance extends Model
{
    protected $table = 'puq_pm_lxc_instances';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'hostname',
        'vmid',
        'creating_upid',
        'puq_pm_lxc_preset_uuid',
        'puq_pm_dns_zone_uuid',

        'service_uuid',
        'puq_pm_cluster_uuid',
        'puq_pm_node_uuid',

        'rootfs_puq_pm_storage_uuid',
        'mp_puq_pm_storage_uuid',
        'backup_puq_pm_storage_uuid',

        'puq_pm_lxc_os_template_uuid',

        'cores',
        'memory',
        'rootfs_size',
        'mp_size',
        'backup_count',

        'status',

        'backup_upid',
        'last_backup_at',

        'firewall_policy_in',
        'firewall_policy_out',

    ];

    protected $casts = [
        'status' => 'array',
        'backup_schedule' => 'array',
        'firewall_rules' => 'array',
    ];

    protected $guarded = ['status'];

    protected $attributes = [
        'status' => null,
    ];

    public function saveStatus(array $status): void // for getSyncClusterLxc
    {
        $this->status = $status;
        $this->save(['timestamps' => false]);
    }

    public function getStatusAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
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

    public function puqPmCluster(): BelongsTo
    {
        return $this->belongsTo(PuqPmCluster::class, 'puq_pm_cluster_uuid', 'uuid');
    }

    public function puqPmNode(): BelongsTo
    {
        return $this->belongsTo(PuqPmNode::class, 'puq_pm_node_uuid', 'uuid');
    }

    public function puqPmStorageRootfs(): BelongsTo
    {
        return $this->belongsTo(PuqPmStorage::class, 'rootfs_puq_pm_storage_uuid', 'uuid');
    }

    public function puqPmStorageMp(): BelongsTo
    {
        return $this->belongsTo(PuqPmStorage::class, 'mp_puq_pm_storage_uuid', 'uuid');
    }

    public function puqPmStorageBackup(): BelongsTo
    {
        return $this->belongsTo(PuqPmStorage::class, 'backup_puq_pm_storage_uuid', 'uuid');
    }

    public function puqPmLxcInstanceNets(): HasMany
    {
        return $this->hasMany(puqPmLxcInstanceNet::class, 'puq_pm_lxc_instance_uuid', 'uuid');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_uuid', 'uuid');
    }

    public function buildLxcConfig(): array
    {
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;

        $data = [
            'node' => $this->puqPmNode->name,
            'arch' => $puq_pm_lxc_preset->arch,
            'cores' => $this->cores,
            'cpulimit' => $puq_pm_lxc_preset->cpulimit,
            'cpuunits' => $puq_pm_lxc_preset->cpuunits,
            'hostname' => $this->hostname,
            'memory' => $this->memory,
            'swap' => $puq_pm_lxc_preset->swap,
            'onboot' => $puq_pm_lxc_preset->onboot,
            'start' => 1,
            'searchdomain' => $puq_pm_lxc_preset->puqPmDnsZone->name,
        ];

        $puq_pm_lxc_instance_nets = $this->puqPmLxcInstanceNets;

        $rootfs_size_gb = round($this->rootfs_size / 1024, 0, PHP_ROUND_HALF_UP);
        $mp_size_gb = round($this->mp_size / 1024, 0, PHP_ROUND_HALF_UP);

        $data['rootfs'] = "{$this->puqPmStorageRootfs->name}:{$rootfs_size_gb}";
        if (!empty($puq_pm_lxc_preset->rootfs_mountoptions)) {
            $data['rootfs'] .= ",mountoptions={$puq_pm_lxc_preset->rootfs_mountoptions}";
        }

        if (!empty($mp_size_gb) and $mp_size_gb > 0) {
            $data['mp0'] = "{$this->puqPmStorageMp->name}:{$mp_size_gb},mp={$puq_pm_lxc_preset->mp}";
            if (!empty($puq_pm_lxc_preset->mp_mountoptions)) {
                $data['mp0'] .= ",mountoptions={$puq_pm_lxc_preset->mp_mountoptions}";
            }

            if (!empty($puq_pm_lxc_preset->mp_backup)) {
                $data['mp0'] .= ',backup=1';
            }
        }

        $puq_pm_cluster = $this->puqPmCluster;
        $net = $this->buildLxcNetworks($puq_pm_lxc_instance_nets, $puq_pm_cluster, $puq_pm_lxc_preset);

        return array_merge($data, $net);
    }

    public function createLxc(): array
    {
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;

        $service = $this->service;
        $service_data = $service->provision_data;
        $service_data['root_password'] = $this->randomString(16);
        $service_data['username'] = $this->randomString(8, 'abcdefghijklmnopqrstuvwxyz');
        $service_data['password'] = $this->randomString(16);
        $service_data['show_password_once'] = true;

        $service->setProvisionData($service_data);

        $ssh_public_key = PuqPmSshPublicKey::query()
            ->where('client_uuid', $service->client_uuid)
            ->where('uuid',
                $service_data['puq_pm_ssh_public_key_uuid'] ?? '')->first();

        if (!$ssh_public_key) {
            $ssh_public_key = PuqPmSshPublicKey::query()->where('client_uuid', $service->client_uuid)->first();
        }

        $puq_pm_lxc_os_template = $puq_pm_lxc_preset->puqPmLxcOsTemplates()->where('uuid',
            $this->puq_pm_lxc_os_template_uuid)->first();
        if (!$puq_pm_lxc_os_template) {
            return [
                'status' => 'error',
                'errors' => ['OS template not found.'],
            ];
        }
        $puq_pm_lxc_template = $puq_pm_lxc_os_template->puqPmLxcTemplate;

        $puq_pm_cluster = $this->puqPmCluster;

        $os_template_storage = $this->puqPmNode->puqPmStorages()->where('content', 'like', '%vztmpl%')->first();
        if (!$os_template_storage) {
            return [
                'status' => 'error',
                'errors' => ['OS template storage not found'],
            ];
        }

        $data = $this->buildLxcConfig();

        $data['ostemplate'] = "{$os_template_storage->name}:vztmpl/".$this->addExtensionIfMissing($puq_pm_lxc_template->name);

        $data['password'] = $service_data['root_password'];

        if ($ssh_public_key) {
            $data['ssh-public-keys'] = $ssh_public_key->public_key;
        }

        $create = $puq_pm_cluster->createLxc($data);

        if ($create['status'] == 'error') {
            return $create;
        }

        $this->creating_upid = $create['data']['upid'];
        $this->vmid = $create['data']['vmid'];
        $this->save();

        $this->getStatus();

        return ['status' => 'success', 'data' => $data];
    }

    public function buildLxcNetworks($puq_pm_lxc_instance_nets, $puq_pm_cluster, $puq_pm_lxc_preset): array
    {
        $data = [
            'nameserver' => '',
        ];
        $netIndex = 0;

        $ipv4Net = null;
        $ipv6Net = null;
        $dhcpNet = null;

        $local_private = null;
        $global_private = null;

        foreach ($puq_pm_lxc_instance_nets as $net) {
            if ($net->type === 'public') {
                if ($net->puq_pm_ipv4_pool_uuid && !$ipv4Net) {
                    $ipv4Net = $net;
                }
                if ($net->puq_pm_ipv6_pool_uuid && !$ipv6Net) {
                    $ipv6Net = $net;
                }
                if (!$net->puq_pm_ipv4_pool_uuid && !$net->puq_pm_ipv6_pool_uuid && !$dhcpNet) {
                    $dhcpNet = $net;
                }
            }
            if ($net->type === 'local_private') {
                $local_private = $net;
            }
            if ($net->type === 'global_private') {
                $global_private = $net;
            }
        }

        if ($ipv4Net && $ipv6Net && $ipv4Net->uuid === $ipv6Net->uuid) {
            $data["net{$netIndex}"] = $this->buildPublicNetConfig($ipv4Net, true, true, $puq_pm_cluster,
                $puq_pm_lxc_preset,
                $data['nameserver']);
        } elseif ($ipv4Net && $ipv6Net) {
            $data["net{$netIndex}"] = $this->buildPublicNetConfig($ipv4Net, true, false, $puq_pm_cluster,
                $puq_pm_lxc_preset,
                $data['nameserver']);
            $netIndex++;
            $data["net{$netIndex}"] = $this->buildPublicNetConfig($ipv6Net, false, true, $puq_pm_cluster,
                $puq_pm_lxc_preset,
                $data['nameserver']);
        } elseif ($ipv4Net) {
            $data["net{$netIndex}"] = $this->buildPublicNetConfig($ipv4Net, true, false, $puq_pm_cluster,
                $puq_pm_lxc_preset,
                $data['nameserver']);
        } elseif ($ipv6Net) {
            $data["net{$netIndex}"] = $this->buildPublicNetConfig($ipv6Net, false, true, $puq_pm_cluster,
                $puq_pm_lxc_preset,
                $data['nameserver']);
        } elseif ($dhcpNet) {
            $data["net{$netIndex}"] = $this->buildPublicNetConfig($dhcpNet, false, false, $puq_pm_cluster,
                $puq_pm_lxc_preset,
                $data['nameserver'], true);
        }

        if ($local_private) {
            $netIndex++;
            $data["net{$netIndex}"] = $this->buildPrivateNetConfig($local_private, $puq_pm_cluster, $puq_pm_lxc_preset);
        }

        if ($global_private) {
            $netIndex++;
            $data["net{$netIndex}"] = $this->buildPrivateNetConfig($global_private, $puq_pm_cluster,
                $puq_pm_lxc_preset);
        }

        return $data;
    }

    public function buildPublicNetConfig(
        $net,
        bool $withIPv4,
        bool $withIPv6,
        $puq_pm_cluster,
        $puq_pm_lxc_preset,
        &$nameserver,
        bool $dhcp = false
    ): string {
        $config = "name={$net->name},hwaddr={$net->mac}";

        $puq_pm_public_network = $puq_pm_cluster->puqPmPublicNetworks()
            ->where('puq_pm_mac_pool_uuid', $net->puq_pm_mac_pool_uuid)
            ->first();

        if ($puq_pm_public_network) {
            $config .= ",bridge={$puq_pm_public_network->bridge}";
            if (!empty($puq_pm_public_network->vlan_tag) && $puq_pm_public_network->vlan_tag != 0) {
                $config .= ",tag={$puq_pm_public_network->vlan_tag}";
            }
        }

        $config .= ",firewall={$puq_pm_lxc_preset->pn_firewall}";

        if (!empty($puq_pm_lxc_preset->pn_mtu) && $puq_pm_lxc_preset->pn_mtu != 0) {
            $config .= ",mtu={$puq_pm_lxc_preset->pn_mtu}";
        }
        if (!empty($puq_pm_lxc_preset->pn_rate) && $puq_pm_lxc_preset->pn_rate != 0) {
            $config .= ",rate={$puq_pm_lxc_preset->pn_rate}";
        }

        if ($dhcp) {
            $config .= ',ip=dhcp,ip6=dhcp';

            return $config;
        }

        if ($withIPv4) {
            $ipv4_ip_pool = $net->puqPmIpV4Pool;
            $nameserver .= $ipv4_ip_pool->dns;
            $config .= ",ip={$net->ipv4}/{$ipv4_ip_pool->mask},gw={$ipv4_ip_pool->gateway}";
        }

        if ($withIPv6) {
            $ipv6_ip_pool = $net->puqPmIpV6Pool;
            $nameserver .= $ipv6_ip_pool->dns ? ','.$ipv6_ip_pool->dns : '';
            $config .= ",ip6={$net->ipv6}/{$ipv6_ip_pool->mask},gw6={$ipv6_ip_pool->gateway}";
        }

        return $config;
    }

    public function buildPrivateNetConfig(
        $net,
        $puq_pm_cluster,
        $puq_pm_lxc_preset
    ): string {

        $service = $this->service;
        $client = $service->client;
        $config = "name={$net->name},hwaddr={$net->mac}";

        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;

        $query = PuqPmClientPrivateNetwork::query()
            ->where('client_uuid', $client->uuid)
            ->where('type', $net->type);

        if ($net->type == 'local_public') {
            $query->where('puq_pm_cluster_group_uuid', $puq_pm_cluster_group);
        }

        $puq_pm_client_private_network = $query->first();

        if ($puq_pm_client_private_network) {
            $config .= ",bridge={$puq_pm_client_private_network->bridge}";
            if (!empty($puq_pm_client_private_network->vlan_tag) && $puq_pm_client_private_network->vlan_tag != 0) {
                $config .= ",tag={$puq_pm_client_private_network->vlan_tag}";
            }
        }

        if ($net->type == 'local_public') {
            $config .= ",firewall={$puq_pm_lxc_preset->lpn_firewall}";

            if (!empty($puq_pm_lxc_preset->lpn_mtu) && $puq_pm_lxc_preset->lpn_mtu != 0) {
                $config .= ",mtu={$puq_pm_lxc_preset->lpn_mtu}";
            }

            if (!empty($puq_pm_lxc_preset->lpn_rate) && $puq_pm_lxc_preset->lpn_rate != 0) {
                $config .= ",rate={$puq_pm_lxc_preset->lpn_rate}";
            }

        } else {
            $config .= ",firewall={$puq_pm_lxc_preset->gpn_firewall}";

            if (!empty($puq_pm_lxc_preset->gpn_mtu) && $puq_pm_lxc_preset->gpn_mtu != 0) {
                $config .= ",mtu={$puq_pm_lxc_preset->gpn_mtu}";
            }

            if (!empty($puq_pm_lxc_preset->gpn_rate) && $puq_pm_lxc_preset->gpn_rate != 0) {
                $config .= ",rate={$puq_pm_lxc_preset->gpn_rate}";
            }
        }

        $config .= ",ip={$net->ipv4}/{$net->mask_v4}";

        return $config;
    }

    private function addExtensionIfMissing(
        string $filename,
        array $allowedExtensions = ['.tar.gz', '.tar.xz', '.tar.zst']
    ): string {
        foreach ($allowedExtensions as $ext) {
            if (str_ends_with($filename, $ext)) {
                return $filename;
            }
        }

        return $filename.$allowedExtensions[0];
    }

    public function getLxcAttributes(): array
    {
        $service = $this->service;
        $product = $service->product;

        $configuration = $product->configuration;

        $groups = [
            'cpu' => 'cpu_product_attribute_group_uuid',
            'ram' => 'memory_product_attribute_group_uuid',
            'rootfs' => 'rootfs_product_attribute_group_uuid',
            'mp' => 'mp_product_attribute_group_uuid',
        ];

        $attributes = [];

        foreach ($groups as $key => $configKey) {
            $attributes[$key] = [];
            $items = $product->productAttributes()
                ->where('product_attribute_group_uuid', $configuration[$configKey] ?? '')
                ->get();

            foreach ($items as $item) {
                $attributes[$key][] = $item->name;
            }
        }

        return $attributes;
    }

    public function getDomain(): string
    {
        return $this->hostname && $this->puqPmDnsZone && $this->puqPmDnsZone->name
            ? $this->hostname.'.'.$this->puqPmDnsZone->name
            : '';
    }

    public function getIPv4(): ?string
    {
        $nets = $this->puqPmLxcInstanceNets()->where('type', 'public')->get();
        foreach ($nets as $net) {
            if (!empty($net->ipv4)) {
                return $net->ipv4;
            }
        }

        return null;
    }

    public function getIPv6(): ?string
    {
        $nets = $this->puqPmLxcInstanceNets()->where('type', 'public')->get();
        foreach ($nets as $net) {
            if (!empty($net->ipv6)) {
                return $net->ipv6;
            }
        }

        return null;
    }

    public function getLocalPrivateIPv4(): ?string
    {
        $nets = $this->puqPmLxcInstanceNets()->where('type', 'local_private')->get();
        foreach ($nets as $net) {
            if (!empty($net->ipv4)) {
                return $net->ipv4;
            }
        }

        return null;
    }

    public function getGlobalPrivateIPv4(): ?string
    {
        $nets = $this->puqPmLxcInstanceNets()->where('type', 'global_private')->get();
        foreach ($nets as $net) {
            if (!empty($net->ipv4)) {
                return $net->ipv4;
            }
        }

        return null;
    }

    public function deleteLxc(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;

        if (empty($this->vmid)) {
            return [
                'status' => 'error',
                'errors' => ['LXC has not been created yet, VMID is empty'],
            ];
        }

        $status = $this->getStatus();
        if (empty($status['node'])) {
            return [
                'status' => 'error',
                'errors' => ['LXC has not been created yet, Node not found'],
            ];
        }

        return $puq_pm_cluster->deleteLxc($this->vmid);
    }

    public function postInstallLxc(): array
    {
        if (empty($this->vmid)) {
            return [
                'status' => 'error',
                'errors' => ['LXC has not been created yet, VMID is empty'],
            ];
        }

        $service = $this->service;
        $service_data = $service->provision_data;

        $puq_pm_lxc_os_template = $this->puqPmLxcOsTemplate;
        $post_install_script = $puq_pm_lxc_os_template->puqPmScripts()->where('type', 'post_install')->first();

        if (empty($post_install_script) || empty($post_install_script->script)) {
            return ['status' => 'success'];
        }

        $script = str_replace(
            ['{USER_NAME}', '{USER_PASSWORD}'],
            [$service_data['username'], $service_data['password']],
            $post_install_script->script
        );

        $post_install = $this->runSshScriptOnLxc($script);

        if ($post_install['status'] !== 'success') {
            return $post_install;
        }

        if (trim($post_install['data']) !== 'success') {
            return [
                'status' => 'error',
                'errors' => ['Post install script failed'],
                'data' => $post_install['data'],
            ];
        }

        return ['status' => 'success'];
    }

    public function runSshScriptOnLxc($script): array
    {
        $puq_pm_cluster = $this->puqPmCluster;

        if (empty($this->vmid)) {
            return [
                'status' => 'error',
                'errors' => ['LXC has not been created yet, VMID is empty'],
            ];
        }

        return $puq_pm_cluster->runSshScriptOnLxc($this->vmid, $script);
    }

    // Generate a random string
    private function randomString(
        $length = 12,
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#$%^*()_-+='
    ): string {
        $str = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, $max)];
        }

        return $str;
    }

    public function getAvailableBackupStorages(): array
    {
        $storages = [];
        $status = $this->getStatus();
        if (empty($status)) {
            return [];
        }

        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_node = $puq_pm_cluster->puqPmNodes()->where('name', $status['node'] ?? '')->first();
        if (!$puq_pm_node) {
            return [];
        }
        $this->puq_pm_node_uuid = $puq_pm_node->uuid;
        $puq_pm_lxc_preset_cluster_group = $puq_pm_lxc_preset->puqPmLxcPresetClusterGroups()
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster->puqPmClusterGroup->uuid)->first();
        $backup_storages = $puq_pm_lxc_preset_cluster_group->getBackupStorages();

        foreach ($backup_storages as $backup_storage) {
            if ($backup_storage->puq_pm_node_uuid == $this->puq_pm_node_uuid) {
                $storages[] = $backup_storage;
            }
        }

        $this->save();

        return $storages;
    }

    public function setBackupStorageName(?PuqPmStorage $backup_puq_pm_storage = null): void
    {

        $service = $this->service;
        $service_data = $service->provision_data;

        $change = false;

        if (!empty($backup_puq_pm_storage)) {
            $change = true;
        }

        if (empty($service_data['backup_storage_name'])) {
            $change = true;
        }

        if (!$change) {
            return;
        }

        if (!$backup_puq_pm_storage) {
            $backup_puq_pm_storage = $this->puqPmStorageBackup;

            if (!$backup_puq_pm_storage) {
                $status = $this->getStatus();
                if (empty($status)) {
                    return;
                }

                $backup_storages = $this->getAvailableBackupStorages();
                foreach ($backup_storages as $backup_storage) {
                    $backup_puq_pm_storage = $backup_storage;
                    $this->backup_puq_pm_storage_uuid = $backup_puq_pm_storage->uuid;
                    $this->save();
                    break;
                }
            }
        }

        if ($backup_puq_pm_storage) {
            $service_data['backup_storage_name'] = $backup_puq_pm_storage->name;
            $service->setProvisionData($service_data);
        }

    }

    public function getInfo(): array
    {
        $service = $this->service;
        $product = $service->product;

        $service_data = $service->provision_data;

        $os_product_option_group_uuid = data_get($product,
            'module.module.product_data.os_product_option_group_uuid');
        $os_product_option = $service->productOptions()->where('product_option_group_uuid',
            $os_product_option_group_uuid)->first();

        $os_product_icon_url = data_get($os_product_option, 'images.icon');
        $os_product_name = $os_product_option->name;

        $mp_size = $this->mp_size > 0 ? (string) $this->mp_size / 1024 .' GB' : null;

        $data = [
            'os' => [
                'icon_url' => $os_product_icon_url,
                'name' => $os_product_name,
            ],
            'cores' => (string) $this->cores,
            'ram' => (string) $this->memory / 1024 .' GB',
            'main_disk' => (string) $this->rootfs_size / 1024 .' GB',
            'addition_disk' => (string) $mp_size ?? null,
            'backups' => $this->backup_count > 0 ? (string) $this->backup_count : null,
            'domain' => $this->getDomain(),
            'ipv4' => $this->getIPv4(),
            'ipv6' => $this->getIPv6(),
            'local_private_network_ipv4' => $this->getLocalPrivateIPv4(),
            'global_private_network_ipv4' => $this->getGlobalPrivateIPv4(),
            'show_password_once' => $service_data['show_password_once'] ?? null,
        ];

        return $data ?? [];
    }

    public function getLocation(): array
    {
        $service = $this->service;
        $product = $service->product;
        $location_product_option_group_uuid = data_get($product,
            'module.module.product_data.location_product_option_group_uuid');
        $location_product_option = $service->productOptions()->where('product_option_group_uuid',
            $location_product_option_group_uuid)->first();

        $location_product_icon_url = data_get($location_product_option, 'images.icon');
        $location_product_background_url = data_get($location_product_option, 'images.background');

        $data = [
            'name' => $location_product_option->name,
            'data_center' => $location_product_option->value,
            'description' => $location_product_option->description,
            'short_description' => $location_product_option->short_description,
            'icon_url' => $location_product_icon_url,
            'background_url' => $location_product_background_url,
        ];

        return $data ?? [];
    }

    public function getStatus(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster->getSyncResources();

        $status = $this->newQuery()->where('uuid', $this->uuid)->value('status');

        $cpuUsage = round($status['cpu'] ?? 0, 2, PHP_ROUND_HALF_UP);
        $cpuUsage = max(0, min(100, $cpuUsage));

        $memUsed = $status['mem'] ?? 0;
        $memMax = $status['maxmem'] ?? 0;
        $memUsage = $memMax > 0 ? round(($memUsed / $memMax) * 100, 2, PHP_ROUND_HALF_UP) : 0;
        $memUsage = max(0, min(100, $memUsage));

        $diskUsed = $status['disk'] ?? 0;
        $diskMax = $status['maxdisk'] ?? 0;
        $diskUsage = $diskMax > 0 ? round(($diskUsed / $diskMax) * 100, 2, PHP_ROUND_HALF_UP) : 0;
        $diskUsage = max(0, min(100, $diskUsage));

        $uptime = isset($status['uptime']) ? gmdate('H:i:s', $status['uptime']) : '00:00:00';

        if (!empty($status['lock'])
        ) {
            $status['status'] = $status['lock'];
        }

        $custom = [
            'status' => $status['status'] ?? 'unknown',
            'status_btn' => ($status['status'] ?? '') === 'running' ? 'success' : 'danger',

            'cpu' => $cpuUsage,

            'memory' => $memUsage,
            'memory_used' => $memUsed,
            'memory_max' => $memMax,

            'disk' => $diskUsage,
            'disk_used' => $diskUsed,
            'disk_max' => $diskMax,

            'uptime' => $uptime,
        ];

        return array_merge($status, $custom);

    }

    public function getRrdData(string $timeframe): array
    {
        $service = $this->service;

        if (empty($this->vmid)) {
            return ['status' => 'success', 'data' => []];
        }
        $puq_pm_node = $this->puqPmNode;
        $puq_pm_cluster = $this->puqPmCluster;
        $rrd_data = $puq_pm_cluster->getXlcRrdData($puq_pm_node->name, $this->vmid, $timeframe);

        if ($rrd_data['status'] == 'error') {
            return ['status' => 'success', 'data' => []];
        }

        $orderDate = strtotime($service->order_date);

        $data = [];
        foreach ($rrd_data['data'] as $item) {
            if ($item['time'] < $orderDate) {
                $data[] = ['time' => $item['time']];
            } else {
                $data[] = $item;
            }
        }
        $rrd_data['data'] = $data;

        return $rrd_data;
    }

    public function start(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster->getSyncResources($puq_pm_cluster->getClusterResources(true));

        $status = $this->getStatus() ?? [];

        if (isset($status['status']) && $status['status'] === 'running') {
            return ['status' => 'error', 'errors' => ['LXC is already running']];
        }

        $start = $puq_pm_cluster->startLxc($status['node'], $status['vmid']);

        if ($start['status'] === 'success') {
            $maxTries = 30;
            $tries = 0;

            do {
                sleep(1);
                $puq_pm_cluster->getClusterResources(true);
                $status = $this->getStatus() ?? [];
                $tries++;

            } while ($status['status'] !== 'running' && $tries < $maxTries);

            if ($status['status'] === 'running') {
                return ['status' => 'success'];
            } else {
                return ['status' => 'error', 'errors' => ['Timeout while starting LXC']];
            }
        }

        return $start;
    }

    public function stop(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster->getSyncResources($puq_pm_cluster->getClusterResources(true));

        $status = $this->getStatus() ?? [];

        if (isset($status['status']) && $status['status'] === 'stopped') {
            return ['status' => 'error', 'errors' => ['LXC is already stopped']];
        }

        $stop = $puq_pm_cluster->stopLxc($status['node'], $status['vmid']);

        if ($stop['status'] === 'success') {
            $maxTries = 30;
            $tries = 0;

            do {
                sleep(1);
                $puq_pm_cluster->getClusterResources(true);
                $status = $this->getStatus() ?? [];
                $tries++;

            } while ($status['status'] !== 'stopped' && $tries < $maxTries);

            if ($status['status'] === 'stopped') {
                return ['status' => 'success'];
            } else {
                return ['status' => 'error', 'errors' => ['Timeout while stopping LXC']];
            }
        }

        return $stop;
    }

    public function console(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $cluster_resources = $puq_pm_cluster->getClusterResources(true);
        $puq_pm_cluster->getSyncResources($cluster_resources);
        $status = $this->getStatus() ?? [];
        $console = $puq_pm_cluster->consoleLxc($status['node'], $status['vmid']);

        return $console;
    }

    public function resetPasswords(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $cluster_resources = $puq_pm_cluster->getClusterResources(true);
        $puq_pm_cluster->getSyncResources($cluster_resources);

        $status = $this->getStatus() ?? [];
        if (empty($status['status']) or $status['status'] != 'running') {
            return ['status' => 'error', 'errors' => ['Status mart be running']];
        }

        $service = $this->service;
        $service_data = $service->provision_data;

        $service_data['root_password'] = $this->randomString(16);
        $service_data['password'] = $this->randomString(16);
        $service_data['show_password_once'] = true;

        $puq_pm_lxc_os_template = $this->puqPmLxcOsTemplate;
        $reset_password_script = $puq_pm_lxc_os_template->puqPmScripts()->where('type', 'reset_password')->first();

        if (empty($reset_password_script) || empty($reset_password_script->script)) {
            return ['status' => 'success'];
        }

        $script = str_replace(
            ['{USER_NAME}', '{USER_NEW_PASSWORD}', '{ROOT_NEW_PASSWORD}'],
            [$service_data['username'], $service_data['password'], $service_data['root_password']],
            $reset_password_script->script
        );

        $reset_passwords = $this->runSshScriptOnLxc($script);

        if ($reset_passwords['status'] !== 'success') {
            return $reset_passwords;
        }

        $service->provision_data = $service_data;
        $service->setProvisionData($service_data);

        return $reset_passwords;
    }

    public function getConfig(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $service = $this->service;
        $service_data = $service->provision_data;
        $status = $this->getStatus();

        if (empty($status['node']) or empty($this->vmid)) {
            return [
                'status' => 'error',
                'errors' => ['LXC has not been created yet or Node not found'],
            ];
        }

        $config = $puq_pm_cluster->getLxcConfig($status['node'], $this->vmid);

        return $config;
    }

    public function getBackupSchedule(): array
    {
        $schedule = $this->backup_schedule;

        return [
            'monday' => [
                'enable' => $schedule['monday']['enable'] ?? false,
                'time' => $schedule['monday']['time'] ?? '00:00',
            ],
            'tuesday' => [
                'enable' => $schedule['tuesday']['enable'] ?? false,
                'time' => $schedule['tuesday']['time'] ?? '00:00',
            ],
            'wednesday' => [
                'enable' => $schedule['wednesday']['enable'] ?? false,
                'time' => $schedule['wednesday']['time'] ?? '00:00',
            ],
            'thursday' => [
                'enable' => $schedule['thursday']['enable'] ?? false,
                'time' => $schedule['thursday']['time'] ?? '00:00',
            ],
            'friday' => [
                'enable' => $schedule['friday']['enable'] ?? false,
                'time' => $schedule['friday']['time'] ?? '00:00',
            ],
            'saturday' => [
                'enable' => $schedule['saturday']['enable'] ?? false,
                'time' => $schedule['saturday']['time'] ?? '00:00',
            ],
            'sunday' => [
                'enable' => $schedule['sunday']['enable'] ?? false,
                'time' => $schedule['sunday']['time'] ?? '00:00',
            ],
        ];
    }

    public function getBackups(): array
    {
        $this->setBackupStorageName();
        $puq_pm_cluster = $this->puqPmCluster;

        $service = $this->service;
        $service_data = $service->provision_data;
        $status = $this->getStatus();

        if (empty($status['node']) or empty($service_data['backup_storage_name']) or empty($this->vmid)) {
            return [
                'status' => 'error',
                'errors' => ['LXC has not been created yet, Node, Backup Storage not found'],
            ];
        }
        $backups = $puq_pm_cluster->getBackups($status['node'], $this->vmid,
            $service_data['backup_storage_name'] ?? '');

        return $backups;
    }

    public function backupNow(string $note): array
    {
        $this->setBackupStorageName();
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();
        $service = $this->service;
        $service_data = $service->provision_data;

        $backup_now = $puq_pm_cluster->backupNow(
            $note,
            $status['node'],
            $this->vmid,
            $service_data['backup_storage_name'],
            $puq_pm_lxc_preset->vzdump_mode,
            $puq_pm_lxc_preset->vzdump_compress,
            $puq_pm_lxc_preset->vzdump_bwlimit
        );

        $maxTries = 10;
        $tries = 0;

        do {
            sleep(1);
            $puq_pm_cluster->getClusterResources(true);
            $status = $this->getStatus() ?? [];
            $tries++;
        } while (($status['status'] ?? '') !== 'backup' && $tries < $maxTries);

        if (($status['status'] ?? '') === 'backup') {
            return ['status' => 'success', 'backup' => $backup_now];
        } else {
            return ['status' => 'error', 'errors' => ['Timeout waiting for backup status']];
        }
    }

    public function makeScheduleBackup(): array
    {
        $this->setBackupStorageName();
        $backups_raw = $this->getBackups();
        if ($backups_raw['status'] == 'error') {
            return $backups_raw;
        }

        while ($this->backup_count <= count($backups_raw['data'])) {
            usort($backups_raw['data'], function ($a, $b) {
                return $a['ctime'] <=> $b['ctime'];
            });

            $oldest = array_shift($backups_raw['data']);
            $puq_pm_cluster = $this->puqPmCluster;
            $status = $this->getStatus();
            $service = $this->service;
            $service_data = $service->provision_data;

            $delete = $puq_pm_cluster->deleteFile(
                $status['node'],
                $service_data['backup_storage_name'],
                $oldest['volid']
            );

            if ($delete['status'] == 'error') {
                return $delete;
            }
        }

        return $this->backupNow('Scheduler: '.now());
    }

    public function deleteFile(int $date, int $size): array
    {
        $backups_raw = $this->getBackups();
        if ($backups_raw['status'] == 'error') {
            return $backups_raw;
        }

        foreach ($backups_raw['data'] as $backup_raw) {
            if ($backup_raw['ctime'] == $date and $backup_raw['size'] == $size) {
                $puq_pm_cluster = $this->puqPmCluster;
                $status = $this->getStatus();
                $service = $this->service;
                $service_data = $service->provision_data;

                $delete = $puq_pm_cluster->deleteFile($status['node'], $service_data['backup_storage_name'],
                    $backup_raw['volid']);

                return $delete;
            }
        }

        return ['status' => 'error', 'errors' => ['Backup not found']];
    }

    public function deleteAllBackups(): array
    {
        $backups_raw = $this->getBackups();
        if ($backups_raw['status'] == 'error') {
            return $backups_raw;
        }

        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();
        $service = $this->service;
        $service_data = $service->provision_data;

        foreach ($backups_raw['data'] as $backup_raw) {
            if (!empty($status['node']) and !empty($service_data['backup_storage_name']) and !empty($backup_raw['volid'])) {
                $puq_pm_cluster->deleteFile($status['node'], $service_data['backup_storage_name'],
                    $backup_raw['volid']);
            }
        }

        return ['status' => 'success'];
    }

    public function restoreBackup(int $date, int $size): array
    {
        $backups_raw = $this->getBackups();
        if ($backups_raw['status'] == 'error') {
            return $backups_raw;
        }

        foreach ($backups_raw['data'] as $backup_raw) {
            if ($backup_raw['ctime'] == $date and $backup_raw['size'] == $size) {
                $puq_pm_cluster = $this->puqPmCluster;
                $status = $this->getStatus();

                $data = $this->buildLxcConfig();

                $data['node'] = $status['node'];
                $data['ostemplate'] = $backup_raw['volid'];
                $data['vmid'] = $this->vmid;
                $restore = $puq_pm_cluster->restoreLxcBackup($data);

                $puq_pm_cluster->getClusterResources(true);
                $status = $this->getStatus();

                return $restore;
            }
        }

        return ['status' => 'error', 'errors' => ['Backup not found']];
    }

    public function getPublicNetworks(): array
    {
        $ipv4_public_network = [];
        $ipv6_public_network = [];

        $puq_pm_lxc_instance_nets = $this->puqPmLxcInstanceNets()->where('type', 'public')->get();

        foreach ($puq_pm_lxc_instance_nets as $puq_pm_lxc_instance_net) {
            if (!empty($puq_pm_lxc_instance_net->puq_pm_ipv4_pool_uuid)) {
                $ipv4_public_network = [
                    'name' => $puq_pm_lxc_instance_net->name,
                    'mac' => $puq_pm_lxc_instance_net->mac,
                    'ip' => $puq_pm_lxc_instance_net->ipv4 ?? 'dhcp',
                    'rdns' => $puq_pm_lxc_instance_net->rdns_v4,
                ];
            }
            if (!empty($puq_pm_lxc_instance_net->puq_pm_ipv6_pool_uuid)) {
                $ipv6_public_network = [
                    'name' => $puq_pm_lxc_instance_net->name,
                    'mac' => $puq_pm_lxc_instance_net->mac,
                    'ip' => $puq_pm_lxc_instance_net->ipv6 ?? 'dhcp',
                    'rdns' => $puq_pm_lxc_instance_net->rdns_v6,
                ];
            }
        }

        if (!empty($ipv4_public_network) and !empty($ipv6_public_network)) {
            if ($ipv4_public_network['mac'] != $ipv6_public_network['mac']) {
                $ipv6_public_network['name'] = $ipv4_public_network['name'].'v6';
            }
        }

        return [
            'ipv4' => $ipv4_public_network,
            'ipv6' => $ipv6_public_network,
        ];
    }

    public function getPrivateNetworks(): array
    {
        $local_private_network = [];
        $global_private_network = [];

        $puq_pm_lxc_instance_nets = $this->puqPmLxcInstanceNets()->whereIn('type',
            ['local_private', 'global_private'])->get();

        foreach ($puq_pm_lxc_instance_nets as $puq_pm_lxc_instance_net) {

            if ($puq_pm_lxc_instance_net->type == 'local_private') {
                $local_private_network = [
                    'name' => $puq_pm_lxc_instance_net->name,
                    'mac' => $puq_pm_lxc_instance_net->mac,
                    'ip' => $puq_pm_lxc_instance_net->ipv4,
                    'mask' => $puq_pm_lxc_instance_net->mask_v4,
                ];
            }
            if ($puq_pm_lxc_instance_net->type == 'global_private') {
                $global_private_network = [
                    'name' => $puq_pm_lxc_instance_net->name,
                    'mac' => $puq_pm_lxc_instance_net->mac,
                    'ip' => $puq_pm_lxc_instance_net->ipv4,
                    'mask' => $puq_pm_lxc_instance_net->mask_v4,
                ];
            }
        }

        return [
            'local' => $local_private_network,
            'global' => $global_private_network,
        ];
    }

    public function setRdns(?string $ipv4_rDNS = null, ?string $ipv6_rDNS = null): void
    {
        $domain = $this->getDomain();
        $puq_pm_lxc_instance_nets = $this->puqPmLxcInstanceNets()->where('type', 'public')->get();

        foreach ($puq_pm_lxc_instance_nets as $puq_pm_lxc_instance_net) {
            if (!empty($puq_pm_lxc_instance_net->puq_pm_ipv4_pool_uuid)) {
                $puq_pm_lxc_instance_net->rdns_v4 = $ipv4_rDNS ?? $domain;
            }
            if (!empty($puq_pm_lxc_instance_net->puq_pm_ipv6_pool_uuid)) {
                $puq_pm_lxc_instance_net->rdns_v6 = $ipv6_rDNS ?? $domain;
            }
            $puq_pm_lxc_instance_net->save();
        }
    }

    public function getFirewallOptions(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();

        $data = [
            'node' => $status['node'],
            'vmid' => $this->vmid,
        ];

        return $puq_pm_cluster->getLxcFirewallOptions($data);
    }

    public function setFirewallOptions(?string $firewall_policy_in = null, ?string $firewall_policy_out = null): array
    {
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();

        if (!empty($firewall_policy_in)) {
            $this->firewall_policy_in = $firewall_policy_in;
        }

        if (!empty($firewall_policy_out)) {
            $this->firewall_policy_out = $firewall_policy_out;
        }

        if (empty($this->firewall_policy_in)) {
            $this->firewall_policy_in = $puq_pm_lxc_preset->firewall_policy_in;
        }

        if (empty($this->firewall_policy_out)) {
            $this->firewall_policy_out = $puq_pm_lxc_preset->firewall_policy_out;
        }

        $data = [
            'node' => $status['node'],
            'vmid' => $this->vmid,
            'enable' => $puq_pm_lxc_preset->firewall_enable,
            'dhcp' => $puq_pm_lxc_preset->firewall_dhcp,
            'ipfilter' => $puq_pm_lxc_preset->firewall_ipfilter,
            'macfilter' => $puq_pm_lxc_preset->firewall_macfilter,
            'ndp' => $puq_pm_lxc_preset->firewall_ndp,
            'radv' => $puq_pm_lxc_preset->firewall_radv,
            'log_level_in' => $puq_pm_lxc_preset->firewall_log_level_in,
            'log_level_out' => $puq_pm_lxc_preset->firewall_log_level_out,
            'policy_in' => $this->firewall_policy_in,
            'policy_out' => $this->firewall_policy_out,
        ];
        $this->save();

        return $puq_pm_cluster->setLxcFirewallOptions($data);
    }

    public function getFirewallPolicies(): array
    {

        if (empty($this->firewall_policy_in) or empty($this->firewall_policy_out)) {
            $firewall_options = $this->getFirewallOptions();

            if ($firewall_options['status'] == 'error') {
                return $firewall_options;
            }

            $this->firewall_policy_in = $firewall_options['data']['policy_in'];
            $this->firewall_policy_out = $firewall_options['data']['policy_out'];
            $this->save();
        }

        return [
            'policy_in' => $this->firewall_policy_in,
            'policy_out' => $this->firewall_policy_out,
        ];
    }

    public function getFirewallRules(): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();

        $data = [
            'node' => $status['node'],
            'vmid' => $this->vmid,
        ];

        return $puq_pm_cluster->getLxcFirewallRules($data);
    }

    public function setFirewallRuleUpdate($data): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();

        $data['node'] = $status['node'];
        $data['vmid'] = $this->vmid;

        return $puq_pm_cluster->setLxcFirewallRuleUpdate($data);
    }

    public function setFirewallRuleDelete($data): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();

        $data['node'] = $status['node'];
        $data['vmid'] = $this->vmid;

        return $puq_pm_cluster->setLxcFirewallRuleDelete($data);
    }

    public function createFirewallRule($data): array
    {
        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();

        $data['node'] = $status['node'];
        $data['vmid'] = $this->vmid;

        return $puq_pm_cluster->createLxcFirewallRule($data);
    }

    // Rebuild
    public function rebuildNow(): array
    {
        $status = $this->getStatus() ?? [];

        $puq_pm_lxc_preset = $this->puqPmLxcPreset;

        $service = $this->service;
        $service_data = $service->provision_data;
        $service_data['root_password'] = $this->randomString(16);
        $service_data['username'] = $this->randomString(8, 'abcdefghijklmnopqrstuvwxyz');
        $service_data['password'] = $this->randomString(16);
        $service_data['show_password_once'] = true;

        $service->setProvisionData($service_data);

        $ssh_public_key = PuqPmSshPublicKey::query()
            ->where('client_uuid', $service->client_uuid)
            ->where('uuid',
                $service_data['puq_pm_ssh_public_key_uuid'] ?? '')->first();

        if (!$ssh_public_key) {
            $ssh_public_key = PuqPmSshPublicKey::query()->where('client_uuid', $service->client_uuid)->first();
        }

        $puq_pm_lxc_os_template = $puq_pm_lxc_preset->puqPmLxcOsTemplates()->where('uuid',
            $this->puq_pm_lxc_os_template_uuid)->first();
        if (!$puq_pm_lxc_os_template) {
            return [
                'status' => 'error',
                'errors' => ['OS template not found.'],
            ];
        }
        $puq_pm_lxc_template = $puq_pm_lxc_os_template->puqPmLxcTemplate;

        $puq_pm_cluster = $this->puqPmCluster;

        $os_template_storage = $this->puqPmNode->puqPmStorages()->where('content', 'like', '%vztmpl%')->first();
        if (!$os_template_storage) {
            return [
                'status' => 'error',
                'errors' => ['OS template storage not found'],
            ];
        }

        $data = $this->buildLxcConfig();
        $data['vmid'] = $this->vmid;
        $data['node'] = $status['node'];

        $data['ostemplate'] = "{$os_template_storage->name}:vztmpl/".$this->addExtensionIfMissing($puq_pm_lxc_template->name);

        $data['password'] = $service_data['root_password'];

        if ($ssh_public_key) {
            $data['ssh-public-keys'] = $ssh_public_key->public_key;
        }

        $delete = $puq_pm_cluster->deleteLxc($this->vmid);

        if ($delete['status'] == 'error') {
            return $delete;
        }

        $create = $puq_pm_cluster->createLxc($data);

        if ($create['status'] == 'error') {
            return $create;
        }

        return ['status' => 'success', 'data' => $data];

    }

    // Rescale
    public function rescaleNow(): array
    {
        $lcx_preset = $this->puqPmLxcPreset;
        $service = $this->service;
        $product = $service->product;
        $puq_pm_cluster = $this->puqPmCluster;
        $product_data = data_get($product, 'module.module.product_data');
        $product_options = $lcx_preset->getServiceProductOptions($service, $product_data);
        $status = $this->getStatus();

        if ($status['status'] == 'running') {
            $stop = $this->stop();
            if ($stop['status'] == 'error') {
                return $stop;
            }
        }

        // Backups -------------------------------------------------------------------------
        $backup_count = $lcx_preset->backup_count + $product_options['backup_count'];
        if ($this->backup_count != $this->backup_count) {

            $set_backup_storage = $this->setBackupStorage();

            if ($set_backup_storage['status'] == 'error') {
                return $set_backup_storage;
            }

            $this->backup_count = $backup_count;
            $this->save();
        }

        // Set CPU and Memory --------------------------------------------------------------
        $cpu = $lcx_preset->cores + $product_options['cores'];
        $memory = $lcx_preset->memory + $product_options['memory'];

        if ($this->cores != $cpu or $this->memory != $memory) {
            $set_cpu_ram = $this->setCpuMemory($cpu, $memory);

            if ($set_cpu_ram['status'] == 'error') {
                return $set_cpu_ram;
            }

            $this->save();
        }

        // resize rootfs -------------------------------------------------------------------
        if ($this->rootfs_size != $lcx_preset->rootfs_size + $product_options['rootfs_size']) {

            $this->rootfs_size = $lcx_preset->rootfs_size + $product_options['rootfs_size'];

            $resize_rootfs = $puq_pm_cluster->resizeLxcDisk($status['node'], $this->vmid,
                [
                    'disk' => 'rootfs',
                    'size' => (string) $this->rootfs_size.'M',
                ]);
            if ($resize_rootfs['status'] == 'error') {
                return $resize_rootfs;
            }
            $this->save();
        }

        $config = $this->getConfig();
        if ($config['status'] == 'error') {
            return $config;
        }

        $old_mp_size = $this->mp_size;
        $this->mp_size = $lcx_preset->mp_size + $product_options['mp_size'];

        // Delete MP ------------------------------------------------------------------------
        if (!empty($config['data']['mp0']) and $this->mp_size == 0) {
            $delete_mp = $this->deleteMp();
            if ($delete_mp['status'] == 'error') {
                return $delete_mp;
            }
            $this->save();
        }

        // Create MP ------------------------------------------------------------------------
        if (empty($config['data']['mp0']) and $this->mp_size > 0) {
            $create_mp = $this->createMp();
            if ($create_mp['status'] == 'error') {
                return $create_mp;
            }
            $this->save();
        }

        // Resize MP -----------------------------------------------------------------------
        if (!empty($config['data']['mp0']) and ($old_mp_size != $this->mp_size) and ($this->mp_size != 0)) {

            $resize_mp = $puq_pm_cluster->resizeLxcDisk($status['node'], $this->vmid,
                [
                    'disk' => 'mp0',
                    'size' => (string) $this->mp_size.'M',
                ]);
            if ($resize_mp['status'] == 'error') {
                return $resize_mp;
            }
            $this->save();
        }

        // Delete networks
        $delete_networks = $this->deleteNetworks($product_options);
        if ($delete_networks['status'] == 'error') {
            return $delete_networks;
        }
        // Create networks
        $create_networks = $this->createNetworks($product_options);
        if ($create_networks['status'] == 'error') {
            return $create_networks;
        }

        $this->load('puqPmLxcInstanceNets');

        $set_net_config = $this->setNetworkConfiguration($config);
        if ($set_net_config['status'] == 'error') {
            return $set_net_config;
        }

        $start = $this->start();
        if ($start['status'] == 'error') {
            return $start;
        }

        return [
            'status' => 'success',
        ];

    }

    public function setBackupStorage(): array
    {
        $status = $this->getStatus();
        $puq_pm_node = $this->puqPmNode()->where('name', $status['node'])->first();
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;
        $puq_pm_lxc_preset_cluster_group = $puq_pm_lxc_preset
            ->puqPmLxcPresetClusterGroups()
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster->puq_pm_cluster_group_uuid)
            ->first();

        if (empty($this->backup_count) or $this->backup_count == 0) {
            $backup_storages = $puq_pm_lxc_preset_cluster_group->getBackupStorages();
            $backup_storage = $puq_pm_cluster_group->getAvailableStorage($puq_pm_node, $backup_storages, 0);
            if (!$backup_storage) {
                return [
                    'status' => 'error',
                    'errors' => ['Backup storage is unavailable'],
                ];
            }
            $this->backup_puq_pm_storage_uuid = $backup_storage->uuid;
        }

        return ['status' => 'success'];
    }

    public function setCpuMemory($cpu, $memory): array
    {
        $set_cpu_memory = false;
        if ($this->cores != $cpu) {
            $this->cores = $cpu;
            $set_cpu_memory = true;
        }

        if ($this->memory != $memory) {
            $this->memory = $memory;
            $set_cpu_memory = true;
        }

        if ($set_cpu_memory) {

            $puq_pm_cluster = $this->puqPmCluster;
            $status = $this->getStatus();

            $set_config = $puq_pm_cluster->setLxcConfig($status['node'], $this->vmid,
                ['cores' => $cpu, 'memory' => $memory]);

            if ($set_config['status'] == 'error') {
                return $set_config;
            }
        }

        return [
            'status' => 'success',
        ];
    }

    public function deleteMp(): array
    {
        $status = $this->getStatus();

        $puq_pm_cluster = $this->puqPmCluster;

        $delete_config_mp = $puq_pm_cluster->deleteLxcConfig($status['node'], $this->vmid, 'mp0');

        if ($delete_config_mp['status'] == 'error') {
            return $delete_config_mp;
        }

        $delete_config_unused = $puq_pm_cluster->deleteLxcConfig($status['node'], $this->vmid, 'unused0');

        if ($delete_config_unused['status'] == 'error') {
            return $delete_config_unused;
        }

        return [
            'status' => 'success',
        ];
    }

    public function createMp(): array
    {
        $status = $this->getStatus();

        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;
        $puq_pm_node = $this->puqPmNode()->where('name', $status['node'])->first();
        $puq_pm_lxc_preset_cluster_group = $puq_pm_lxc_preset
            ->puqPmLxcPresetClusterGroups()
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster->puq_pm_cluster_group_uuid)
            ->first();

        $mp_storages = $puq_pm_lxc_preset_cluster_group->getAdditionalStorages();
        $mp_storage = $puq_pm_cluster_group->getAvailableStorage($puq_pm_node, $mp_storages, $this->mp_size * 1024);

        if (!$mp_storage) {
            return [
                'status' => 'error',
                'errors' => ['Addition disk storage is unavailable'],
            ];
        }

        $this->mp_puq_pm_storage_uuid = $mp_storage->uuid;

        $mp_size_gb = $this->mp_size / 1024;
        $data['mp0'] = "{$mp_storage->name}:{$mp_size_gb},mp={$puq_pm_lxc_preset->mp}";
        if (!empty($puq_pm_lxc_preset->mp_mountoptions)) {
            $data['mp0'] .= ",mountoptions={$puq_pm_lxc_preset->mp_mountoptions}";
        }

        if (!empty($puq_pm_lxc_preset->mp_backup)) {
            $data['mp0'] .= ',backup=1';
        }

        $set_config = $puq_pm_cluster->setLxcConfig($status['node'], $this->vmid,$data);

        if ($set_config['status'] == 'error') {
            return $set_config;
        }

        return [
            'status' => 'success',
        ];
    }

    public function deleteNetworks(array $product_options): array
    {
        $puq_pm_lxc_instance_nets = $this->puqPmLxcInstanceNets;
        foreach ($puq_pm_lxc_instance_nets as $net) {

            if ($net->type == 'local_private') {
                if ($product_options['local_private_network'] != '1') {
                    $net->delete();
                }
            }

            if ($net->type == 'global_private') {
                if ($product_options['global_private_network'] != '1') {
                    $net->delete();
                }
            }

            if ($net->type == 'public') {

                if ($product_options['ipv4_public_network'] != '1') {
                    $net->puq_pm_ipv4_pool_uuid = null;
                }

                if ($product_options['ipv6_public_network'] != '1') {
                    $net->puq_pm_ipv6_pool_uuid = null;
                }

                if (empty($net->puq_pm_ipv4_pool_uuid) and empty($net->puq_pm_ipv6_pool_uuid)) {
                    $net->delete();
                }

            }
        }

        return [
            'status' => 'success',
        ];

    }

    public function createNetworks(array $product_options): array
    {
        $this->load('puqPmLxcInstanceNets');

        $ipv4_public_network = $this->puqPmLxcInstanceNets()
            ->where('type', 'public')
            ->whereNotNull('puq_pm_ipv4_pool_uuid')
            ->first();
        if ($product_options['ipv4_public_network'] == '1' and !$ipv4_public_network) {
            $create_ipv4_public_network = $this->createIpv4PublicNetwork();
            if ($create_ipv4_public_network['status'] == 'error') {
                return $create_ipv4_public_network;
            }
        }

        $ipv6_public_network = $this->puqPmLxcInstanceNets()
            ->where('type', 'public')
            ->whereNotNull('puq_pm_ipv6_pool_uuid')
            ->first();
        if ($product_options['ipv6_public_network'] == '1' and !$ipv6_public_network) {
            $create_ipv6_public_network = $this->createIpv6PublicNetwork();
            if ($create_ipv6_public_network['status'] == 'error') {
                return $create_ipv6_public_network;
            }
        }

        $local_private_network = $this->puqPmLxcInstanceNets()
            ->where('type', 'local_private')
            ->first();
        if ($product_options['local_private_network'] == '1' and !$local_private_network) {
            $create_local_private_network = $this->createLocalPrivateNetwork();
            if ($create_local_private_network['status'] == 'error') {
                return $create_local_private_network;
            }
        }

        $global_private_network = $this->puqPmLxcInstanceNets()
            ->where('type', 'global_private')
            ->first();
        if ($product_options['global_private_network'] == '1' and !$global_private_network) {
            $create_global_private_network = $this->createGlobalPrivateNetwork();
            if ($create_global_private_network['status'] == 'error') {
                return $create_global_private_network;
            }
        }

        return [
            'status' => 'success',
        ];
    }

    public function createIpv4PublicNetwork(): array
    {
        $status = $this->getStatus();
        $puq_pm_node = $this->puqPmNode()->where('name', $status['node'])->first();
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_dns_zone = $puq_pm_lxc_preset->puqPmDnsZone;
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;
        $puq_pm_lxc_preset_cluster_group = $puq_pm_lxc_preset
            ->puqPmLxcPresetClusterGroups()
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster->puq_pm_cluster_group_uuid)
            ->first();

        $public_networks = $puq_pm_lxc_preset_cluster_group->getPublicNetworks();
        $ipv6_public_network = $this->puqPmLxcInstanceNets()
            ->where('type', 'public')
            ->whereNotNull('puq_pm_ipv6_pool_uuid')
            ->first();

        $ipv4_public_network = $puq_pm_cluster_group->getAvailablePublicNetwork($puq_pm_node, $public_networks, 'ipv4');
        if (!$ipv4_public_network) {
            return [
                'status' => 'error',
                'errors' => ['IPv4 public network is unavailable'],
            ];
        }

        // IPv4 checks

        $macPoolIPv4 = $ipv4_public_network->puqPmMacPool;
        $ipPoolIPv4 = $ipv4_public_network->puqPmIpPool;

        if ($ipPoolIPv4) {
            $ip_ipv4 = $ipPoolIPv4->getIp();
        } else {
            $ip_ipv4 = 'dhcp';
        }

        $mac_ipv4 = $macPoolIPv4->getMac();

        if (!$mac_ipv4) {
            return [
                'status' => 'error',
                'errors' => ["No free MAC in MAC pool '{$macPoolIPv4->name}' for IPv4"],
            ];
        }
        if (!$ip_ipv4) {
            return [
                'status' => 'error',
                'errors' => ["No free IP in IP pool '{$ipPoolIPv4->name}' for IPv4"],
            ];
        }

        if ($ipv6_public_network) {
            $ipv6_public_network->name = $puq_pm_lxc_preset->pn_name.'v6';
        }

        $puq_pm_lxc_instance_net = new PuqPmLxcInstanceNet();
        $puq_pm_lxc_instance_net->name = $puq_pm_lxc_preset->pn_name;
        $puq_pm_lxc_instance_net->puq_pm_lxc_instance_uuid = $this->uuid;
        $puq_pm_lxc_instance_net->type = 'public';
        $puq_pm_lxc_instance_net->puq_pm_mac_pool_uuid = $macPoolIPv4->uuid;
        $puq_pm_lxc_instance_net->mac = $mac_ipv4;
        $puq_pm_lxc_instance_net->puq_pm_ipv4_pool_uuid = $ipPoolIPv4->uuid ?? null;
        $puq_pm_lxc_instance_net->ipv4 = $ip_ipv4 == 'dhcp' ? null : $ip_ipv4;
        $puq_pm_lxc_instance_net->rdns_v4 = $this->hostname.'.'.$puq_pm_dns_zone->name;
        $puq_pm_lxc_instance_net->save();

        return ['status' => 'success'];
    }

    public function createIpv6PublicNetwork(): array
    {
        $status = $this->getStatus();
        $puq_pm_node = $this->puqPmNode()->where('name', $status['node'])->first();
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_dns_zone = $puq_pm_lxc_preset->puqPmDnsZone;
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;
        $puq_pm_lxc_preset_cluster_group = $puq_pm_lxc_preset
            ->puqPmLxcPresetClusterGroups()
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster->puq_pm_cluster_group_uuid)
            ->first();

        $public_networks = $puq_pm_lxc_preset_cluster_group->getPublicNetworks();
        $ipv4_public_network = $this->puqPmLxcInstanceNets()
            ->where('type', 'public')
            ->whereNotNull('puq_pm_ipv4_pool_uuid')
            ->first();

        $ipv6_public_network = $puq_pm_cluster_group->getAvailablePublicNetwork($puq_pm_node, $public_networks, 'ipv6');
        if (!$ipv6_public_network) {
            return [
                'status' => 'error',
                'errors' => ['IPv6 public network is unavailable'],
            ];
        }

        // IPv6 checks

        $macPoolIPv6 = $ipv6_public_network->puqPmMacPool;
        $ipPoolIPv6 = $ipv6_public_network->puqPmIpPool;

        if ($ipPoolIPv6) {
            $ip_ipv6 = $ipPoolIPv6->getIp();
        } else {
            $ip_ipv6 = 'dhcp';
        }

        $mac_ipv6 = $macPoolIPv6->getMac();

        if (!$mac_ipv6) {
            return [
                'status' => 'error',
                'errors' => ["No free MAC in MAC pool '{$macPoolIPv6->name}' for IPv6"],
            ];
        }
        if (!$ip_ipv6) {
            return [
                'status' => 'error',
                'errors' => ["No free IP in IP pool '{$ipPoolIPv6->name}' for IPv6"],
            ];
        }


        if ($ipv4_public_network) {
            $ipv4_public_network->name = $puq_pm_lxc_preset->pn_name;
        }

        $puq_pm_lxc_instance_net = new PuqPmLxcInstanceNet();
        $puq_pm_lxc_instance_net->name = $puq_pm_lxc_preset->pn_name.'v6';
        $puq_pm_lxc_instance_net->puq_pm_lxc_instance_uuid = $this->uuid;
        $puq_pm_lxc_instance_net->type = 'public';
        $puq_pm_lxc_instance_net->puq_pm_mac_pool_uuid = $macPoolIPv6->uuid;
        $puq_pm_lxc_instance_net->mac = $mac_ipv6;
        $puq_pm_lxc_instance_net->puq_pm_ipv6_pool_uuid = $ipPoolIPv6->uuid ?? null;
        $puq_pm_lxc_instance_net->ipv6 = $ip_ipv6 == 'dhcp' ? null : $ip_ipv6;
        $puq_pm_lxc_instance_net->rdns_v6 = $this->hostname.'.'.$puq_pm_dns_zone->name;
        $puq_pm_lxc_instance_net->save();

        return ['status' => 'success'];
    }

    public function createLocalPrivateNetwork(): array
    {
        $status = $this->getStatus();

        $service = $this->service;
        $client = $service->client;
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;
        $puq_pm_node = $this->puqPmNode()->where('name', $status['node'])->first();
        $local_private_networks = $puq_pm_cluster_group->getLocalPrivateNetworks();
        $local_private_network = $puq_pm_cluster_group->getAvailableLocalPrivateNetwork($puq_pm_node,
            $local_private_networks);
        $macPoolLocalPrivateNetwork = $local_private_network->puqPmMacPool;
        $mac_local_private_network = $macPoolLocalPrivateNetwork->getMac();

        $puq_pm_client_private_network = PuqPmClientPrivateNetwork::query()
            ->where('type', 'local_private')
            ->where('client_uuid', $client->uuid)
            ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster_group->uuid)
            ->first();
        if (!$puq_pm_client_private_network) {
            $bridge_vlan_tag = $local_private_network->getLocalBridgeVlanTag($puq_pm_cluster_group->uuid);
            if (!$bridge_vlan_tag) {
                return [
                    'status' => 'error',
                    'errors' => ["No available bridge or vlan in Local Private Network"],
                ];
            }

            $puq_pm_client_private_network = PuqPmClientPrivateNetwork::createLocalPrivateNetwork($client->uuid,
                $puq_pm_cluster_group->uuid, $bridge_vlan_tag['bridge'],
                $bridge_vlan_tag['vlan_tag']);
        }
        $ipv4_cidr = $puq_pm_client_private_network->getIPv4();

        if (!$ipv4_cidr) {
            return [
                'status' => 'error',
                'errors' => ["No available IP in Local Private Network"],
            ];
        }

        list($ip_ipv4, $prefix_ipv) = explode('/', $ipv4_cidr);

        $puq_pm_lxc_instance_net = new PuqPmLxcInstanceNet();
        $puq_pm_lxc_instance_net->name = $puq_pm_lxc_preset->lpn_name;
        $puq_pm_lxc_instance_net->puq_pm_lxc_instance_uuid = $this->uuid;
        $puq_pm_lxc_instance_net->type = 'local_private';
        $puq_pm_lxc_instance_net->puq_pm_mac_pool_uuid = $macPoolLocalPrivateNetwork->uuid;
        $puq_pm_lxc_instance_net->mac = $mac_local_private_network;
        $puq_pm_lxc_instance_net->ipv4 = $ip_ipv4;
        $puq_pm_lxc_instance_net->mask_v4 = $prefix_ipv;
        $puq_pm_lxc_instance_net->save();

        return ['status' => 'success'];
    }

    public function createGlobalPrivateNetwork(): array
    {
        $status = $this->getStatus();

        $service = $this->service;
        $client = $service->client;
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_cluster = $this->puqPmCluster;
        $puq_pm_cluster_group = $puq_pm_cluster->puqPmClusterGroup;
        $puq_pm_node = $this->puqPmNode()->where('name', $status['node'])->first();
        $global_private_networks = $puq_pm_cluster_group->getGlobalPrivateNetworks();
        $global_private_network = $puq_pm_cluster_group->getAvailableGlobalPrivateNetwork($puq_pm_node,
            $global_private_networks);

        if (!$global_private_network) {
            return [
                'status' => 'error',
                'errors' => ['Global private network is unavailable'],
            ];
        }

        $macPoolGlobalPrivateNetwork = $global_private_network->puqPmMacPool;
        $mac_global_private_network = $macPoolGlobalPrivateNetwork->getMac();

        $puq_pm_client_private_network = PuqPmClientPrivateNetwork::query()
            ->where('type', 'global_private')
            ->where('client_uuid', $client->uuid)
            ->first();

        if (!$puq_pm_client_private_network) {
            $bridge_vlan_tag = $global_private_network->getGlobalBridgeVlanTag();

            if (!$bridge_vlan_tag) {
                return [
                    'status' => 'error',
                    'errors' => ["No available bridge or vlan in Global Private Network"],
                ];
            }

            $puq_pm_client_private_network = PuqPmClientPrivateNetwork::createGlobalPrivateNetwork($client->uuid,
                $bridge_vlan_tag['bridge'],
                $bridge_vlan_tag['vlan_tag']);
        }

        $ipv4_cidr = $puq_pm_client_private_network->getIPv4();

        if (!$ipv4_cidr) {
            return [
                'status' => 'error',
                'errors' => ["No available IP in Global Private Network"],
            ];
        }

        list($ip_ipv4, $prefix_ipv) = explode('/', $ipv4_cidr);

        $puq_pm_lxc_instance_net = new PuqPmLxcInstanceNet();
        $puq_pm_lxc_instance_net->name = $puq_pm_lxc_preset->gpn_name;
        $puq_pm_lxc_instance_net->puq_pm_lxc_instance_uuid = $this->uuid;
        $puq_pm_lxc_instance_net->type = 'global_private';
        $puq_pm_lxc_instance_net->puq_pm_mac_pool_uuid = $macPoolGlobalPrivateNetwork->uuid;
        $puq_pm_lxc_instance_net->mac = $mac_global_private_network;
        $puq_pm_lxc_instance_net->ipv4 = $ip_ipv4;
        $puq_pm_lxc_instance_net->mask_v4 = $prefix_ipv;
        $puq_pm_lxc_instance_net->save();

        return ['status' => 'success'];
    }

    public function setNetworkConfiguration($config): array
    {
        $puq_pm_lxc_instance_nets = $this->puqPmLxcInstanceNets;
        $puq_pm_lxc_preset = $this->puqPmLxcPreset;
        $puq_pm_cluster = $this->puqPmCluster;
        $status = $this->getStatus();

        $net_conf = $this->buildLxcNetworks($puq_pm_lxc_instance_nets, $puq_pm_cluster, $puq_pm_lxc_preset);

        $to_delete = [];
        foreach ($config['data'] as $key => $val) {
            if (in_array($key, ['net0', 'net1', 'net2', 'net3'])) {
                if (empty($net_conf[$key])) {
                    $to_delete[] = $key;
                }
            }
        }

        if (!empty($to_delete)) {
            $delete_nets = $puq_pm_cluster->deleteLxcConfig($status['node'], $this->vmid, implode(',', $to_delete));

            if ($delete_nets['status'] == 'error') {
                return $delete_nets;
            }
        }

        $set_config = $puq_pm_cluster->setLxcConfig($status['node'], $this->vmid, $net_conf);

        return $set_config;
    }
}
