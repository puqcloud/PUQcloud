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

use App\Models\HomeCompany;
use App\Models\ProductOption;
use App\Models\Service;
use App\Models\Task;
use App\Modules\Product;
use App\Services\SettingService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Product\puqProxmox\Models\PuqPmLxcInstance;
use Modules\Product\puqProxmox\Models\PuqPmLxcOsTemplate;
use Modules\Product\puqProxmox\Models\PuqPmLxcPreset;
use Modules\Product\puqProxmox\Models\PuqPmSshPublicKey;

class puqProxmox extends Product
{

    public string $log_level = 'debug'; // 'error', 'info', 'debug'

    public $product_data;

    public $product_uuid;

    public $service_data;

    public $service_uuid;

    public function __construct()
    {
        parent::__construct();
    }

    public function activate(): string
    {
        try {

            if (!Schema::hasTable('puq_pm_cluster_groups')) {
                Schema::create('puq_pm_cluster_groups', function (Blueprint $table) {
                    $table->uuid()->primary();

                    $table->string('name')->unique();
                    $table->string('fill_type')->default('default'); // lowest or default
                    $table->string('description')->nullable();
                    $table->uuid('country_uuid')->nullable();
                    $table->uuid('region_uuid')->nullable();
                    $table->string('data_center')->nullable();
                    $table->string('local_private_network')->default('172.16.0.0/24'); // will bee default for client's local private network in this location
                    $table->timestamps();
                });

                $groupExists = DB::table('puq_pm_cluster_groups')->where('name', 'Default')->exists();
                $home_company = HomeCompany::query()->where('default', true)->first();

                if (!$groupExists) {
                    DB::table('puq_pm_cluster_groups')->insert([
                        'uuid' => (string) Str::uuid(),
                        'name' => 'Default',
                        'fill_type' => 'default',
                        'country_uuid' => $home_company->country_uuid,
                        'region_uuid' => $home_company->region_uuid,
                        'data_center' => 'Default',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (!Schema::hasTable('puq_pm_clusters')) {
                Schema::create('puq_pm_clusters', function (Blueprint $table) {

                    $table->uuid()->primary();
                    $table->uuid('puq_pm_cluster_group_uuid');

                    $table->string('name')->unique();
                    $table->longText('description')->nullable();
                    $table->boolean('disable')->default(false);
                    $table->boolean('default')->default(false);
                    $table->integer('max_accounts')->default(0);

                    $table->string('vncwebproxy_domain')->nullable();
                    $table->string('vncwebproxy_api_key')->nullable();

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_access_servers')) {
                Schema::create('puq_pm_access_servers', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->uuid('puq_pm_cluster_uuid');
                    $table->foreign('puq_pm_cluster_uuid')->references('uuid')->on('puq_pm_clusters')->onDelete('cascade');

                    $table->string('name');
                    $table->string('description')->nullable();

                    $table->string('ssh_host');
                    $table->string('ssh_username');
                    $table->longText('ssh_password');
                    $table->integer('ssh_port')->default(22);
                    $table->integer('ssh_response_time')->default(0);
                    $table->string('ssh_error')->nullable();

                    $table->string('api_host');
                    $table->string('api_token_id');
                    $table->longText('api_token');
                    $table->integer('api_port')->default(8006);
                    $table->integer('api_response_time')->default(0);
                    $table->string('api_error')->nullable();

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_ip_pools')) {
                Schema::create('puq_pm_ip_pools', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');
                    $table->string('type'); // IPv4 or IPv6
                    $table->string('first_ip');
                    $table->string('last_ip');
                    $table->integer('mask');
                    $table->string('gateway');
                    $table->string('dns');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_mac_pools')) {
                Schema::create('puq_pm_mac_pools', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');
                    $table->string('first_mac');
                    $table->string('last_mac');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_nodes')) {
                Schema::create('puq_pm_nodes', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->uuid('puq_pm_cluster_uuid');
                    $table->foreign('puq_pm_cluster_uuid')->references('uuid')->on('puq_pm_clusters')->onDelete('cascade');

                    $table->string('id')->nullable();
                    $table->string('name')->nullable();
                    $table->float('cpu')->nullable();
                    $table->string('level')->nullable();
                    $table->bigInteger('maxcpu')->nullable();
                    $table->bigInteger('maxmem')->nullable();
                    $table->bigInteger('mem')->nullable();
                    $table->string('status')->nullable();
                    $table->bigInteger('uptime')->nullable();

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_tags')) {
                Schema::create('puq_pm_tags', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_node_x_tag')) {
                Schema::create('puq_pm_node_x_tag', function (Blueprint $table) {

                    $table->uuid('puq_pm_tag_uuid');
                    $table->foreign('puq_pm_tag_uuid')->references('uuid')->on('puq_pm_tags')->onDelete('cascade');

                    $table->uuid('puq_pm_node_uuid');
                    $table->foreign('puq_pm_node_uuid')->references('uuid')->on('puq_pm_nodes')->onDelete('cascade');

                    $table->primary(['puq_pm_tag_uuid', 'puq_pm_node_uuid']);
                });
            }

            if (!Schema::hasTable('puq_pm_storages')) {
                Schema::create('puq_pm_storages', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->uuid('puq_pm_node_uuid');
                    $table->foreign('puq_pm_node_uuid')->references('uuid')->on('puq_pm_nodes')->onDelete('cascade');

                    $table->uuid('puq_pm_cluster_uuid');
                    $table->foreign('puq_pm_cluster_uuid')->references('uuid')->on('puq_pm_clusters')->onDelete('cascade');

                    $table->string('id')->nullable();
                    $table->string('name')->nullable();
                    $table->bigInteger('disk')->nullable();
                    $table->bigInteger('maxdisk')->nullable();
                    $table->string('plugintype')->nullable();
                    $table->boolean('shared')->nullable();
                    $table->string('status')->nullable();
                    $table->string('content')->nullable();

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_storage_x_tag')) {
                Schema::create('puq_pm_storage_x_tag', function (Blueprint $table) {

                    $table->uuid('puq_pm_tag_uuid');
                    $table->foreign('puq_pm_tag_uuid')->references('uuid')->on('puq_pm_tags')->onDelete('cascade');

                    $table->uuid('puq_pm_storage_uuid');
                    $table->foreign('puq_pm_storage_uuid')->references('uuid')->on('puq_pm_storages')->onDelete('cascade');

                    $table->primary(['puq_pm_tag_uuid', 'puq_pm_storage_uuid']);
                });
            }

            if (!Schema::hasTable('puq_pm_public_networks')) {
                Schema::create('puq_pm_public_networks', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');

                    $table->uuid('puq_pm_cluster_uuid');
                    $table->foreign('puq_pm_cluster_uuid')->references('uuid')->on('puq_pm_clusters')->onDelete('cascade');

                    $table->uuid('puq_pm_mac_pool_uuid');
                    $table->foreign('puq_pm_mac_pool_uuid')->references('uuid')->on('puq_pm_mac_pools')->onDelete('cascade');

                    $table->uuid('puq_pm_ip_pool_uuid')->nullable(); // if null, DHCP
                    $table->foreign('puq_pm_ip_pool_uuid')->references('uuid')->on('puq_pm_ip_pools')->onDelete('cascade');

                    $table->string('bridge');
                    $table->integer('vlan_tag')->default(0); // if 0, untagged

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_public_network_x_tag')) {
                Schema::create('puq_pm_public_network_x_tag', function (Blueprint $table) {

                    $table->uuid('puq_pm_tag_uuid');
                    $table->foreign('puq_pm_tag_uuid')->references('uuid')->on('puq_pm_tags')->onDelete('cascade');

                    $table->uuid('puq_pm_public_network_uuid');
                    $table->foreign('puq_pm_public_network_uuid')->references('uuid')->on('puq_pm_public_networks')->onDelete('cascade');

                    $table->primary(['puq_pm_tag_uuid', 'puq_pm_public_network_uuid']);
                });
            }

            if (!Schema::hasTable('puq_pm_private_networks')) {
                Schema::create('puq_pm_private_networks', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');
                    $table->string('type')->default('local_private'); // local or global

                    $table->uuid('puq_pm_cluster_uuid');
                    $table->foreign('puq_pm_cluster_uuid')->references('uuid')->on('puq_pm_clusters')->onDelete('cascade');

                    $table->uuid('puq_pm_mac_pool_uuid');
                    $table->foreign('puq_pm_mac_pool_uuid')->references('uuid')->on('puq_pm_mac_pools')->onDelete('cascade');

                    $table->string('bridge');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_private_network_x_tag')) {
                Schema::create('puq_pm_private_network_x_tag', function (Blueprint $table) {

                    $table->uuid('puq_pm_tag_uuid');
                    $table->foreign('puq_pm_tag_uuid')->references('uuid')->on('puq_pm_tags')->onDelete('cascade');

                    $table->uuid('puq_pm_private_network_uuid');
                    $table->foreign('puq_pm_private_network_uuid')->references('uuid')->on('puq_pm_private_networks')->onDelete('cascade');

                    $table->primary(['puq_pm_tag_uuid', 'puq_pm_private_network_uuid']);
                });
            }

            if (!Schema::hasTable('puq_pm_dns_zones')) {
                Schema::create('puq_pm_dns_zones', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');
                    $table->integer('ttl')->default(3600);

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_lxc_presets')) {
                Schema::create('puq_pm_lxc_presets', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');
                    $table->string('hostname')->default('{COUNTRY}-{RAND:10}');
                    // {YEAR}       - 4-digit current year (e.g., 2025)
                    // {MONTH}      - 2-digit current month (01–12)
                    // {DAY}        - 2-digit current day of the month (01–31)
                    // {HOUR}       - 2-digit hour in 24h format (00–23)
                    // {MINUTE}     - 2-digit current minute (00–59)
                    // {SECOND}     - 2-digit current second (00–59)
                    // {TIMESTAMP}  - Full timestamp in YmdHis format (e.g., 20250804153322)
                    // {RAND:X}     - Random number of X digits (e.g., {RAND:4} → 3842)
                    // {RSTR:X}     - Random uppercase string of X characters (e.g., {RSTR:5} → KDJRW)
                    // {COUNTRY}    - 2-letter country code of the client (e.g., US, PL, CA)

                    $table->text('description')->nullable();

                    $table->boolean('onboot')->default(true); // Specifies whether a container will be started during system bootup.

                    // CPU
                    $table->string('arch')->default('amd64'); // amd64 | i386 | arm64 | armhf | riscv32 | riscv64
                    $table->integer('cores')->default(1);
                    $table->integer('cpulimit')->default(0);
                    $table->integer('cpuunits')->default(8);

                    // RAM
                    $table->integer('memory')->default(16);
                    $table->integer('swap')->default(0);

                    // rootfs
                    $table->integer('rootfs_size')->default(1024);
                    $table->text('rootfs_mountoptions')->nullable(); // nosuid, noexec, nodev, noatime, lazytime, discard

                    // mount point
                    $table->string('mp')->default('/mnt/volume'); // will be adding number of MP(0,1,2....n)
                    $table->integer('mp_size')->default(1024);
                    $table->text('mp_mountoptions')->nullable(); // nosuid, noexec, nodev, noatime, lazytime, discard
                    $table->boolean('mp_backup')->default(true);

                    // Backup
                    $table->string('vzdump_mode')->default('suspend'); // snapshot, suspend, stop
                    $table->string('vzdump_compress')->default('zstd'); // 0, 1, gzip, lzo, zstd
                    $table->integer('vzdump_bwlimit')->default(0);
                    $table->integer('backup_count')->default(0);

                    // Public network
                    $table->string('pn_name')->default('eth0');
                    $table->integer('pn_rate')->nullable()->default(0); // Mbps
                    $table->boolean('pn_firewall')->default(true);
                    $table->integer('pn_mtu')->nullable()->default(0); // 0 - as bridge

                    // Local Private network
                    $table->string('lpn_name')->default('eth1');
                    $table->integer('lpn_rate')->nullable()->default(0); // Mbps
                    $table->boolean('lpn_firewall')->default(true);
                    $table->integer('lpn_mtu')->nullable()->default(0); // 0 - as bridge

                    // Global Private network
                    $table->string('gpn_name')->default('eth2');
                    $table->integer('gpn_rate')->nullable()->default(0); // Mbps
                    $table->boolean('gpn_firewall')->default(true);
                    $table->integer('gpn_mtu')->nullable()->default(0); // 0 - as bridge

                    $table->uuid('puq_pm_dns_zone_uuid');
                    $table->foreign('puq_pm_dns_zone_uuid')->references('uuid')->on('puq_pm_dns_zones')->onDelete('restrict');


                    // Firewall
                    $table->boolean('firewall_enable')->default(true);
                    $table->boolean('firewall_dhcp')->default(false);

                    $table->boolean('firewall_ipfilter')->default(true);
                    $table->boolean('firewall_macfilter')->default(true);

                    $table->string('firewall_log_level_in')->default('nolog');
                    $table->string('firewall_log_level_out')->default('nolog');
                    $table->string('firewall_policy_in')->default('ACCEPT');
                    $table->string('firewall_policy_out')->default('ACCEPT');

                    $table->boolean('firewall_ndp')->default(false);
                    $table->boolean('firewall_radv')->default(false);

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_lxc_preset_cluster_groups')) {
                Schema::create('puq_pm_lxc_preset_cluster_groups', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->uuid('puq_pm_lxc_preset_uuid');
                    $table->foreign('puq_pm_lxc_preset_uuid', 'fk_preset_pcg')
                        ->references('uuid')->on('puq_pm_lxc_presets')->onDelete('cascade');

                    $table->uuid('puq_pm_cluster_group_uuid');
                    $table->foreign('puq_pm_cluster_group_uuid', 'fk_cluster_group_pcg')
                        ->references('uuid')->on('puq_pm_cluster_groups')->onDelete('cascade');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_lxc_preset_cluster_group_x_tag')) {
                Schema::create('puq_pm_lxc_preset_cluster_group_x_tag', function (Blueprint $table) {

                    $table->uuid('puq_pm_tag_uuid');

                    $table->foreign('puq_pm_tag_uuid', 'fk_tag_pcg_x_tag')
                        ->references('uuid')->on('puq_pm_tags')->onDelete('cascade');

                    $table->uuid('puq_pm_lxc_preset_cluster_uuid');
                    $table->foreign('puq_pm_lxc_preset_cluster_uuid', 'fk_cluster_pcg_x_tag')
                        ->references('uuid')->on('puq_pm_lxc_preset_cluster_groups')->onDelete('cascade');

                    $table->string('type'); // node, rootfs_storage, etc.

                    $table->primary(['puq_pm_tag_uuid', 'puq_pm_lxc_preset_cluster_uuid', 'type']);
                });
            }

            if (!Schema::hasTable('puq_pm_lxc_templates')) {
                Schema::create('puq_pm_lxc_templates', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');
                    $table->string('url');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_lxc_os_templates')) {
                Schema::create('puq_pm_lxc_os_templates', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('key');
                    $table->string('name');
                    $table->string('distribution');
                    $table->string('version');

                    $table->uuid('puq_pm_lxc_template_uuid');
                    $table->foreign('puq_pm_lxc_template_uuid')->references('uuid')->on('puq_pm_lxc_templates')->onDelete('cascade');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_lxc_preset_x_lxc_os_templates')) {
                Schema::create('puq_pm_lxc_preset_x_lxc_os_templates', function (Blueprint $table) {

                    $table->uuid('puq_pm_lxc_preset_uuid');
                    $table->foreign('puq_pm_lxc_preset_uuid', 'fk_preset_x_lot')
                        ->references('uuid')->on('puq_pm_lxc_presets')->onDelete('cascade');

                    $table->uuid('puq_pm_lxc_os_template_uuid');
                    $table->foreign('puq_pm_lxc_os_template_uuid', 'fk_os_template_x_lot')
                        ->references('uuid')->on('puq_pm_lxc_os_templates')->onDelete('cascade');

                    $table->primary(['puq_pm_lxc_preset_uuid', 'puq_pm_lxc_os_template_uuid']);
                });
            }

            if (!Schema::hasTable('puq_pm_lxc_instances')) {
                Schema::create('puq_pm_lxc_instances', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('hostname');
                    $table->integer('vmid')->nullable();

                    $table->string('creating_upid')->nullable();

                    $table->uuid('puq_pm_lxc_preset_uuid');
                    $table->foreign('puq_pm_lxc_preset_uuid')->references('uuid')->on('puq_pm_lxc_presets')->onDelete('restrict');

                    $table->uuid('puq_pm_dns_zone_uuid');
                    $table->foreign('puq_pm_dns_zone_uuid')->references('uuid')->on('puq_pm_dns_zones')->onDelete('restrict');

                    $table->uuid('service_uuid');
                    $table->foreign('service_uuid')->references('uuid')->on('services')->onDelete('restrict');

                    $table->uuid('puq_pm_cluster_uuid');
                    $table->foreign('puq_pm_cluster_uuid')->references('uuid')->on('puq_pm_clusters')->onDelete('restrict');

                    $table->uuid('puq_pm_node_uuid')->nullable();
                    $table->foreign('puq_pm_node_uuid')->references('uuid')->on('puq_pm_nodes')->onDelete('set null');

                    $table->uuid('rootfs_puq_pm_storage_uuid')->nullable();
                    $table->foreign('rootfs_puq_pm_storage_uuid')->references('uuid')->on('puq_pm_storages')->onDelete('set null');

                    $table->uuid('mp_puq_pm_storage_uuid')->nullable();
                    $table->foreign('mp_puq_pm_storage_uuid')->references('uuid')->on('puq_pm_storages')->onDelete('set null');

                    $table->uuid('puq_pm_lxc_os_template_uuid')->nullable();
                    $table->foreign('puq_pm_lxc_os_template_uuid')->references('uuid')->on('puq_pm_lxc_os_templates')->onDelete('set null');

                    $table->uuid('backup_puq_pm_storage_uuid')->nullable();
                    $table->foreign('backup_puq_pm_storage_uuid')->references('uuid')->on('puq_pm_storages')->onDelete('set null');
                    $table->integer('backup_count')->default(0);

                    $table->integer('cores')->nullable();
                    $table->integer('memory')->nullable();
                    $table->integer('rootfs_size')->nullable();
                    $table->integer('mp_size')->nullable();

                    $table->longText('status')->nullable(); // json from Proxmox cluster/resources by vmid

                    $table->longText('backup_schedule')->nullable();
                    $table->string('backup_upid')->nullable();
                    $table->dateTime('last_backup_at')->nullable();

                    $table->string('firewall_policy_in')->nullable()->default('ACCEPT');
                    $table->string('firewall_policy_out')->nullable()->default('ACCEPT');
                    $table->longText('firewall_rules')->nullable(); // json

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_lxc_instance_nets')) {
                Schema::create('puq_pm_lxc_instance_nets', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name')->default('eth0');

                    $table->string('type')->default('public'); // public, local_private, global_private

                    $table->uuid('puq_pm_mac_pool_uuid');
                    $table->foreign('puq_pm_mac_pool_uuid')->references('uuid')->on('puq_pm_mac_pools')->onDelete('restrict');
                    $table->string('mac');

                    $table->uuid('puq_pm_ipv4_pool_uuid')->nullable();
                    $table->foreign('puq_pm_ipv4_pool_uuid')->references('uuid')->on('puq_pm_ip_pools')->onDelete('restrict');
                    $table->string('ipv4')->nullable();
                    $table->string('rdns_v4')->nullable(); // public only
                    $table->integer('mask_v4')->nullable(); // private only

                    $table->uuid('puq_pm_ipv6_pool_uuid')->nullable();
                    $table->foreign('puq_pm_ipv6_pool_uuid')->references('uuid')->on('puq_pm_ip_pools')->onDelete('restrict');
                    $table->string('ipv6')->nullable();
                    $table->string('rdns_v6')->nullable(); // public only
                    $table->integer('mask_v6')->nullable(); // private only

                    $table->uuid('puq_pm_lxc_instance_uuid');
                    $table->foreign('puq_pm_lxc_instance_uuid')->references('uuid')->on('puq_pm_lxc_instances')->onDelete('restrict');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_dns_servers')) {
                Schema::create('puq_pm_dns_servers', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('name');
                    $table->string('type'); // PowerDNS, Bind, Hestia, etc
                    $table->longText('config');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_dns_server_x_dns_zone')) {
                Schema::create('puq_pm_dns_server_x_dns_zone', function (Blueprint $table) {

                    $table->uuid('puq_pm_dns_zone_uuid');
                    $table->foreign('puq_pm_dns_zone_uuid')->references('uuid')->on('puq_pm_dns_zones')->onDelete('cascade');

                    $table->uuid('puq_pm_dns_server_uuid');
                    $table->foreign('puq_pm_dns_server_uuid')->references('uuid')->on('puq_pm_dns_servers')->onDelete('cascade');
                });
            }

            if (!Schema::hasTable('puq_pm_ssh_public_keys')) {
                Schema::create('puq_pm_ssh_public_keys', function (Blueprint $table) {

                    $table->uuid()->primary();
                    $table->uuid('client_uuid');
                    $table->foreign('client_uuid')->references('uuid')->on('clients')->onDelete('cascade');

                    $table->string('name');
                    $table->longText('public_key');

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('puq_pm_client_private_networks')) {
                Schema::create('puq_pm_client_private_networks', function (Blueprint $table) {

                    $table->uuid()->primary();
                    $table->string('name');
                    $table->string('type');// local_private, global_private

                    $table->uuid('puq_pm_cluster_group_uuid')->nullable();
                    $table->foreign('puq_pm_cluster_group_uuid')->references('uuid')->on('puq_pm_cluster_groups')->onDelete('set null');

                    $table->uuid('client_uuid');
                    $table->foreign('client_uuid')->references('uuid')->on('clients')->onDelete('cascade');

                    $table->string('bridge');
                    $table->integer('vlan_tag');

                    $table->string('ipv4_network')->default('172.16.0.0/16');
                    $table->timestamps();
                });
            }

            // Fill in the settings ---------------------------------------------------------------------
            SettingService::set('Product.puqProxmox.global_private_network', '10.0.100.0/24');
            //-------------------------------------------------------------------------------------------

            if (!Schema::hasTable('puq_pm_scripts')) {
                Schema::create('puq_pm_scripts', function (Blueprint $table) {

                    $table->uuid()->primary();

                    $table->string('type');

                    $table->longText('script')->nullable();

                    $table->uuid('puq_pm_lxc_os_template_uuid')->nullable();
                    $table->foreign('puq_pm_lxc_os_template_uuid')->references('uuid')->on('puq_pm_lxc_os_templates')->onDelete('set null');

                    $table->timestamps();
                });
            }

            $this->logInfo('activate', 'Success');

            return 'success';
        } catch (QueryException $e) {
            $this->logError('activate', 'Error activating plugin: '.$e->getMessage());

            return 'Error activating plugin: '.$e->getMessage();
        } catch (\Exception $e) {
            $this->logError('activate', 'Unexpected error activating plugin: '.$e->getMessage());

            return 'Unexpected error activating plugin: '.$e->getMessage();
        }
    }

    public function deactivate(): string
    {
        try {
            $tables = [
                'puq_pm_dns_server_x_dns_zone',
                'puq_pm_dns_servers',
                'puq_pm_lxc_instance_nets',
                'puq_pm_lxc_instances',
                'puq_pm_lxc_preset_x_lxc_os_templates',
                'puq_pm_lxc_os_templates',
                'puq_pm_lxc_templates',
                'puq_pm_lxc_preset_cluster_group_x_tag',
                'puq_pm_lxc_preset_cluster_groups',
                'puq_pm_lxc_presets',
                'puq_pm_dns_zones',
                'puq_pm_private_network_x_tag',
                'puq_pm_private_networks',
                'puq_pm_public_network_x_tag',
                'puq_pm_public_networks',
                'puq_pm_storage_x_tag',
                'puq_pm_storages',
                'puq_pm_node_x_tag',
                'puq_pm_tags',
                'puq_pm_nodes',
                'puq_pm_mac_pools',
                'puq_pm_ip_pools',
                'puq_pm_access_servers',
                'puq_pm_cluster_uuid',
                'puq_pm_clusters',
                'puq_pm_cluster_groups',
            ];
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    Schema::drop($table);
                }
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->logInfo('deactivate', 'Success');

            return 'success';
        } catch (QueryException $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->logError('deactivate', 'Error deactivating plugin: '.$e->getMessage());

            return 'Error deactivating plugin: '.$e->getMessage();
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->logError('deactivate', 'Unexpected error deactivating plugin: '.$e->getMessage());

            return 'Unexpected error deactivating plugin: '.$e->getMessage();
        }
    }

    public function update(): string
    {
        Schema::table('puq_pm_lxc_instances', function (Blueprint $table) {
            $table->string('firewall_policy_in')->nullable()->default('ACCEPT');
            $table->string('firewall_policy_out')->nullable()->default('ACCEPT');
            $table->longText('firewall_rules')->nullable(); // json
        });

        Schema::table('puq_pm_lxc_presets', function (Blueprint $table) {
            $table->boolean('firewall_enable')->default(true);
            $table->boolean('firewall_dhcp')->default(false);

            $table->boolean('firewall_ipfilter')->default(true);
            $table->boolean('firewall_macfilter')->default(true);

            $table->string('firewall_log_level_in')->default('nolog');
            $table->string('firewall_log_level_out')->default('nolog');
            $table->string('firewall_policy_in')->default('ACCEPT');
            $table->string('firewall_policy_out')->default('ACCEPT');

            $table->boolean('firewall_ndp')->default(false);
            $table->boolean('firewall_radv')->default(false);
        });
        $this->activate();

        return 'success';
    }

    public function getProductPage(): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;

        $product = \App\Models\Product::find($this->product_uuid);
        $data['product'] = $product;

        $product_attribute_groups = \App\Models\ProductAttributeGroup::query()->get();
        $data['product_attribute_groups'] = $product_attribute_groups;

        $data['config'] = $this->config;
        $type = $this->product_data['type'] ?? 'lxc';
        $data['type'] = $type;

        if ($type == 'lxc') {
            $puq_pm_lxc_preset_data = [];

            if (!empty($this->product_data['puq_pm_lxc_preset_uuid'])) {
                $puq_pm_lxc_preset = PuqPmLxcPreset::find($this->product_data['puq_pm_lxc_preset_uuid']);
                if ($puq_pm_lxc_preset) {
                    $puq_pm_lxc_preset_data = [
                        'id' => $puq_pm_lxc_preset->uuid,
                        'text' => $puq_pm_lxc_preset->name,
                    ];
                }
            }
            $data['puq_pm_lxc_preset_data'] = json_encode($puq_pm_lxc_preset_data);
            $data['puq_pm_lxc_preset'] = $puq_pm_lxc_preset ?? null;

            $location_group = $product->productOptionGroups()
                ->find($this->product_data['location_product_option_group_uuid'] ?? '');

            $data['location_option_mapping'] = $location_group
                ? $puq_pm_lxc_preset->getLocationOptionMappings($location_group)
                : [];

            $os_group = $product->productOptionGroups()
                ->find($this->product_data['os_product_option_group_uuid'] ?? '');

            $data['os_option_mapping'] = $os_group
                ? $puq_pm_lxc_preset->getOsOptionMappings($os_group)
                : [];

            $cpu_product_attributes = $product->productAttributes()->where('product_attribute_group_uuid',
                $this->product_data['cpu_product_attribute_group_uuid'] ?? '')->get();
            $data['cpu_product_attributes'] = $cpu_product_attributes;

            $memory_product_attributes = $product->productAttributes()->where(
                'product_attribute_group_uuid',
                $this->product_data['memory_product_attribute_group_uuid'] ?? ''
            )->get();
            $data['memory_product_attributes'] = $memory_product_attributes;

            $rootfs_product_attributes = $product->productAttributes()->where(
                'product_attribute_group_uuid',
                $this->product_data['rootfs_product_attribute_group_uuid'] ?? ''
            )->get();
            $data['rootfs_product_attributes'] = $rootfs_product_attributes;

            $mp_product_attributes = $product->productAttributes()->where(
                'product_attribute_group_uuid',
                $this->product_data['mp_product_attribute_group_uuid'] ?? ''
            )->get();
            $data['mp_product_attributes'] = $mp_product_attributes;

            $public_network_product_attributes = $product->productAttributes()->where(
                'product_attribute_group_uuid',
                $this->product_data['public_network_product_attribute_group_uuid'] ?? ''
            )->get();
            $data['public_network_product_attributes'] = $public_network_product_attributes;


            return $this->view('admin_area.product.lxc', $data);
        }

        if ($type == 'app') {
            return $this->view('admin_area.product.app', $data);
        }

        if ($type == 'vps') {
            return $this->view('admin_area.product.vps', $data);
        }

        return $this->view('admin_area.product.error', $data);
    }

    public function getProductData(array $data = []): array
    {
        $type = $data['type'] ?? 'lxc';

        if ($type == 'lxc') {
            $this->product_data = [
                'type' => 'lxc',
                'puq_pm_lxc_preset_uuid' => $data['puq_pm_lxc_preset_uuid'] ?? null,

                'os_product_option_group_uuid' => $data['os_product_option_group_uuid'] ?? null,
                'location_product_option_group_uuid' => $data['location_product_option_group_uuid'] ?? null,

                'ipv4_product_option_group_uuid' => $data['ipv4_product_option_group_uuid'] ?? null,
                'ipv6_product_option_group_uuid' => $data['ipv6_product_option_group_uuid'] ?? null,
                'local_private_network_product_option_group_uuid' => $data['local_private_network_product_option_group_uuid'] ?? null,
                'global_private_network_product_option_group_uuid' => $data['global_private_network_product_option_group_uuid'] ?? null,

                'cpu_cores_product_option_group_uuid' => $data['cpu_cores_product_option_group_uuid'] ?? null,
                'memory_product_option_group_uuid' => $data['memory_product_option_group_uuid'] ?? null,
                'rootfs_size_product_option_group_uuid' => $data['rootfs_size_product_option_group_uuid'] ?? null,
                'mp_size_product_option_group_uuid' => $data['mp_size_product_option_group_uuid'] ?? null,
                'backup_count_product_option_group_uuid' => $data['backup_count_product_option_group_uuid'] ?? null,

                'cpu_product_attribute_group_uuid' => $data['cpu_product_attribute_group_uuid'] ?? null,
                'memory_product_attribute_group_uuid' => $data['memory_product_attribute_group_uuid'] ?? null,
                'rootfs_product_attribute_group_uuid' => $data['rootfs_product_attribute_group_uuid'] ?? null,
                'mp_product_attribute_group_uuid' => $data['mp_product_attribute_group_uuid'] ?? null,
                'public_network_product_attribute_group_uuid' => $data['public_network_product_attribute_group_uuid'] ?? null,

            ];
        }

        if ($type == 'app') {
            $this->product_data = [
                'type' => 'app',
            ];
        }

        if ($type == 'vps') {
            $this->product_data = [
                'type' => 'vps',
            ];
        }

        return $this->product_data;
    }

    public function saveProductData(array $data = []): array
    {
        if (!empty($data['type_new']) && $data['type_new'] == $data['type']) {

            return [
                'status' => 'success',
                'data' => $this->getProductData(['type' => $data['type_new']]),
                'code' => 200,
            ];
        }

        if ($data['type'] == 'lxc') {
            $validator = Validator::make($data, [
                'puq_pm_lxc_preset_uuid' => 'required|exists:puq_pm_lxc_presets,uuid',
                'os_product_option_group_uuid' => 'required',
                'location_product_option_group_uuid' => 'required',
            ], [
                'puq_pm_lxc_preset_uuid.required' => __('Product.puqProxmox.The LXC Preset field is required'),
                'puq_pm_lxc_preset_uuid.exists' => __('Product.puqProxmox.Selected LXC Preset does not exist'),
                'os_product_option_group_uuid.required' => __('Product.puqProxmox.The OS Option Group field is required'),
                'location_product_option_group_uuid.required' => __('Product.puqProxmox.The Location Option Group field is required'),
            ]);

            if ($validator->fails()) {
                return [
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'code' => 422,
                ];
            }
        }

        return [
            'status' => 'success',
            'data' => $this->getProductData($data),
            'code' => 200,
        ];
    }

    public function getServiceData(array $data = []): array
    {
        $type = $this->product_data['type'] ?? 'lxc';
        if ($type == 'lxc') {
            $this->service_data = [
                'puq_pm_ssh_public_key_uuid' => $data['puq_pm_ssh_public_key_uuid'] ?? '',
                'root_password' => $data['root_password'] ?? '',
                'username' => $data['username'] ?? '',
                'password' => $data['password'] ?? '',
                'show_password_once' => $data['show_password_once'] ?? false,
                'backup_storage_name' => $data['backup_storage_name'] ?? '',
            ];
        }

        return $this->service_data;
    }

    public function getServicePage(): string
    {

        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;
        $service = Service::find($this->service_uuid);
        $data['service'] = $service;
        $data['config'] = $this->config;

        $type = $this->service_data['type'] ?? 'lxc';
        if ($type == 'lxc') {
            $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $this->service_uuid)->first();
            if ($lxc_instance) {
                $puq_pm_ssh_public_keys = PuqPmSshPublicKey::query()->where('client_uuid',
                    $service->client_uuid)->get();
            }

            if (!$lxc_instance) {
                return $this->view('admin_area.service.lxc_instance_not_found', []);
            }

            $data['lxc_instance'] = $lxc_instance;
            $data['lxc_instance_status'] = $lxc_instance->getStatus();
            $data['lxc_instance_info'] = $lxc_instance->getInfo();
            $data['lxc_instance_location'] = $lxc_instance->getLocation();

            $data['puq_pm_ssh_public_keys'] = $puq_pm_ssh_public_keys ?? [];

            $data['puq_pm_ssh_public_key_uuid'] = $this->service_data['puq_pm_ssh_public_key_uuid'] ?? '';
            $data['root_password'] = $this->service_data['root_password'] ?? '';
            $data['username'] = $this->service_data['username'] ?? '';
            $data['password'] = $this->service_data['password'] ?? '';
            $data['show_password_once'] = $this->service_data['show_password_once'] ?? false;
            $data['backup_storage_name'] = $this->service_data['backup_storage_name'] ?? '';

            $data['backup_storages'] = [];
            if (!empty($lxc_instance->vmid)) {
                $data['backup_storages'] = $lxc_instance->getAvailableBackupStorages() ?? []; //?????????????????????????? optional list all storages
            }

            return $this->view('admin_area.service.lxc', $data);
        }

        if ($type == 'app') {
            return $this->view('admin_area.service.app', $data);
        }

        if ($type == 'vps') {
            return $this->view('admin_area.service.vps', $data);
        }

        return $this->view('admin_area.service.error', $data);
    }

    public function saveServiceData(array $data = []): array
    {
        $service = \App\Models\Service::find(request()->route('uuid'));
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return [
                'status' => 'success',
                'data' => $this->getServiceData($data),
                'code' => 200,
            ];
        }

        $type = $this->service_data['type'] ?? 'lxc';

        if ($type == 'lxc') {

            $validator = Validator::make($data, [
                'puq_pm_ssh_public_key_uuid' => ['nullable', 'string', 'exists:puq_pm_ssh_public_keys,uuid'],
                'root_password' => ['required', 'string', 'min:8'],
                'username' => ['required', 'string', 'alpha_dash'],
                'password' => ['required', 'string', 'min:8'],
            ], [
                'puq_pm_ssh_public_key_uuid.exists' => __('Product.puqProxmox.Selected SSH public key does not exist'),
                'root_password.required' => __('Product.puqProxmox.Root password is required'),
                'root_password.min' => __('Product.puqProxmox.Root password must be at least 8 characters'),
                'username.required' => __('Product.puqProxmox.Username is required'),
                'username.alpha_dash' => __('Product.puqProxmox.Username can only contain letters, numbers, dashes and underscores'),
                'password.required' => __('Product.puqProxmox.Password is required'),
                'password.min' => __('Product.puqProxmox.Password must be at least 8 characters'),
            ]);

            if ($data['show_password_once'] and $data['show_password_once'] == 'no') {
                $data['show_password_once'] = false;
            } else {
                $data['show_password_once'] = true;
            }

            if ($validator->fails()) {
                return [
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'code' => 422,
                ];
            }
        }


        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $this->service_uuid)->first();
        if ($lxc_instance) {
            $backup_storages = $lxc_instance->getAvailableBackupStorages();
            foreach ($backup_storages as $backup_storage) {
                if ($backup_storage->name == $data['backup_storage_name']) {
                    $lxc_instance->setBackupStorageName($backup_storage);
                    break;
                }
            }
        }

        return [
            'status' => 'success',
            'data' => $this->getServiceData($data),
            'code' => 200,
        ];

    }

    //--------------------------------------------------------------
    public function create(): array
    {
        $data = [
            'module' => $this,
            'method' => '',        // The method name that should be executed inside the job
            'callback' => 'createCallback',
            // Optional. The method name in the module that will be executed after the main method is finished.
            // Receives the result and jobId as parameters.
            'tries' => 5,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $type = $this->product_data['type'] ?? 'lxc';

        if ($type == 'lxc') {
            $data['method'] = 'createLxcJob';
        }

        $tags = [
            'create',
        ];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function createLxcJob(): array
    {
        $log_request = [
            'step' => 'Load Service, Product, PuqPmLxcPreset, PuqPmLxcInstance',
            'service_uuid' => $this->service_uuid,
            'product_uuid' => $this->product_uuid,
            'product_data' => $this->product_data,
        ];

        $log_response = [];

        try {
            $product = \App\Models\Product::find($this->product_uuid);
            $log_response['product'] = $product;

            $service = \App\Models\Service::find($this->service_uuid);
            $log_response['service'] = $service;

            $puq_pm_lxc_preset = PuqPmLxcPreset::find($this->product_data['puq_pm_lxc_preset_uuid']);
            $log_response['puq_pm_lxc_preset'] = $puq_pm_lxc_preset;

            $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
            $log_response['lxc_instance'] = $puq_pm_lxc_preset;

            $this->logDebug(__FUNCTION__, $log_request, $log_response);

            if (!empty($lxc_instance['vmid'])) {
                return [
                    'status' => 'error',
                    'errors' => ['LXC already exist'],
                ];
            }

            $create_lxc_instance = $puq_pm_lxc_preset->createLxcInstance($service, $this->product_data);
            $log_response['create_lxc_instance'] = $create_lxc_instance;
            $log_request['step'] = 'Create LXC Instance';
            $this->logDebug(__FUNCTION__, $log_request, $log_response);

            if ($create_lxc_instance['status'] == 'error') {
                return [
                    'status' => 'error',
                    'errors' => $create_lxc_instance['errors'],
                ];
            }

            $lxc_instance = $create_lxc_instance['data'];
            $create_lxc = $lxc_instance->createLxc();
            $log_response['create_lxc'] = $create_lxc;
            $log_request['step'] = 'Create LXC';
            $this->logDebug(__FUNCTION__, $log_request, $log_response);

            if ($create_lxc['status'] == 'error') {
                return $create_lxc;
            }

            $puq_pm_cluster = $lxc_instance->puqPmCluster;
            $lxc_instance->refresh();
            $crete_task = $puq_pm_cluster->waitForTask($lxc_instance->creating_upid, 100, 5);

            $log_response['crete_task'] = $crete_task;
            $log_request['step'] = 'Create LXC Wait Task';
            $this->logDebug(__FUNCTION__, $log_request, $log_response);

            if ($crete_task['status'] == 'error') {
                return $crete_task;
            }

            sleep(5);
            usleep(5000000);

            $post_install = $lxc_instance->postInstallLxc();
            $log_response['post_install'] = $post_install;
            $log_request['step'] = 'Post Install Script';
            $this->logDebug(__FUNCTION__, $log_request, $log_response);

            if ($post_install['status'] == 'error') {
                return $post_install;
            }

            sleep(6);
            usleep(5000000);

            $puq_pm_cluster->getClusterResources(true);

            $log_response['lxc_status'] = $lxc_instance->getStatus();
            $log_request['step'] = 'Get LXC Status';
            $this->logDebug(__FUNCTION__, $log_request, $log_response);

            $set_firewall_options = $lxc_instance->setFirewallOptions();

            $log_response['set_firewall_options'] = $set_firewall_options;
            $log_request['step'] = 'Set Firewall Options';
            $this->logDebug(__FUNCTION__, $log_request, $log_response);

            if ($set_firewall_options['status'] == 'error') {
                return $set_firewall_options;
            }

            return [
                'status' => 'success',
            ];
        } catch (\Throwable $e) {
            $this->logError(__FUNCTION__, '', $e->getMessage());
            $this->logDebug(__FUNCTION__,
                ['step' => 'exception', 'trace' => $e->getTraceAsString(), 'message' => $e->getMessage()]);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
                'trace' => $e->getTraceAsString(),
            ];
        }
    }

    //--------------------------------------------------------------
    public function suspend(): array
    {
        $suspend = ['status' => 'error', 'errors' => ['Type is not selected']];
        $type = $this->product_data['type'] ?? 'lxc';

        if ($type == 'lxc') {
            $suspend = $this->suspendLxcJob();
        }

        return $suspend;
    }

    public function suspendLxcJob(): array
    {
        try {
            $service = \App\Models\Service::find($this->service_uuid);
            $lxc_instance = PuqPmLxcInstance::query()
                ->where('service_uuid', $service->uuid)
                ->first();

            if (empty($lxc_instance) or empty($lxc_instance->vmid)) {
                $status = ['status' => 'error', 'errors' => ['LXC is not ready']];
                $this->suspendCallback($status);

                return $status;
            }

            $status = $lxc_instance->getStatus();
            if ($status['status'] != 'stopped') {
                $suspend = $lxc_instance->stop();

                if ($suspend['status'] == 'error') {
                    $this->suspendCallback($suspend);

                    return $suspend;
                }
            }

            $status = ['status' => 'success'];
            $this->suspendCallback($status);

            return $status;
        } catch (\Throwable $e) {
            $status = [
                'status' => 'error',
                'errors' => [$e->getMessage()],
                'trace' => $e->getTraceAsString(),
            ];
            $this->logError('Suspend', '', $e->getMessage());
            $this->suspendCallback($status);

            return $status;
        }
    }

    //--------------------------------------------------------------
    public function unsuspend(): array
    {
        $unsuspend = ['status' => 'error', 'errors' => ['Type is not selected']];
        $type = $this->product_data['type'] ?? 'lxc';

        if ($type == 'lxc') {
            $unsuspend = $this->unsuspendLxcJob();
        }

        return $unsuspend;
    }

    public function unsuspendLxcJob(): array
    {
        try {
            $service = \App\Models\Service::find($this->service_uuid);
            $lxc_instance = PuqPmLxcInstance::query()
                ->where('service_uuid', $service->uuid)
                ->first();

            if (empty($lxc_instance) or empty($lxc_instance->vmid)) {
                $status = ['status' => 'error', 'errors' => ['LXC is not ready']];
                $this->unsuspendCallback($status);

                return $status;
            }

            $unsuspend = $lxc_instance->start();

            if ($unsuspend['status'] == 'error') {
                $this->unsuspendCallback($unsuspend);

                return $unsuspend;
            }

            $status = ['status' => 'success'];
            $this->unsuspendCallback($status);

            return $status;
        } catch (\Throwable $e) {
            $status = [
                'status' => 'error',
                'errors' => [$e->getMessage()],
                'trace' => $e->getTraceAsString(),
            ];
            $this->logError('Suspend', '', $e->getMessage());
            $this->unsuspendCallback($status);

            return $status;
        }
    }

    //--------------------------------------------------------------
    public function termination(): array
    {

        $termination = ['status' => 'error', 'errors' => ['Type is not selected']];
        $type = $this->product_data['type'] ?? 'lxc';

        if ($type == 'lxc') {
            $termination = $this->terminationLxcJob();
        }

        return $termination;
    }

    public function terminationLxcJob(): array
    {
        try {
            $service = \App\Models\Service::find($this->service_uuid);
            $lxc_instance = PuqPmLxcInstance::query()
                ->where('service_uuid', $service->uuid)
                ->first();

            if (empty($lxc_instance) or empty($lxc_instance->vmid)) {
                $status = ['status' => 'error', 'errors' => ['LXC is not ready']];
                $this->terminationCallback($status);

                return $status;
            }

            $lxc_instance->deleteAllBackups();
            $delete_lxc = $lxc_instance->deleteLxc();

            if ($delete_lxc['status'] == 'error') {
                $this->terminationCallback($delete_lxc);

                return $delete_lxc;
            }

            $lxc_instance->puqPmLxcInstanceNets()->delete();
            $lxc_instance->delete();

            $service->setProvisionStatus('terminated');

            $status = ['status' => 'success'];
            $this->terminationCallback($status);

            return $status;
        } catch (\Throwable $e) {
            $status = [
                'status' => 'error',
                'errors' => [$e->getMessage()],
                'trace' => $e->getTraceAsString(),
            ];
            $this->logError('Termination', '', $e->getMessage());
            $this->terminationCallback($status);

            return $status;
        }
    }

    //--------------------------------------------------------------
    public function cancellation(): array
    {
        $termination = ['status' => 'error', 'errors' => ['Type is not selected']];
        $type = $this->product_data['type'] ?? 'lxc';

        if ($type == 'lxc') {
            $termination = $this->cancellationLxcJob();
        }

        return $termination;
    }

    public function cancellationLxcJob(): array
    {
        try {
            $service = \App\Models\Service::find($this->service_uuid);
            $lxc_instance = PuqPmLxcInstance::query()
                ->where('service_uuid', $service->uuid)
                ->first();

            if (!empty($lxc_instance) and !empty($lxc_instance->vmid)) {
                $lxc_instance->deleteAllBackups();
                $lxc_instance->deleteLxc();
            }

            if (!empty($lxc_instance)) {
                $lxc_instance->puqPmLxcInstanceNets()->delete();
                $lxc_instance->delete();
            }

            $service->setProvisionStatus('cancellated');
            $status = ['status' => 'success'];
            $this->cancellationCallback($status);

            return $status;

        } catch (\Throwable $e) {
            $this->logError('Cancellation', '', $e->getMessage());

            $status = [
                'status' => 'error',
                'errors' => [$e->getMessage()],
                'trace' => $e->getTraceAsString(),
            ];
            $this->cancellationCallback($status);

            return $status;
        }
    }

    //--------------------------------------------------------------
    public function change_package(): array
    {

        $data = [
            'module' => $this,
            'method' => '',        // The method name that should be executed inside the job
            'callback' => 'change_packageCallback',
            // Optional. The method name in the module that will be executed after the main method is finished.
            // Receives the result and jobId as parameters.
            'tries' => 5,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $type = $this->product_data['type'] ?? 'lxc';

        if ($type == 'lxc') {
            $data['method'] = 'change_packageLxcJob';
        }

        $tags = [
            'change_package',
        ];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('change_package');
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function change_packageLxcJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('change_package');

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return ['status' => 'error', 'errors' => ['LXC is not ready']];
        }

        return $lxc_instance->rescaleNow();
    }

    //------------------------------------------------------------------------------------------------------------------------
    public function adminPermissions(): array
    {
        return [
            [
                'name' => 'Configuration',
                'key' => 'configuration',
                'description' => 'Configuration',
            ],
        ];
    }

    public function adminSidebar(): array
    {
        return [
            [
                'title' => 'Clusters',
                'link' => 'clusters',
                'active_links' => ['clusters', 'cluster.tab'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'Cluster Groups',
                'link' => 'cluster_groups',
                'active_links' => ['cluster_groups', 'cluster_group.tab'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'IP pools',
                'link' => 'ip_pools',
                'active_links' => ['ip_pools', 'ip_pool'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'MAC pools',
                'link' => 'mac_pools',
                'active_links' => ['mac_pools', 'mac_pool'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'Tags',
                'link' => 'tags',
                'active_links' => ['tags'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'LXC Presets',
                'link' => 'lxc_presets',
                'active_links' => ['lxc_presets', 'lxc_preset.tab'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'LXC OS Templates',
                'link' => 'lxc_os_templates',
                'active_links' => ['lxc_os_templates', 'lxc_os_template.tab'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'LXC Templates',
                'link' => 'lxc_templates',
                'active_links' => ['lxc_templates'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'DNS zones',
                'link' => 'dns_zones',
                'active_links' => ['dns_zones', 'dns_zone'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'SSH Public Keys',
                'link' => 'ssh_public_keys',
                'active_links' => ['ssh_public_keys'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'Client Private Networks',
                'link' => 'client_private_networks',
                'active_links' => ['client_private_networks'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'Settings',
                'link' => 'settings',
                'active_links' => ['settings'],
                'permission' => 'configuration',
            ],

        ];
    }

    public function adminWebRoutes(): array
    {
        return [
            // Cluster
            [
                'method' => 'get',
                'uri' => 'clusters',
                'permission' => 'configuration',
                'name' => 'clusters',
                'controller' => 'puqPmClusterController@clusters',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster/{uuid}/{tab}',
                'permission' => 'configuration',
                'name' => 'cluster.tab',
                'controller' => 'puqPmClusterController@clusterTab',
            ],

            // Cluster Group
            [
                'method' => 'get',
                'uri' => 'cluster_groups',
                'permission' => 'configuration',
                'name' => 'cluster_groups',
                'controller' => 'puqPmClusterGroupController@clusterGroups',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster_group/{uuid}/{tab}',
                'permission' => 'configuration',
                'name' => 'cluster_group.tab',
                'controller' => 'puqPmClusterGroupController@clusterGroupTab',
            ],

            // IP Pool
            [
                'method' => 'get',
                'uri' => 'ip_pools',
                'permission' => 'configuration',
                'name' => 'ip_pools',
                'controller' => 'puqPmIpPoolController@ipPools',
            ],
            [
                'method' => 'get',
                'uri' => 'ip_pool/{uuid}',
                'permission' => 'configuration',
                'name' => 'ip_pool',
                'controller' => 'puqPmIpPoolController@ipPool',
            ],

            // MAC Pool
            [
                'method' => 'get',
                'uri' => 'mac_pools',
                'permission' => 'configuration',
                'name' => 'mac_pools',
                'controller' => 'puqPmMacPoolController@macPools',
            ],
            [
                'method' => 'get',
                'uri' => 'mac_pool/{uuid}',
                'permission' => 'configuration',
                'name' => 'mac_pool',
                'controller' => 'puqPmMacPoolController@macPool',
            ],

            // Tags
            [
                'method' => 'get',
                'uri' => 'tags',
                'permission' => 'configuration',
                'name' => 'tags',
                'controller' => 'puqPmTagController@tags',
            ],

            // LXC Presets
            [
                'method' => 'get',
                'uri' => 'lxc_presets',
                'permission' => 'configuration',
                'name' => 'lxc_presets',
                'controller' => 'puqPmLxcPresetController@lxcPresets',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_preset/{uuid}/{tab}',
                'permission' => 'configuration',
                'name' => 'lxc_preset.tab',
                'controller' => 'puqPmLxcPresetController@lxcPresetTab',
            ],

            // LXC Templates
            [
                'method' => 'get',
                'uri' => 'lxc_templates',
                'permission' => 'configuration',
                'name' => 'lxc_templates',
                'controller' => 'puqPmLxcTemplateController@lxcTemplates',
            ],

            // LXC OS Templates
            [
                'method' => 'get',
                'uri' => 'lxc_os_templates',
                'permission' => 'configuration',
                'name' => 'lxc_os_templates',
                'controller' => 'puqPmLxcOsTemplateController@lxcOsTemplates',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_os_template/{uuid}/{tab}',
                'permission' => 'configuration',
                'name' => 'lxc_os_template.tab',
                'controller' => 'puqPmLxcOsTemplateController@lxcOsTemplateTab',
            ],

            // DNS Zones
            [
                'method' => 'get',
                'uri' => 'dns_zones',
                'permission' => 'configuration',
                'name' => 'dns_zones',
                'controller' => 'puqPmDnsZoneController@dnsZones',
            ],
            [
                'method' => 'get',
                'uri' => 'dns_zone/{uuid}',
                'permission' => 'configuration',
                'name' => 'dns_zone',
                'controller' => 'puqPmDnsZoneController@dnsZone',
            ],

            // SSH Public Keys
            [
                'method' => 'get',
                'uri' => 'ssh_public_keys',
                'permission' => 'configuration',
                'name' => 'ssh_public_keys',
                'controller' => 'puqPmSshPublicKeyController@sshPublicKeys',
            ],

            // Client Private Network
            [
                'method' => 'get',
                'uri' => 'client_private_networks',
                'permission' => 'configuration',
                'name' => 'client_private_networks',
                'controller' => 'puqPmClientPrivateNetworkController@clientPrivateNetworks',
            ],

            // Settings
            [
                'method' => 'get',
                'uri' => 'settings',
                'permission' => 'configuration',
                'name' => 'settings',
                'controller' => 'puqPmController@settings',
            ],

        ];
    }

    public function adminApiRoutes(): array
    {
        return [

            // Clusters
            [
                'method' => 'get',
                'uri' => 'clusters',
                'permission' => 'configuration',
                'name' => 'clusters.get',
                'controller' => 'puqPmClusterController@getClusters',
            ],
            [
                'method' => 'post',
                'uri' => 'cluster',
                'permission' => 'configuration',
                'name' => 'cluster.post',
                'controller' => 'puqPmClusterController@postCluster',
            ],
            [
                'method' => 'delete',
                'uri' => 'cluster/{uuid}',
                'permission' => 'configuration',
                'name' => 'cluster.delete',
                'controller' => 'puqPmClusterController@deleteCluster',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster/{uuid}/access_servers',
                'permission' => 'configuration',
                'name' => 'cluster.access_servers.get',
                'controller' => 'puqPmClusterController@getClusterAccessServers',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster/{uuid}/nodes',
                'permission' => 'configuration',
                'name' => 'cluster.nodes.get',
                'controller' => 'puqPmClusterController@getClusterNodes',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster/{uuid}/storages',
                'permission' => 'configuration',
                'name' => 'cluster.storages.get',
                'controller' => 'puqPmClusterController@getClusterStorages',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster/{uuid}/public_networks',
                'permission' => 'configuration',
                'name' => 'cluster.public_networks.get',
                'controller' => 'puqPmClusterController@getClusterPublicNetworks',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster/{uuid}/private_networks',
                'permission' => 'configuration',
                'name' => 'cluster.private_networks.get',
                'controller' => 'puqPmClusterController@getClusterPrivateNetworks',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster/{uuid}',
                'permission' => 'configuration',
                'name' => 'cluster.get',
                'controller' => 'puqPmClusterController@getCluster',
            ],
            [
                'method' => 'put',
                'uri' => 'cluster/{uuid}',
                'permission' => 'configuration',
                'name' => 'cluster.put',
                'controller' => 'puqPmClusterController@putCluster',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster/{uuid}/vncwebproxy_test_connection',
                'permission' => 'configuration',
                'name' => 'cluster.vncwebproxy_test_connection.get',
                'controller' => 'puqPmClusterController@getClusterVncwebproxyTestConnection',
            ],


            // Cluster Sync
            [
                'method' => 'get',
                'uri' => 'sync/cluster/{uuid}/info',
                'permission' => 'configuration',
                'name' => 'sync.cluster.info.get',
                'controller' => 'puqPmClusterController@getSyncClusterInfo',
            ],
            [
                'method' => 'get',
                'uri' => 'sync/cluster/{uuid}/nodes',
                'permission' => 'configuration',
                'name' => 'sync.cluster.nodes.get',
                'controller' => 'puqPmClusterController@getSyncClusterNodes',
            ],
            [
                'method' => 'get',
                'uri' => 'sync/cluster/{uuid}/storages',
                'permission' => 'configuration',
                'name' => 'sync.cluster.storages.get',
                'controller' => 'puqPmClusterController@getSyncClusterStorages',
            ],
            [
                'method' => 'get',
                'uri' => 'sync/cluster/{uuid}/storages/sync_templates',
                'permission' => 'configuration',
                'name' => 'sync.cluster.storages.sync_templates.get',
                'controller' => 'puqPmClusterController@getSyncClusterStoragesSyncTemplates',
            ],

            // Access Servers
            [
                'method' => 'post',
                'uri' => 'access_server',
                'permission' => 'configuration',
                'name' => 'access_server.post',
                'controller' => 'puqPmClusterController@postAccessServer',
            ],
            [
                'method' => 'delete',
                'uri' => 'access_server/{uuid}',
                'permission' => 'configuration',
                'name' => 'access_server.delete',
                'controller' => 'puqPmClusterController@deleteAccessServer',
            ],
            [
                'method' => 'get',
                'uri' => 'access_server/{uuid}/test_connection',
                'permission' => 'configuration',
                'name' => 'access_server.test_connection.get',
                'controller' => 'puqPmClusterController@getAccessServerTestConnection',
            ],

            // Public Networks
            [
                'method' => 'post',
                'uri' => 'public_network',
                'permission' => 'configuration',
                'name' => 'public_network.post',
                'controller' => 'puqPmClusterController@postPublicNetwork',
            ],
            [
                'method' => 'get',
                'uri' => 'public_network/{uuid}',
                'permission' => 'configuration',
                'name' => 'public_network.get',
                'controller' => 'puqPmClusterController@getPublicNetwork',
            ],
            [
                'method' => 'put',
                'uri' => 'public_network/{uuid}',
                'permission' => 'configuration',
                'name' => 'public_network.put',
                'controller' => 'puqPmClusterController@putPublicNetwork',
            ],
            [
                'method' => 'delete',
                'uri' => 'public_network/{uuid}',
                'permission' => 'configuration',
                'name' => 'public_network.delete',
                'controller' => 'puqPmClusterController@deletePublicNetwork',
            ],

            // Private Networks
            [
                'method' => 'post',
                'uri' => 'private_network',
                'permission' => 'configuration',
                'name' => 'private_network.post',
                'controller' => 'puqPmClusterController@postPrivateNetwork',
            ],
            [
                'method' => 'get',
                'uri' => 'private_network/{uuid}',
                'permission' => 'configuration',
                'name' => 'private_network.get',
                'controller' => 'puqPmClusterController@getPrivateNetwork',
            ],
            [
                'method' => 'put',
                'uri' => 'private_network/{uuid}',
                'permission' => 'configuration',
                'name' => 'private_network.put',
                'controller' => 'puqPmClusterController@putPrivateNetwork',
            ],
            [
                'method' => 'delete',
                'uri' => 'private_network/{uuid}',
                'permission' => 'configuration',
                'name' => 'private_network.delete',
                'controller' => 'puqPmClusterController@deletePrivateNetwork',
            ],

            // Cluster Groups
            [
                'method' => 'get',
                'uri' => 'cluster_groups',
                'permission' => 'configuration',
                'name' => 'cluster_groups.get',
                'controller' => 'puqPmClusterGroupController@getClusterGroups',
            ],
            [
                'method' => 'post',
                'uri' => 'cluster_group',
                'permission' => 'configuration',
                'name' => 'cluster_group.post',
                'controller' => 'puqPmClusterGroupController@postClusterGroup',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'cluster_group.get',
                'controller' => 'puqPmClusterGroupController@getClusterGroup',
            ],
            [
                'method' => 'put',
                'uri' => 'cluster_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'cluster_group.put',
                'controller' => 'puqPmClusterGroupController@putClusterGroup',
            ],
            [
                'method' => 'delete',
                'uri' => 'cluster_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'cluster_group.delete',
                'controller' => 'puqPmClusterGroupController@deleteClusterGroup',
            ],
            [
                'method' => 'get',
                'uri' => 'cluster_groups/select',
                'permission' => 'configuration',
                'name' => 'cluster_groups.select.get',
                'controller' => 'puqPmClusterGroupController@getClusterGroupsSelect',
            ],

            // IP Pools
            [
                'method' => 'get',
                'uri' => 'ip_pools',
                'permission' => 'configuration',
                'name' => 'ip_pools.get',
                'controller' => 'puqPmIpPoolController@getIpPools',
            ],
            [
                'method' => 'post',
                'uri' => 'ip_pool',
                'permission' => 'configuration',
                'name' => 'ip_pool.post',
                'controller' => 'puqPmIpPoolController@postIpPool',
            ],
            [
                'method' => 'get',
                'uri' => 'ip_pool/{uuid}',
                'permission' => 'configuration',
                'name' => 'ip_pool.get',
                'controller' => 'puqPmIpPoolController@getIpPool',
            ],
            [
                'method' => 'put',
                'uri' => 'ip_pool/{uuid}',
                'permission' => 'configuration',
                'name' => 'ip_pool.put',
                'controller' => 'puqPmIpPoolController@putIpPool',
            ],
            [
                'method' => 'delete',
                'uri' => 'ip_pool/{uuid}',
                'permission' => 'configuration',
                'name' => 'ip_pool.delete',
                'controller' => 'puqPmIpPoolController@deleteIpPool',
            ],
            [
                'method' => 'get',
                'uri' => 'ip_pools/select',
                'permission' => 'configuration',
                'name' => 'ip_pools.select.get',
                'controller' => 'puqPmIpPoolController@getIpPoolsSelect',
            ],
            [
                'method' => 'get',
                'uri' => 'ip_pool/{uuid}/used_ips',
                'permission' => 'configuration',
                'name' => 'ip_pool.used_ips.get',
                'controller' => 'puqPmIpPoolController@getUsedIps',
            ],

            // MAC Pools
            [
                'method' => 'get',
                'uri' => 'mac_pools',
                'permission' => 'configuration',
                'name' => 'mac_pools.get',
                'controller' => 'puqPmMacPoolController@getMacPools',
            ],
            [
                'method' => 'post',
                'uri' => 'mac_pool',
                'permission' => 'configuration',
                'name' => 'mac_pool.post',
                'controller' => 'puqPmMacPoolController@postMacPool',
            ],
            [
                'method' => 'get',
                'uri' => 'mac_pool/{uuid}',
                'permission' => 'configuration',
                'name' => 'mac_pool.get',
                'controller' => 'puqPmMacPoolController@getMacPool',
            ],
            [
                'method' => 'put',
                'uri' => 'mac_pool/{uuid}',
                'permission' => 'configuration',
                'name' => 'mac_pool.put',
                'controller' => 'puqPmMacPoolController@putMacPool',
            ],
            [
                'method' => 'delete',
                'uri' => 'mac_pool/{uuid}',
                'permission' => 'configuration',
                'name' => 'mac_pool.delete',
                'controller' => 'puqPmMacPoolController@deleteMacPool',
            ],
            [
                'method' => 'get',
                'uri' => 'mac_pools/select',
                'permission' => 'configuration',
                'name' => 'mac_pools.select.get',
                'controller' => 'puqPmMacPoolController@getMacPoolsSelect',
            ],
            [
                'method' => 'get',
                'uri' => 'mac_pool/{uuid}/used_macs',
                'permission' => 'configuration',
                'name' => 'mac_pool.used_macs.get',
                'controller' => 'puqPmMacPoolController@getUsedMacs',
            ],

            // Nodes
            [
                'method' => 'delete',
                'uri' => 'node/{uuid}',
                'permission' => 'configuration',
                'name' => 'node.delete',
                'controller' => 'puqPmClusterController@deleteNode',
            ],

            // Storages
            [
                'method' => 'delete',
                'uri' => 'storage/{uuid}',
                'permission' => 'configuration',
                'name' => 'storage.delete',
                'controller' => 'puqPmClusterController@deleteStorage',
            ],

            // Tags
            [
                'method' => 'get',
                'uri' => 'tags',
                'permission' => 'configuration',
                'name' => 'tags.get',
                'controller' => 'puqPmTagController@getTags',
            ],
            [
                'method' => 'post',
                'uri' => 'tag',
                'permission' => 'configuration',
                'name' => 'tag.post',
                'controller' => 'puqPmTagController@postTag',
            ],
            [
                'method' => 'get',
                'uri' => 'tag/{uuid}',
                'permission' => 'configuration',
                'name' => 'tag.get',
                'controller' => 'puqPmTagController@getTag',
            ],
            [
                'method' => 'put',
                'uri' => 'tag/{uuid}',
                'permission' => 'configuration',
                'name' => 'tag.put',
                'controller' => 'puqPmTagController@putTag',
            ],
            [
                'method' => 'delete',
                'uri' => 'tag/{uuid}',
                'permission' => 'configuration',
                'name' => 'tag.delete',
                'controller' => 'puqPmTagController@deleteTag',
            ],
            [
                'method' => 'get',
                'uri' => 'tag-editor/tags/search',
                'permission' => 'configuration',
                'name' => 'tag-editor.tags.search.get',
                'controller' => 'puqPmTagController@getTagEditorSearchTags',
            ],
            [
                'method' => 'post',
                'uri' => 'tag-editor/tags/update',
                'permission' => 'configuration',
                'name' => 'tag-editor.tags.update.get',
                'controller' => 'puqPmTagController@getTagEditorUpdateTags',
            ],

            // LXC Presets
            [
                'method' => 'get',
                'uri' => 'lxc_presets',
                'permission' => 'configuration',
                'name' => 'lxc_presets.get',
                'controller' => 'puqPmLxcPresetController@getLxcPresets',
            ],
            [
                'method' => 'post',
                'uri' => 'lxc_preset',
                'permission' => 'configuration',
                'name' => 'lxc_preset.post',
                'controller' => 'puqPmLxcPresetController@postLxcPreset',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_preset/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_preset.get',
                'controller' => 'puqPmLxcPresetController@getLxcPreset',
            ],
            [
                'method' => 'put',
                'uri' => 'lxc_preset/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_preset.put',
                'controller' => 'puqPmLxcPresetController@putLxcPreset',
            ],
            [
                'method' => 'delete',
                'uri' => 'lxc_preset/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_preset.delete',
                'controller' => 'puqPmLxcPresetController@deleteLxcPreset',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_preset/{uuid}/lxc_preset_cluster_groups',
                'permission' => 'configuration',
                'name' => 'lxc_preset.lxc_preset_cluster_groups.get',
                'controller' => 'puqPmLxcPresetController@getLxcPresetLxcPresetClusterGroups',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_preset/{uuid}/lxc_os_templates',
                'permission' => 'configuration',
                'name' => 'lxc_preset.lxc_os_templates.get',
                'controller' => 'puqPmLxcPresetController@getLxcPresetLxcPresetLxcOsTemplates',
            ],
            [
                'method' => 'post',
                'uri' => 'lxc_preset/{uuid}/lxc_os_template',
                'permission' => 'configuration',
                'name' => 'lxc_preset.lxc_os_template.post',
                'controller' => 'puqPmLxcPresetController@postLxcPresetLxcPresetLxcOsTemplate',
            ],
            [
                'method' => 'delete',
                'uri' => 'lxc_preset/{uuid}/lxc_os_template/{lxc_os_template_uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_preset.lxc_os_template.delete',
                'controller' => 'puqPmLxcPresetController@deleteLxcPresetLxcPresetLxcOsTemplate',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_presets/select',
                'permission' => 'configuration',
                'name' => 'lxc_presets.select.get',
                'controller' => 'puqPmLxcPresetController@getLxcPresetsSelect',
            ],


            // LXC Preset Cluster Groups
            [
                'method' => 'get',
                'uri' => 'lxc_preset_cluster_groups',
                'permission' => 'configuration',
                'name' => 'lxc_preset_cluster_groups.get',
                'controller' => 'puqPmLxcPresetController@getLxcPresetClusterGroups',
            ],
            [
                'method' => 'post',
                'uri' => 'lxc_preset_cluster_group',
                'permission' => 'configuration',
                'name' => 'lxc_preset_cluster_group.post',
                'controller' => 'puqPmLxcPresetController@postLxcPresetClusterGroups',
            ],
            [
                'method' => 'delete',
                'uri' => 'lxc_preset_cluster_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_preset_cluster_group.delete',
                'controller' => 'puqPmLxcPresetController@deleteLxcPresetClusterGroup',
            ],

            // LXC Templates
            [
                'method' => 'get',
                'uri' => 'lxc_templates',
                'permission' => 'configuration',
                'name' => 'lxc_templates.get',
                'controller' => 'puqPmLxcTemplateController@getLxcTemplates',
            ],
            [
                'method' => 'post',
                'uri' => 'lxc_template',
                'permission' => 'configuration',
                'name' => 'lxc_template.post',
                'controller' => 'puqPmLxcTemplateController@postLxcTemplate',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_template/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_template.get',
                'controller' => 'puqPmLxcTemplateController@getLxcTemplate',
            ],
            [
                'method' => 'put',
                'uri' => 'lxc_template/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_template.put',
                'controller' => 'puqPmLxcTemplateController@putLxcTemplate',
            ],
            [
                'method' => 'delete',
                'uri' => 'lxc_template/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_template.delete',
                'controller' => 'puqPmLxcTemplateController@deleteLxcTemplate',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_template/{uuid}/check_lxc_template_file',
                'permission' => 'configuration',
                'name' => 'lxc_template.check_lxc_template_file.get',
                'controller' => 'puqPmLxcTemplateController@checkLxcTemplateFile',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_templates/select',
                'permission' => 'configuration',
                'name' => 'lxc_templates.select.get',
                'controller' => 'puqPmLxcTemplateController@getLxcTemplatesSelect',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_templates/sync_templates',
                'permission' => 'configuration',
                'name' => 'lxc_templates.sync_templates.get',
                'controller' => 'puqPmLxcTemplateController@getLxcTemplatesSyncTemplates',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_templates/sync_delete_templates',
                'permission' => 'configuration',
                'name' => 'lxc_templates.sync_delete_templates.get',
                'controller' => 'puqPmLxcTemplateController@getLxcTemplatesSyncDeleteTemplates',
            ],
            // LXC OS Templates
            [
                'method' => 'get',
                'uri' => 'lxc_os_templates',
                'permission' => 'configuration',
                'name' => 'lxc_os_templates.get',
                'controller' => 'puqPmLxcOsTemplateController@getLxcOsTemplates',
            ],
            [
                'method' => 'post',
                'uri' => 'lxc_os_template',
                'permission' => 'configuration',
                'name' => 'lxc_os_template.post',
                'controller' => 'puqPmLxcOsTemplateController@postLxcOsTemplate',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_os_template/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_os_template.get',
                'controller' => 'puqPmLxcOsTemplateController@getLxcOsTemplate',
            ],
            [
                'method' => 'put',
                'uri' => 'lxc_os_template/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_os_template.put',
                'controller' => 'puqPmLxcOsTemplateController@putLxcOsTemplate',
            ],
            [
                'method' => 'delete',
                'uri' => 'lxc_os_template/{uuid}',
                'permission' => 'configuration',
                'name' => 'lxc_os_template.delete',
                'controller' => 'puqPmLxcOsTemplateController@deleteLxcOsTemplate',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_os_templates/select',
                'permission' => 'configuration',
                'name' => 'lxc_os_templates.select.get',
                'controller' => 'puqPmLxcOsTemplateController@getLxcOsTemplatesSelect',
            ],
            [
                'method' => 'get',
                'uri' => 'lxc_os_template/{uuid}/script/{type}',
                'permission' => 'configuration',
                'name' => 'lxc_os_template.script.get',
                'controller' => 'puqPmLxcOsTemplateController@getLxcOsTemplateScript',
            ],
            [
                'method' => 'put',
                'uri' => 'lxc_os_template/{uuid}/script/{type}',
                'permission' => 'configuration',
                'name' => 'lxc_os_template.script.put',
                'controller' => 'puqPmLxcOsTemplateController@putLxcOsTemplateScript',
            ],

            // DNS Zones
            [
                'method' => 'get',
                'uri' => 'dns_zones',
                'permission' => 'configuration',
                'name' => 'dns_zones.get',
                'controller' => 'puqPmDnsZoneController@getDnsZones',
            ],
            [
                'method' => 'post',
                'uri' => 'dns_zone',
                'permission' => 'configuration',
                'name' => 'dns_zone.post',
                'controller' => 'puqPmDnsZoneController@postDnsZone',
            ],
            [
                'method' => 'get',
                'uri' => 'dns_zone/{uuid}',
                'permission' => 'configuration',
                'name' => 'dns_zone.get',
                'controller' => 'puqPmDnsZoneController@getDnsZone',
            ],
            [
                'method' => 'put',
                'uri' => 'dns_zone/{uuid}',
                'permission' => 'configuration',
                'name' => 'dns_zone.put',
                'controller' => 'puqPmDnsZoneController@putDnsZone',
            ],
            [
                'method' => 'delete',
                'uri' => 'dns_zone/{uuid}',
                'permission' => 'configuration',
                'name' => 'dns_zone.delete',
                'controller' => 'puqPmDnsZoneController@deleteDnsZone',
            ],
            [
                'method' => 'get',
                'uri' => 'dns_zones/forward/select',
                'permission' => 'configuration',
                'name' => 'dns_zones.forward.select.get',
                'controller' => 'puqPmDnsZoneController@getDnsZonesForwardSelect',
            ],
            [
                'method' => 'get',
                'uri' => 'dns_zones/reverse/select',
                'permission' => 'configuration',
                'name' => 'dns_zones.reverse.select.get',
                'controller' => 'puqPmDnsZoneController@getDnsZonesReverseSelect',
            ],

            // SSH public keys
            [
                'method' => 'get',
                'uri' => 'ssh_public_keys',
                'permission' => 'configuration',
                'name' => 'ssh_public_keys.get',
                'controller' => 'puqPmSshPublicKeyController@getSshPublicKeys',
            ],
            [
                'method' => 'post',
                'uri' => 'ssh_public_key',
                'permission' => 'configuration',
                'name' => 'ssh_public_key.post',
                'controller' => 'puqPmSshPublicKeyController@postSshPublicKey',
            ],
            [
                'method' => 'delete',
                'uri' => 'ssh_public_key/{uuid}',
                'permission' => 'configuration',
                'name' => 'ssh_public_key.delete',
                'controller' => 'puqPmSshPublicKeyController@deleteSshPublicKey',
            ],

            // Client Private Networks
            [
                'method' => 'get',
                'uri' => 'client_private_networks',
                'permission' => 'configuration',
                'name' => 'client_private_networks.get',
                'controller' => 'puqPmClientPrivateNetworkController@getClientPrivateNetworks',
            ],
            [
                'method' => 'post',
                'uri' => 'client_private_network',
                'permission' => 'configuration',
                'name' => 'client_private_network.post',
                'controller' => 'puqPmClientPrivateNetworkController@postClientPrivateNetwork',
            ],
            [
                'method' => 'delete',
                'uri' => 'client_private_network/{uuid}',
                'permission' => 'configuration',
                'name' => 'client_private_network.delete',
                'controller' => 'puqPmClientPrivateNetworkController@deleteClientPrivateNetwork',
            ],

            // Settings
            [
                'method' => 'get',
                'uri' => 'settings',
                'permission' => 'configuration',
                'name' => 'settings.get',
                'controller' => 'puqPmController@getSettings',
            ],
            [
                'method' => 'put',
                'uri' => 'settings',
                'permission' => 'configuration',
                'name' => 'settings.put',
                'controller' => 'puqPmController@putSettings',
            ],
        ];
    }

    public function scheduler(): array
    {
        return [
            [
                'artisan' => 'SyncClusters',
                'cron' => '* * * * *',
                'disable' => false,
            ],
            [
                'artisan' => 'MakeBackups',
                'cron' => '* * * * *',
                'disable' => false,
            ],
        ];
    }

    public function getClientAreaMenuConfig(): array
    {
        $service = \App\Models\Service::find($this->service_uuid);


        $type = $this->product_data['type'] ?? 'lxc';
        if ($type == 'lxc') {

            $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

            if (!$lxc_instance) {
                return [
                    'general' => [
                        'name' => __('Product.puqProxmox.General'),
                        'template' => 'client_area.lxc.deployment',
                    ],
                ];
            }

            $menu = [
                'general' => [
                    'name' => __('Product.puqProxmox.General'),
                    'template' => 'client_area.lxc.general',
                ],
                'graphs' => [
                    'name' => __('Product.puqProxmox.Graphs'),
                    'template' => 'client_area.lxc.graphs',
                ],
            ];

            //if ......
            $menu['backups'] = [
                'name' => __('Product.puqProxmox.Backups'),
                'template' => 'client_area.lxc.backups',
            ];

            $menu['networks'] = [
                'name' => __('Product.puqProxmox.Networks'),
                'template' => 'client_area.lxc.networks',
            ];

            $menu['firewall'] = [
                'name' => __('Product.puqProxmox.Firewall'),
                'template' => 'client_area.lxc.firewall',
            ];

//            $menu['addition_disk'] = [
//                'name' => __('Product.puqProxmox.Addition Disk'),
//                'template' => 'client_area.lxc.addition_disk',
//            ];

            $menu['rebuild'] = [
                'name' => __('Product.puqProxmox.Rebuild'),
                'template' => 'client_area.lxc.rebuild',
            ];

            $menu['rescale'] = [
                'name' => __('Product.puqProxmox.Rescale'),
                'template' => 'client_area.lxc.rescale',
            ];

            return $menu;
        }

        return [];
    }


    // LXC General ------------------------------------------------------------------------------
    public function variables_general(): array
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        return $data;
    }

    public function controllerClient_getLxcInfo(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

        if ($lxc_instance) {
            $data = $lxc_instance->getInfo();
        }

        return response()->json([
            'status' => 'success',
            'data' => $data ?? [],
        ]);
    }

    public function controllerClient_getLxcLocation(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

        if ($lxc_instance) {
            $data = $lxc_instance->getLocation();
        }

        return response()->json([
            'status' => 'success',
            'data' => $data ?? [],
        ]);
    }

    public function controllerClient_getLxcStatus(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

        if ($lxc_instance) {
            $status = $lxc_instance->getStatus();
        }

        return response()->json([
            'status' => 'success',
            'data' => $status ?? [],
        ]);
    }

    public function controllerClient_getLxcStart(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $status = $lxc_instance->getStatus();
        if ($status['status'] != 'stopped') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The Start action failed. LXC running or not yet ready')],
            ], 412);
        }

        $stop = $lxc_instance->start();

        if ($stop['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The Start action failed. Please try again later')],
            ], 412);
        }

        $set_firewall_options = $lxc_instance->setFirewallOptions();

        if ($set_firewall_options['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $set_firewall_options['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ]);
    }

    public function controllerClient_getLxcStop(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $status = $lxc_instance->getStatus();
        if ($status['status'] != 'running') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The Start action failed. LXC stopped or not yet ready')],
            ], 412);
        }

        $stop = $lxc_instance->stop();

        if ($stop['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The Stop action failed. Please try again later')],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ]);
    }

    public function controllerClient_getLxcConsole(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $status = $lxc_instance->getStatus();
        if ($status['status'] != 'running') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.Failed to open console. LXC stopped or not yet ready')],
            ], 412);
        }

        $console = $lxc_instance->console();

        if ($console['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $console['errors'] ?? [],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'data' => $console['data'],
        ]);
    }

    public function controllerClient_getUsernamePassword(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        if (!empty($this->service_data['show_password_once'])) {
            $data = [
                'root_password' => $this->service_data['root_password'] ?? '',
                'username' => $this->service_data['username'] ?? '',
                'password' => $this->service_data['password'] ?? '',
            ];
        }

        $this->service_data['show_password_once'] = false;
        $service->setProvisionData($this->service_data);

        return response()->json([
            'status' => 'success',
            'data' => $data ?? [],
        ]);
    }

    public function controllerClient_getLxcResetPasswords(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $status = $lxc_instance->getStatus();
        if (empty($status) or $status['status'] != 'running') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.Failed to reset passwords. LXC stopped or not yet ready')],
            ], 412);
        }

        $reset_passwords = $lxc_instance->resetPasswords();
        if ($reset_passwords['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $reset_passwords['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Passwords Reset Successfully'),
        ], 200);
    }


    // LXC Graphs -------------------------------------------------------------------------------
    public function variables_graphs(): array
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        return $data;
    }

    public function controllerClient_getLxcRrdData(Request $request): JsonResponse
    {
        $timeframe = $request->input('timeframe') ?? 'hour';
        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if ($lxc_instance) {
            $data = $lxc_instance->getRrdData($timeframe);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data['data'] ?? [],
        ]);
    }


    // LXC Backups -------------------------------------------------------------------------------
    public function variables_backups(): array
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        return $data;
    }

    public function controllerClient_getLxcBackupSchedule(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $backup_schedule = $lxc_instance->getBackupSchedule();

        return response()->json([
            'status' => 'success',
            'data' => $backup_schedule,
        ], 200);
    }

    public function controllerClient_postLxcBackupSchedule(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $schedule = [];

        foreach ($days as $day) {
            $time = $request->input("{$day}_time", '00:00');
            if (!preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])$/', $time)) {
                $time = '00:00';
            }

            $schedule[$day] = [
                'enable' => $request->input("{$day}_enabled") == 'yes' ? true : false,
                'time' => $time,
            ];
        }

        $lxc_instance->backup_schedule = $schedule;
        $lxc_instance->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }

    public function controllerClient_getLxcBackups(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ], 200);
        }

        $backups_raw = $lxc_instance->getBackups();
        if ($backups_raw['status'] == 'error') {
            return response()->json([
                'status' => 'success',
                'data' => ['original' => []],
            ], 200);
        }
        $backups = [];
        foreach ($backups_raw['data'] as $backup_raw) {
            $backups[$backup_raw['ctime']] = [
                'note' => $backup_raw['notes'],
                'date' => $backup_raw['ctime'],
                'size' => $backup_raw['size'],
                'urls' => [
                    'restore' => route('client.api.cloud.service.module.post',
                        ['uuid' => $this->service_uuid, 'method' => 'postLxcBackupRestore']),
                    'delete' => route('client.api.cloud.service.module.post',
                        ['uuid' => $this->service_uuid, 'method' => 'postLxcBackupDelete']),
                ],
            ];
        }

        krsort($backups);
        $data['original']['data'] = array_values($backups);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    public function controllerClient_postLxcBackupDelete(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $delete = $lxc_instance->deleteFile((int) $request->input('date'), (int) $request->input('size'));

        if ($delete['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $delete['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }

    public function controllerClient_getLxcBackupsInfo(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $status = $lxc_instance->getStatus();
        $backups_raw = $lxc_instance->getBackups();

        $info = [
            'max_backups' => $lxc_instance->backup_count,
            'used_backups' => count($backups_raw['data'] ?? []),
            'status' => $status['status'],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $info,
        ], 200);
    }

    public function controllerClient_postLxcBackupNow(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'note' => ['required', 'string', 'max:250', 'regex:/^[A-Za-z0-9\s\-_]+$/'],
        ], [
            'note.required' => __('Product.puqProxmox.The Note field is required'),
            'note.regex' => __('Product.puqProxmox.Note can only contain English letters and numbers'),
            'note.max' => __('Product.puqProxmox.Note cannot exceed 250 characters'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $status = $lxc_instance->getStatus();

        if ($status['status'] != 'running' && $status['status'] != 'stopped') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC status should be running or stopped')],
            ], 412);
        }

        $backups_raw = $lxc_instance->getBackups();
        if ($lxc_instance->backup_count <= count($backups_raw['data'] ?? [])) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.Max backups reached')],
            ], 412);
        }

        $backup_now = $lxc_instance->backupNow($request->input('note'));

        if ($backup_now['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $backup_now['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }

    public function controllerClient_postLxcBackupRestore(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $status = $lxc_instance->getStatus();

        if ($status['status'] != 'stopped') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC status should be stopped')],
            ], 412);
        }

        $restore = $lxc_instance->restoreBackup((int) $request->input('date'), (int) $request->input('size'));

        if ($restore['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $restore['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }


    // LXC Networks --------------------------------------------------------------------------------
    public function variables_networks(): array
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        return $data;
    }

    public function controllerClient_getPublicNetworks(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $networks = $lxc_instance->getPublicNetworks();

        return response()->json([
            'status' => 'success',
            'data' => $networks,
        ], 200);
    }

    public function controllerClient_getPrivateNetworks(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $networks = $lxc_instance->getPrivateNetworks();

        return response()->json([
            'status' => 'success',
            'data' => $networks,
        ], 200);
    }

    public function controllerClient_postLxcPublicNetworks(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ipv4_rdns' => [
                'nullable',
                'string',
                'max:250',
                'regex:/^(?!-)(?!.*--)(?!.*\.$)([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]+$/',
            ],
            'ipv6_rdns' => [
                'nullable',
                'string',
                'max:250',
                'regex:/^(?!-)(?!.*--)(?!.*\.$)([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]+$/',
            ],
        ], [
            'ipv4_rdns.regex' => __('Product.puqProxmox.Invalid IPv4 rDNS format'),
            'ipv6_rdns.regex' => __('Product.puqProxmox.Invalid IPv6 rDNS format'),
            'ipv4_rdns.max' => __('Product.puqProxmox.rDNS cannot exceed 250 characters'),
            'ipv6_rdns.max' => __('Product.puqProxmox.rDNS cannot exceed 250 characters'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $lxc_instance->setRdns($request->input('ipv4_rdns'), $request->input('ipv6_rdns'));

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }


    // Firewall -------------------------------------------------------------------------------------
    public function variables_firewall(): array
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        return $data;
    }

    public function controllerClient_getLxcFirewallPolicies(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $firewall_policies = $lxc_instance->getFirewallPolicies();

        return response()->json([
            'status' => 'success',
            'data' => $firewall_policies,
        ], 200);
    }

    public function controllerClient_postLxcFirewallPolicies(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'policy_in' => ['required', 'string', 'in:ACCEPT,REJECT,DROP'],
            'policy_out' => ['required', 'string', 'in:ACCEPT,REJECT,DROP'],
        ], [
            'policy_in.required' => __('Product.puqProxmox.Incoming policy is required'),
            'policy_in.in' => __('Product.puqProxmox.Invalid value for incoming policy'),
            'policy_out.required' => __('Product.puqProxmox.Outgoing policy is required'),
            'policy_out.in' => __('Product.puqProxmox.Invalid value for outgoing policy'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $set_firewall_options = $lxc_instance->setFirewallOptions($request->input('policy_in'),
            $request->input('policy_out'));

        if ($set_firewall_options['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $set_firewall_options['errors'],
            ], 412);
        }


        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }

    public function controllerClient_getLxcFirewallRules(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ], 200);
        }

        $firewall_rules_raw = $lxc_instance->getFirewallRules();

        if ($firewall_rules_raw['status'] == 'error') {
            return response()->json([
                'status' => 'success',
                'data' => ['original' => []],
            ], 200);
        }
        $firewall_rules = [];
        foreach ($firewall_rules_raw['data'] as $firewall_rule_raw) {
            $firewall_rules[$firewall_rule_raw['pos']] = [
                'pos' => $firewall_rule_raw['pos'],
                'action' => $firewall_rule_raw['action'],
                'type' => $firewall_rule_raw['type'] ?? '',
                'proto' => $firewall_rule_raw['proto'] ?? '',
                'dest' => $firewall_rule_raw['dest'] ?? '',
                'dport' => $firewall_rule_raw['dport'] ?? '',
                'source' => $firewall_rule_raw['source'] ?? '',
                'sport' => $firewall_rule_raw['sport'] ?? '',
                'comment' => $firewall_rule_raw['comment'] ?? '',
                'urls' => [
                    'delete' => route('client.api.cloud.service.module.post',
                        ['uuid' => $this->service_uuid, 'method' => 'postLxcFirewallRuleDelete']),
                ],
            ];
        }

        ksort($firewall_rules);
        $data['original']['data'] = array_values($firewall_rules);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    public function controllerClient_postLxcFirewallRuleUpdateOrder(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ], 200);
        }

        $data = [
            'pos' => $request->input('pos') ?? 0,
            'moveto' => $request->input('moveto') ?? 0,
        ];

        $create = $lxc_instance->setFirewallRuleUpdate($data);

        if ($create['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $create['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
        ], 200);
    }

    public function controllerClient_postLxcFirewallRuleDelete(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ], 200);
        }

        $data = [
            'pos' => $request->input('pos') ?? 0,
        ];

        $create = $lxc_instance->setFirewallRuleDelete($data);

        if ($create['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $create['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
        ], 200);
    }

    public function controllerClient_postLxcFirewallRuleCreate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', 'string', 'in:ACCEPT,REJECT,DROP'],
            'type' => ['required', 'string', 'in:in,out'],

            'proto' => ['nullable', 'string', 'in:tcp,udp,icmp'],

            'source' => [
                'nullable', 'string', function ($attribute, $value, $fail) {
                    if (!filter_var($value,
                            FILTER_VALIDATE_IP) && !preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]|[1-2][0-9]|3[0-2])$/',
                            $value) && !preg_match('/^([0-9a-fA-F:]+)\/([0-9]|[1-9][0-9]|1[0-1][0-9]|12[0-8])$/',
                            $value)) {
                        $fail(__('Product.puqProxmox.Source must be valid IPv4/IPv6 or CIDR subnet'));
                    }
                },
            ],

            'dest' => [
                'nullable', 'string', function ($attribute, $value, $fail) {
                    if (!filter_var($value,
                            FILTER_VALIDATE_IP) && !preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]|[1-2][0-9]|3[0-2])$/',
                            $value) && !preg_match('/^([0-9a-fA-F:]+)\/([0-9]|[1-9][0-9]|1[0-1][0-9]|12[0-8])$/',
                            $value)) {
                        $fail(__('Product.puqProxmox.Destination must be valid IPv4/IPv6 or CIDR subnet'));
                    }
                },
            ],

            'sport' => ['nullable', 'integer', 'between:1,65535'],
            'dport' => ['nullable', 'integer', 'between:1,65535'],

            'comment' => ['nullable', 'string', 'max:255'],
        ], [
            'action.required' => __('Product.puqProxmox.Action is required'),
            'action.in' => __('Product.puqProxmox.Invalid action value'),

            'type.required' => __('Product.puqProxmox.Direction is required'),
            'type.in' => __('Product.puqProxmox.Invalid direction value'),

            'proto.in' => __('Product.puqProxmox.Invalid protocol value'),

            'sport.integer' => __('Product.puqProxmox.Source port must be a number'),
            'sport.between' => __('Product.puqProxmox.Source port must be between 1 and 65535'),

            'dport.integer' => __('Product.puqProxmox.Destination port must be a number'),
            'dport.between' => __('Product.puqProxmox.Destination port must be between 1 and 65535'),

            'comment.max' => __('Product.puqProxmox.Comment too long'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ], 200);
        }

        $data = [
            'action' => $request->input('action'),
            'type' => $request->input('type'),
            'enable' => 1,
        ];

        if ($request->filled('proto')) {
            $data['proto'] = $request->input('proto');
        }
        if ($request->filled('dest')) {
            $data['dest'] = $request->input('dest');
        }
        if ($request->filled('dport')) {
            $data['dport'] = $request->input('dport');
        }
        if ($request->filled('source')) {
            $data['source'] = $request->input('source');
        }
        if ($request->filled('sport')) {
            $data['sport'] = $request->input('sport');
        }
        if ($request->filled('comment')) {
            $data['comment'] = $request->input('comment');
        }

        $create = $lxc_instance->createFirewallRule($data);

        if ($create['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $create['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }


    // Rebuild --------------------------------------------------------------------------------------
    public function variables_rebuild(): array
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        return $data;
    }

    public function controllerClient_getLxcRebuildInfo(Request $request): JsonResponse
    {
        $client = app('client');
        $currency = $client->currency;
        $service = \App\Models\Service::find($this->service_uuid);
        $product = $service->product;
        $lxc_os_templates = PuqPmLxcOsTemplate::query()->get();

        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }
        $images = [];
        $status = $lxc_instance->getStatus();

        $product_option_group_uuid = [];

        $uuid = data_get($product, 'module.module.product_data.os_product_option_group_uuid');
        if ($uuid && !in_array($uuid, $product_option_group_uuid)) {
            $product_option_group_uuid[] = $uuid;
        }

        $product_options = ProductOption::query()->whereIn('product_option_group_uuid',
            $product_option_group_uuid)->orderBy('order')->get();

        foreach ($product_options as $product_option) {

            $price_model = $product_option->prices()
                ->where('currency_uuid', $currency->uuid)
                ->where('period', 'monthly')
                ->first();

            $price = [];
            if ($price_model) {
                if ($price_model->setup) {
                    $price['setup'] = $currency->prefix.' '.number_format($price_model->setup, 2).' '.$currency->suffix;
                }
                if ($price_model->base) {
                    $price['base'] = $currency->prefix.' '.number_format($price_model->base, 2).' '.$currency->suffix;
                }
            }

            $os_template = $lxc_os_templates->where('key', $product_option->value)->first();

            $icon = data_get($product_option, 'images.icon');

            if (empty($os_template->distribution) or empty($os_template->version)) {
                continue;
            }

            $versions = [
                'version' => $os_template->version ?? '',
                'uuid' => $product_option->uuid,
                'name' => $product_option->name,
                'value' => $product_option->value,
                'price' => $price,
            ];

            if (empty($images[$os_template->distribution ?? 'test']['name'])) {
                $images[$os_template->distribution ?? 'test']['name'] = $os_template->distribution;
                $images[$os_template->distribution ?? 'test']['icon'] = $icon;
            }
            $images[$os_template->distribution ?? 'test']['versions'][] = $versions;
        }

        $info = [
            'status' => $status['status'],
            'images' => $images,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $info,
        ], 200);
    }

    public function controllerClient_postLxcRebuildNow(Request $request): JsonResponse
    {

        if ($request->input('protect') != 'REINSTALL') {
            return response()->json([
                'status' => 'error',
                'message' => ['protect' => [__('Product.puqProxmox.The Protect word should be REINSTALL')]],
            ], 412);
        }

        $service = \App\Models\Service::find($this->service_uuid);
        $product = $service->product;
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $status = $lxc_instance->getStatus();

        if ($status['status'] != 'stopped') {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC status should be stopped')],
            ], 412);
        }

        $product_data = data_get($product, 'module.module.product_data');
        $os_product_option_uuid = $request->get('os_product_option_uuid');
        $os_product_option_group_uuid = $product_data['os_product_option_group_uuid'] ?? null;

        if (!$product->hasProductOption($os_product_option_group_uuid, $os_product_option_uuid)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The selected Image is not available')],
            ], 404);
        }

        $current_options = $service->productOptions()->where('product_option_group_uuid',
            $os_product_option_group_uuid)->pluck('uuid');
        if ($current_options->isNotEmpty()) {
            $service->productOptions()->detach($current_options);
        }

        $service->productOptions()->attach($os_product_option_uuid);
        $selected_option = $service->productOptions()->where('uuid', $os_product_option_uuid)->first();
        $puq_pm_lxc_os_template = PuqPmLxcOsTemplate::query()->where('key', $selected_option->value)->first();
        $lxc_instance->puq_pm_lxc_os_template_uuid = $puq_pm_lxc_os_template->uuid;
        $restore = $lxc_instance->rebuildNow();
        $puq_pm_cluster = $lxc_instance->puqPmCluster;
        $puq_pm_cluster->getClusterResources(true);
        $lxc_instance->getStatus();

        if ($restore['status'] == 'error') {
            $service->productOptions()->detach($os_product_option_uuid);
            $service->productOptions()->attach($current_options);

            return response()->json([
                'status' => 'error',
                'errors' => $restore['errors'],
            ], 412);
        }

        $lxc_instance->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }


    // Rescale ------------------------------------------------------------------------------------------
    public function variables_rescale(): array
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        return $data;
    }

    public function controllerClient_getLxcRescaleOptions(Request $request): JsonResponse
    {

        $service = \App\Models\Service::find($this->service_uuid);
        $product = $service->product;
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $service_price = $service->price;
        $currency = $service_price->currency;

        $options = [];

        foreach ($service->getUpdateDowngradeProductOptions() as $u_d_o) {

            if ($u_d_o['product_option_group']->uuid == $this->product_data['os_product_option_group_uuid']) {
                continue;
            }
            if ($u_d_o['product_option_group']->uuid == $this->product_data['location_product_option_group_uuid']) {
                continue;
            }

            $current = [
                'name' => $u_d_o['current']->name,
                'uuid' => $u_d_o['current']->uuid,
                'order' => $u_d_o['current']->order,
                'price' => $u_d_o['current']->price['base'] ?? 0.00,
            ];

            $product_option_group = [
                'name' => $u_d_o['product_option_group']->name,
                'uuid' => $u_d_o['product_option_group']->uuid,
            ];

            $up = [];

            foreach ($u_d_o['up'] as $o_up) {
                $up[] = [
                    'name' => $o_up->name,
                    'uuid' => $o_up->uuid,
                    'order' => $o_up->order,
                    'price' => $o_up->price['base'] ?? 0.00,
                ];
            }

            $down = [];

            foreach ($u_d_o['down'] as $o_down) {
                $down[] = [
                    'name' => $o_down->name,
                    'uuid' => $o_down->uuid,
                    'order' => $o_down->order,
                    'price' => $o_down->price['base'] ?? 0.00,
                ];
            }

            $tmp = [
                'currency_code' => $currency->code,
                'currency_prefix' => $currency->prefix,
                'currency_suffix' => $currency->suffix,
                'product_option_group' => $product_option_group,
                'current' => $current,
                'up' => $up,
                'down' => $down,
            ];

            $options[] = $tmp;

        }

        return response()->json([
            'status' => 'success',
            'data' => $options,
        ], 200);
    }

    public function controllerClient_getLxcRescaleCalculateSummary(Request $request): JsonResponse
    {

        $service = \App\Models\Service::find($this->service_uuid);
        $product = $service->product;
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }


        $service_price = $service->price;
        $currency = $service_price->currency;
        $options = [];
        $switch_fee = 0;
        $period = $service_price->period;
        $total_old_price = (float) $service_price->base;
        $total_new_price = (float) $service_price->base;

        foreach ($service->getUpdateDowngradeProductOptions() as $u_d_o) {
            $product_option_group_uuid = $u_d_o['product_option_group']->uuid;
            $new_product_option_uuid = $request->input($product_option_group_uuid);
            $current_price_base = $u_d_o['current']->price['base'] ?? 0.00;
            $total_old_price += (float) $current_price_base;

            if ($product_option_group_uuid == $this->product_data['os_product_option_group_uuid']) {
                $total_new_price += (float) $current_price_base;
                continue;
            }
            if ($product_option_group_uuid == $this->product_data['location_product_option_group_uuid']) {
                $total_new_price += (float) $current_price_base;
                continue;
            }

            if ($new_product_option_uuid == $u_d_o['current']->uuid) {
                $total_new_price += (float) $current_price_base;
                continue;
            }

            $label = '';
            $price = 0.00;

            foreach ($u_d_o['up'] as $o_up) {
                if ($o_up->uuid == $new_product_option_uuid) {
                    $switch_fee += $o_up->price['switch_up'] ?? 0;
                    $label = $o_up->name;
                    $price = $currency->prefix.' '.number_format($o_up->price['base'] ?? 0, 2).' '.$currency->suffix;
                    $new_price_base = $o_up->price['base'] ?? 0;
                    $total_new_price += (float) $new_price_base;
                }
            }

            foreach ($u_d_o['down'] as $o_down) {
                if ($o_down->uuid == $new_product_option_uuid) {
                    $switch_fee += $o_down->price['switch_down'] ?? 0;
                    $label = $o_down->name;
                    $price = $currency->prefix.' '.number_format($o_down->price['base'] ?? 0, 2).' '.$currency->suffix;
                    $new_price_base = $o_down->price['base'] ?? 0;
                    $total_new_price += (float) $new_price_base;
                }
            }

            $options[] = [
                'group_lable' => $u_d_o['product_option_group']->name,
                'old' => [
                    'label' => $u_d_o['current']->name,
                    'price' => $currency->prefix.' '.number_format($current_price_base, 2).' '.$currency->suffix,
                ],
                'new' => [
                    'label' => $label,
                    'price' => $price,
                ],

            ];

        }

        $data = [
            'switch_fee' => $currency->prefix.' '.number_format($switch_fee, 2).' '.$currency->suffix,
            'old_price' => $currency->prefix.' '.number_format($total_old_price, 2).' '.$currency->suffix,
            'new_price' => $currency->prefix.' '.number_format($total_new_price, 2).' '.$currency->suffix,
            'period' => __('main.'.$period),
            'options' => $options,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    public function controllerClient_postLxcRescale(Request $request): JsonResponse
    {
        $service = \App\Models\Service::find($this->service_uuid);
        $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();
        if (!$lxc_instance) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.LXC not yet ready')],
            ], 412);
        }

        $options = [];

        foreach ($service->getUpdateDowngradeProductOptions() as $u_d_o) {

            $product_option_group_uuid = $u_d_o['product_option_group']->uuid;
            $new_product_option_uuid = $request->input($product_option_group_uuid);

            if ($product_option_group_uuid == $this->product_data['os_product_option_group_uuid']) {
                continue;
            }
            if ($product_option_group_uuid == $this->product_data['location_product_option_group_uuid']) {
                continue;
            }

            if ($new_product_option_uuid == $u_d_o['current']->uuid) {
                continue;
            }

            foreach ($u_d_o['up'] as $o_up) {
                if ($o_up->uuid == $new_product_option_uuid) {
                    $options[$product_option_group_uuid] = $new_product_option_uuid;
                }
            }

            foreach ($u_d_o['down'] as $o_down) {
                if ($o_down->uuid == $new_product_option_uuid) {
                    $options[$product_option_group_uuid] = $new_product_option_uuid;
                }
            }
        }

        if (empty($options)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.No update options selected')],
            ], 412);
        }

        $update = $service->setUpdateDowngradeProductOptions($options);

        if ($update['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $update['errors'],
            ], $update['code'] ?? 500);
        }

        $change_package = $this->change_package();

        if ($change_package['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $change_package['errors'],
            ], $change_package['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ], 200);
    }

}
