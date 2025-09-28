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

use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PuqPmLxcPreset extends Model
{
    protected $table = 'puq_pm_lxc_presets';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'hostname',
        'description',
        'arch',
        'cores',
        'cpulimit',
        'cpuunits',
        'memory',
        'swap',
        'rootfs_size',
        'rootfs_mountoptions',
        'mp',
        'mp_size',
        'mp_mountoptions',
        'mp_backup',
        'vzdump_mode',
        'vzdump_compress',
        'vzdump_bwlimit',
        'backup_count',
        'pn_name',
        'pn_rate',
        'pn_firewall',
        'pn_mtu',
        'lpn_name',
        'lpn_rate',
        'lpn_firewall',
        'lpn_mtu',
        'gpn_name',
        'gpn_rate',
        'gpn_firewall',
        'gpn_mtu',
        'puq_pm_dns_zone_uuid',
        'puq_pm_dns_zone_uuid',
        'firewall_enable', 'firewall_dhcp', 'firewall_ipfilter', 'firewall_macfilter', 'firewall_ndp', 'firewall_radv',
        'firewall_log_level_in', 'firewall_log_level_out', 'firewall_policy_in', 'firewall_policy_out',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmLxcPresetClusterGroups(): HasMany
    {
        return $this->hasMany(PuqPmLxcPresetClusterGroup::class, 'puq_pm_lxc_preset_uuid', 'uuid');
    }

    public function puqPmDnsZone(): BelongsTo
    {
        return $this->belongsTo(PuqPmDnsZone::class, 'puq_pm_dns_zone_uuid', 'uuid');
    }

    public function puqPmLxcInstances(): HasMany
    {
        return $this->hasMany(PuqPmLxcInstance::class, 'puq_pm_lxc_preset_uuid', 'uuid');
    }

    public function puqPmLxcOsTemplates(): BelongsToMany
    {
        return $this->belongsToMany(PuqPmLxcOsTemplate::class,
            'puq_pm_lxc_preset_x_lxc_os_templates',
            'puq_pm_lxc_preset_uuid',
            'puq_pm_lxc_os_template_uuid'
        );
    }

    // --------------------------------------------------------------------------------

    private function getTagsByLocation(string $location): ?array
    {
        $puq_pm_cluster_group = PuqPmClusterGroup::getByLocation($location);
        $puq_pm_lxc_preset_cluster_group = $this->puqPmLxcPresetClusterGroups()->where('puq_pm_cluster_group_uuid',
            $puq_pm_cluster_group->uuid)->first();

        if (!$puq_pm_lxc_preset_cluster_group) {
            return null;
        }

        return [
            'node' => $puq_pm_lxc_preset_cluster_group->getTagsByType('node'),
            'rootfs_storage' => $puq_pm_lxc_preset_cluster_group->getTagsByType('rootfs_storage'),
            'additional_storage' => $puq_pm_lxc_preset_cluster_group->getTagsByType('additional_storage'),
            'backup_storage' => $puq_pm_lxc_preset_cluster_group->getTagsByType('backup_storage'),
            'public_network' => $puq_pm_lxc_preset_cluster_group->getTagsByType('public_network'),
            'local_private_network' => $puq_pm_lxc_preset_cluster_group->getTagsByType('local_private_network'),
            'global_private_network' => $puq_pm_lxc_preset_cluster_group->getTagsByType('global_private_network'),
        ];
    }

    public function getServiceProductOptions($service, array $product_data): array
    {
        $getOption = function ($group_uuid) use ($service) {
            return $service->productOptions()
                ->where('product_option_group_uuid', $group_uuid)
                ->first();
        };

        $location_product_option = $getOption($product_data['location_product_option_group_uuid'] ?? null);
        $os_product_option_group = $getOption($product_data['os_product_option_group_uuid'] ?? null);
        $ipv4_product_option = $getOption($product_data['ipv4_product_option_group_uuid'] ?? null);
        $ipv6_product_option = $getOption($product_data['ipv6_product_option_group_uuid'] ?? null);
        $local_private_network_product_option = $getOption($product_data['local_private_network_product_option_group_uuid'] ?? null);
        $global_private_network_ipv4_product_option = $getOption($product_data['global_private_network_product_option_group_uuid'] ?? null);
        $cpu_cores_product_option = $getOption($product_data['cpu_cores_product_option_group_uuid'] ?? null);
        $memory_product_option = $getOption($product_data['memory_product_option_group_uuid'] ?? null);
        $rootfs_size_product_option = $getOption($product_data['rootfs_size_product_option_group_uuid'] ?? null);
        $mp_size_product_option = $getOption($product_data['mp_size_product_option_group_uuid'] ?? null);
        $backup_count_product_option = $getOption($product_data['backup_count_product_option_group_uuid'] ?? null);

        $template = $this->puqPmLxcOsTemplates()
            ->where('key', $os_product_option_group?->value)
            ->first();

        return [
            'service_uuid' => $service->uuid,
            'location' => $location_product_option->value ?? '',
            'ipv4_public_network' => $ipv4_product_option->value ?? false,
            'ipv6_public_network' => $ipv6_product_option->value ?? false,
            'local_private_network' => $local_private_network_product_option->value ?? false,
            'global_private_network' => $global_private_network_ipv4_product_option->value ?? false,
            'cores' => $cpu_cores_product_option->value ?? 0,
            'memory' => $memory_product_option->value ?? 0,
            'rootfs_size' => $rootfs_size_product_option->value ?? 0,
            'mp_size' => $mp_size_product_option->value ?? 0,
            'backup_count' => $backup_count_product_option->value ?? 0,
            'os_template_uuid' => $template?->uuid,
        ];
    }

    public function createLxcInstance(Service $service, array $product_data): array
    {

        $product_options = $this->getServiceProductOptions($service, $product_data);
        $location = $product_options['location'];
        $client = $service->client;

        $puq_pm_cluster_group = PuqPmClusterGroup::getByLocation($location);
        if (!$puq_pm_cluster_group) {
            return [
                'status' => 'error',
                'errors' => ['No available cluster group for the location: '.$location],
            ];
        }

        $puq_pm_lxc_os_template = $this->puqPmLxcOsTemplates()->where('uuid',
            $product_options['os_template_uuid'])->first();
        if (!$puq_pm_lxc_os_template) {
            return [
                'status' => 'error',
                'errors' => ['OS template is not available'],
            ];
        }

        DB::beginTransaction();
        try {
            // Get Available resources (node, storage, network, etc)
            $needs = [
                'cores' => $this->cores + $product_options['cores'],
                'memory' => $this->memory + $product_options['memory'],
                'rootfs_size' => $this->rootfs_size + $product_options['rootfs_size'],
                'mp_size' => $this->mp_size + $product_options['mp_size'],
                'backup_count' => $this->backup_count + $product_options['backup_count'],
                'ipv4_public_network' => $product_options['ipv4_public_network'],
                'ipv6_public_network' => $product_options['ipv6_public_network'],
                'local_private_network' => $product_options['local_private_network'],
                'global_private_network' => $product_options['global_private_network'],
            ];
            $tags = $this->getTagsByLocation($location);
            $resources = $puq_pm_cluster_group->getAvailableResources($needs, $tags);

            if (empty($resources['puq_pm_node'])) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['Node is unavailable'],
                ];
            }

            if (empty($resources['rootfs_puq_pm_storage'])) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['Main disk storage is unavailable'],
                ];
            }

            if ($needs['mp_size'] > 0 and empty($resources['mp_puq_pm_storage'])) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['Addition disk storage is unavailable'],
                ];
            }

            if ($needs['backup_count'] > 0 and empty($resources['backup_puq_pm_storage'])) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['Backup storage is unavailable'],
                ];
            }

            if ($needs['ipv4_public_network'] and empty($resources['ipv4_public_network'])) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['IPv4 public network is unavailable'],
                ];
            }

            if ($needs['ipv6_public_network'] and empty($resources['ipv6_public_network'])) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['IPv6 public network is unavailable'],
                ];
            }

            if ($needs['local_private_network'] and empty($resources['local_private_network'])) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['Local private network is unavailable'],
                ];
            }

            if ($needs['global_private_network'] and empty($resources['global_private_network'])) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'errors' => ['Global private network is unavailable'],
                ];
            }

            $puq_pm_node = $resources['puq_pm_node'];
            $puq_pm_cluster = $puq_pm_node->puqPmCluster;
            $rootfs_puq_pm_storage = $resources['rootfs_puq_pm_storage'];
            $mp_puq_pm_storage = $resources['mp_puq_pm_storage'];
            $backup_puq_pm_storage = $resources['backup_puq_pm_storage'];
            $ipv4_public_network = $resources['ipv4_public_network'];
            $ipv6_public_network = $resources['ipv6_public_network'];
            $local_private_network = $resources['local_private_network'];
            $global_private_network = $resources['global_private_network'];

            $puq_pm_dns_zone = $this->puqPmDnsZone;

            $excludeMacs = [];
            // IPv4 checks
            if ($product_options['ipv4_public_network']) {
                $macPoolIPv4 = $ipv4_public_network->puqPmMacPool;

                $ipPoolIPv4 = $ipv4_public_network->puqPmIpPool;
                if ($ipPoolIPv4) {
                    $ip_ipv4 = $ipPoolIPv4->getIp();
                } else {
                    $ip_ipv4 = 'dhcp';
                }

                $mac_ipv4 = $macPoolIPv4->getMac($excludeMacs);
                $excludeMacs[] = $mac_ipv4;

                if (!$mac_ipv4) {
                    DB::rollBack();

                    return [
                        'status' => 'error',
                        'errors' => ["No free MAC in MAC pool '{$macPoolIPv4->name}' for IPv4"],
                    ];
                }
                if (!$ip_ipv4) {
                    DB::rollBack();

                    return [
                        'status' => 'error',
                        'errors' => ["No free IP in IP pool '{$ipPoolIPv4->name}' for IPv4"],
                    ];
                }
            }

            // IPv6 separate interface check
            $needSeparateIPv6 = false;
            if ($product_options['ipv6_public_network']) {
                $macPoolIPv6 = $ipv6_public_network->puqPmMacPool;
                $ipPoolIPv6 = $ipv6_public_network->puqPmIpPool;

                if (!$product_options['ipv4_public_network']) {
                    $needSeparateIPv6 = true;
                } else {
                    if (
                        $ipv4_public_network->bridge !== $ipv6_public_network->bridge ||
                        $ipv4_public_network->vlan_tag !== $ipv6_public_network->vlan_tag
                    ) {
                        $needSeparateIPv6 = true;
                    }
                }

                if ($needSeparateIPv6) {
                    $mac_ipv6 = $macPoolIPv6->getMac($excludeMacs);

                    if ($ipPoolIPv6) {
                        $ip_ipv6 = $ipPoolIPv6->getIp();
                    } else {
                        $ip_ipv6 = 'dhcp';
                    }

                    if (!$mac_ipv6) {
                        DB::rollBack();

                        return [
                            'status' => 'error',
                            'errors' => ["No free MAC in MAC pool '{$macPoolIPv6->name}' for IPv6"],
                        ];
                    }
                    if (!$ip_ipv6) {
                        DB::rollBack();

                        return [
                            'status' => 'error',
                            'errors' => ["No free IP in IP pool '{$ipPoolIPv6->name}' for IPv6"],
                        ];
                    }
                } else {
                    if ($ipPoolIPv6) {
                        $ip_ipv6 = $ipPoolIPv6->getIp();
                    } else {
                        $ip_ipv6 = 'dhcp';
                    }
                    if (!$ip_ipv6) {
                        DB::rollBack();

                        return [
                            'status' => 'error',
                            'errors' => ["No free IP in IP pool '{$ipPoolIPv6->name}' for IPv6"],
                        ];
                    }
                }
            }

            $parts = explode('-', $location);
            $country = $parts[0];

            $puqPmLxcInstance = new PuqPmLxcInstance();
            $puqPmLxcInstance->hostname = $puq_pm_dns_zone->generateLxcHostname($this->hostname, $country);
            $puqPmLxcInstance->vmid = null;
            $puqPmLxcInstance->puq_pm_lxc_preset_uuid = $this->uuid;
            $puqPmLxcInstance->puq_pm_dns_zone_uuid = $puq_pm_dns_zone->uuid;
            $puqPmLxcInstance->service_uuid = $service->uuid;
            $puqPmLxcInstance->puq_pm_cluster_uuid = $puq_pm_cluster->uuid;

            $puqPmLxcInstance->cores = $needs['cores'];
            $puqPmLxcInstance->memory = $needs['memory'];
            $puqPmLxcInstance->rootfs_size = $needs['rootfs_size'];
            $puqPmLxcInstance->mp_size = $needs['mp_size'];
            $puqPmLxcInstance->backup_count = $needs['backup_count'];

            $puqPmLxcInstance->puq_pm_lxc_os_template_uuid = $puq_pm_lxc_os_template->uuid;

            $puqPmLxcInstance->puq_pm_node_uuid = $puq_pm_node->uuid ?? null;
            $puqPmLxcInstance->rootfs_puq_pm_storage_uuid = $rootfs_puq_pm_storage->uuid ?? null;
            $puqPmLxcInstance->mp_puq_pm_storage_uuid = $mp_puq_pm_storage->uuid ?? null;
            $puqPmLxcInstance->backup_puq_pm_storage_uuid = $backup_puq_pm_storage->uuid ?? null;

            $puqPmLxcInstance->save();

            if ($product_options['ipv4_public_network']) {
                $puq_pm_lxc_instance_net = new PuqPmLxcInstanceNet();
                $puq_pm_lxc_instance_net->name = $this->pn_name;
                $puq_pm_lxc_instance_net->puq_pm_lxc_instance_uuid = $puqPmLxcInstance->uuid;
                $puq_pm_lxc_instance_net->type = 'public';
                $puq_pm_lxc_instance_net->puq_pm_mac_pool_uuid = $macPoolIPv4->uuid;
                $puq_pm_lxc_instance_net->mac = $mac_ipv4;
                $puq_pm_lxc_instance_net->puq_pm_ipv4_pool_uuid = $ipPoolIPv4->uuid ?? null;
                $puq_pm_lxc_instance_net->ipv4 = $ip_ipv4 == 'dhcp' ? null : $ip_ipv4;
                $puq_pm_lxc_instance_net->rdns_v4 = $puqPmLxcInstance->hostname.'.'.$puq_pm_dns_zone->name;

                if ($product_options['ipv6_public_network'] && !$needSeparateIPv6) {
                    $puq_pm_lxc_instance_net->puq_pm_ipv6_pool_uuid = $ipPoolIPv6->uuid ?? null;
                    $puq_pm_lxc_instance_net->ipv6 = $ip_ipv6 == 'dhcp' ? null : $ip_ipv6;
                    $puq_pm_lxc_instance_net->rdns_v6 = $puqPmLxcInstance->hostname.'.'.$puq_pm_dns_zone->name;
                }

                $puq_pm_lxc_instance_net->save();

                if ($product_options['ipv6_public_network'] && $needSeparateIPv6) {
                    $puq_pm_lxc_instance_net_v6 = new PuqPmLxcInstanceNet();
                    $puq_pm_lxc_instance_net_v6->name = $this->pn_name.'v6';
                    $puq_pm_lxc_instance_net_v6->puq_pm_lxc_instance_uuid = $puqPmLxcInstance->uuid;
                    $puq_pm_lxc_instance_net_v6->type = 'public';
                    $puq_pm_lxc_instance_net_v6->puq_pm_mac_pool_uuid = $macPoolIPv6->uuid;
                    $puq_pm_lxc_instance_net_v6->mac = $mac_ipv6;
                    $puq_pm_lxc_instance_net_v6->puq_pm_ipv6_pool_uuid = $ipPoolIPv6->uuid ?? null;
                    $puq_pm_lxc_instance_net_v6->ipv6 = $ip_ipv6 == 'dhcp' ? null : $ip_ipv6;
                    $puq_pm_lxc_instance_net_v6->rdns_v6 = $puqPmLxcInstance->hostname.'.'.$puq_pm_dns_zone->name;
                    $puq_pm_lxc_instance_net_v6->save();
                }
            }

            if (!$product_options['ipv4_public_network'] && $product_options['ipv6_public_network']) {
                $puq_pm_lxc_instance_net = new PuqPmLxcInstanceNet();
                $puq_pm_lxc_instance_net->name = $this->pn_name.'v6';
                $puq_pm_lxc_instance_net->puq_pm_lxc_instance_uuid = $puqPmLxcInstance->uuid;
                $puq_pm_lxc_instance_net->type = 'public';
                $puq_pm_lxc_instance_net->puq_pm_mac_pool_uuid = $macPoolIPv6->uuid;
                $puq_pm_lxc_instance_net->mac = $mac_ipv6;
                $puq_pm_lxc_instance_net->puq_pm_ipv6_pool_uuid = $ipPoolIPv6->uuid ?? null;
                $puq_pm_lxc_instance_net->ipv6 = $ip_ipv6 == 'dhcp' ? null : $ip_ipv6;
                $puq_pm_lxc_instance_net->rdns_v6 = $puqPmLxcInstance->hostname.'.'.$puq_pm_dns_zone->name;
                $puq_pm_lxc_instance_net->save();
            }

            if ($product_options['local_private_network']) {
                $macPoolLocalPrivateNetwork = $local_private_network->puqPmMacPool;
                $mac_local_private_network = $macPoolLocalPrivateNetwork->getMac($excludeMacs);
                $excludeMacs[] = $mac_local_private_network;

                $puq_pm_client_private_network = PuqPmClientPrivateNetwork::query()
                    ->where('type', 'local_private')
                    ->where('client_uuid', $client->uuid)
                    ->where('puq_pm_cluster_group_uuid', $puq_pm_cluster_group->uuid)
                    ->first();
                if (!$puq_pm_client_private_network) {
                    $bridge_vlan_tag = $local_private_network->getLocalBridgeVlanTag($puq_pm_cluster_group->uuid);
                    if (!$bridge_vlan_tag) {
                        DB::rollBack();

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
                    DB::rollBack();

                    return [
                        'status' => 'error',
                        'errors' => ["No available IP in Local Private Network"],
                    ];
                }

                list($ip_ipv4, $prefix_ipv) = explode('/', $ipv4_cidr);

                $puq_pm_lxc_instance_net = new PuqPmLxcInstanceNet();
                $puq_pm_lxc_instance_net->name = $this->lpn_name;
                $puq_pm_lxc_instance_net->puq_pm_lxc_instance_uuid = $puqPmLxcInstance->uuid;
                $puq_pm_lxc_instance_net->type = 'local_private';
                $puq_pm_lxc_instance_net->puq_pm_mac_pool_uuid = $macPoolLocalPrivateNetwork->uuid;
                $puq_pm_lxc_instance_net->mac = $mac_local_private_network;
                $puq_pm_lxc_instance_net->ipv4 = $ip_ipv4;
                $puq_pm_lxc_instance_net->mask_v4 = $prefix_ipv;
                $puq_pm_lxc_instance_net->save();
            }

            if ($product_options['global_private_network']) {
                $macPoolGlobalPrivateNetwork = $global_private_network->puqPmMacPool;
                $mac_global_private_network = $macPoolGlobalPrivateNetwork->getMac($excludeMacs);

                $puq_pm_client_private_network = PuqPmClientPrivateNetwork::query()
                    ->where('type', 'global_private')
                    ->where('client_uuid', $client->uuid)
                    ->first();

                if (!$puq_pm_client_private_network) {
                    $bridge_vlan_tag = $global_private_network->getGlobalBridgeVlanTag();

                    if (!$bridge_vlan_tag) {
                        DB::rollBack();

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
                    DB::rollBack();

                    return [
                        'status' => 'error',
                        'errors' => ["No available IP in Global Private Network"],
                    ];
                }

                list($ip_ipv4, $prefix_ipv) = explode('/', $ipv4_cidr);

                $puq_pm_lxc_instance_net = new PuqPmLxcInstanceNet();
                $puq_pm_lxc_instance_net->name = $this->gpn_name;
                $puq_pm_lxc_instance_net->puq_pm_lxc_instance_uuid = $puqPmLxcInstance->uuid;
                $puq_pm_lxc_instance_net->type = 'global_private';
                $puq_pm_lxc_instance_net->puq_pm_mac_pool_uuid = $macPoolGlobalPrivateNetwork->uuid;
                $puq_pm_lxc_instance_net->mac = $mac_global_private_network;
                $puq_pm_lxc_instance_net->ipv4 = $ip_ipv4;
                $puq_pm_lxc_instance_net->mask_v4 = $prefix_ipv;
                $puq_pm_lxc_instance_net->save();
            }


            $puqPmLxcInstance->save();

            DB::commit();

            return [
                'status' => 'success',
                'data' => $puqPmLxcInstance,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    // Product Configuration ---------------------------------------------------------------------------------
    public function getLocationOptionMappings(?ProductOptionGroup $product_option_group): array
    {
        $product_option_values = $product_option_group
            ? array_map('strtolower', $product_option_group->productOptions()->pluck('value')->toArray())
            : [];

        $data = [];
        $used_values = [];

        foreach ($this->puqPmLxcPresetClusterGroups as $lxc_preset_cluster_group) {
            $cluster_group = $lxc_preset_cluster_group->puqPmClusterGroup;

            $value = null;
            if ($cluster_group && $cluster_group->country && $cluster_group->data_center) {
                $value = strtolower($cluster_group->country->code.'-'.$cluster_group->data_center);
            }

            if (in_array($value, $product_option_values)) {
                $used_values[] = $value;
            }
            $data[] = [
                'cluster_group' => $cluster_group,
                'value' => in_array($value, $product_option_values) ? $value : null,
                'mapped' => in_array($value, $product_option_values),
            ];
        }

        foreach ($product_option_values as $value) {
            if (!in_array($value, $used_values)) {
                $data[] = [
                    'cluster_group' => null,
                    'value' => $value,
                    'mapped' => false,
                ];
            }
        }

        return $data;
    }

    public function getOsOptionMappings(?ProductOptionGroup $product_option_group): array
    {
        $product_option_values = $product_option_group
            ? array_map('strtolower', $product_option_group->productOptions()->pluck('value')->toArray())
            : [];

        $data = [];
        $used_values = [];

        foreach ($this->puqPmLxcOsTemplates as $puqPmLxcOsTemplate) {

            $value = strtolower($puqPmLxcOsTemplate->key);


            if (in_array($value, $product_option_values)) {
                $used_values[] = $value;
            }
            $data[] = [
                'os_template' => $puqPmLxcOsTemplate,
                'value' => in_array($value, $product_option_values) ? $value : null,
                'mapped' => in_array($value, $product_option_values),
            ];
        }

        foreach ($product_option_values as $value) {
            if (!in_array($value, $used_values)) {
                $data[] = [
                    'cluster_group' => null,
                    'value' => $value,
                    'mapped' => false,
                ];
            }
        }

        return $data;
    }

    public function getLxcAttributes(Product $product): array
    {
        $configuration = $product->configuration;

        $groups = [
            'cpu' => 'cpu_product_attribute_group_uuid',
            'ram' => 'memory_product_attribute_group_uuid',
            'rootfs' => 'rootfs_product_attribute_group_uuid',
            'mp' => 'mp_product_attribute_group_uuid',
            'backup_count' => 'backup_count_product_attribute_group_uuid',
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

}
