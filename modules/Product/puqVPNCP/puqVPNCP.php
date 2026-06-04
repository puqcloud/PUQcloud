<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

use App\Models\Service;
use App\Models\Task;
use App\Modules\Product;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Product\puqVPNCP\Models\PuqVpncpServer;
use Modules\Product\puqVPNCP\Models\PuqVpncpServerGroup;
use OpenApi\Attributes as OA;

class puqVPNCP extends Product
{
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
            if (! Schema::hasTable('puq_vpncp_server_groups')) {
                Schema::create('puq_vpncp_server_groups', function (Blueprint $table) {
                    $table->uuid()->primary();
                    $table->string('name')->unique();
                    $table->string('fill_type')->default('default');
                    $table->uuid('country_uuid')->nullable();
                    $table->uuid('region_uuid')->nullable();
                    $table->string('data_center')->nullable();
                    $table->text('description')->nullable();
                    $table->timestamps();
                });

                $groupExists = DB::table('puq_vpncp_server_groups')->where('name', 'Default')->exists();

                if (! $groupExists) {
                    DB::table('puq_vpncp_server_groups')->insert([
                        'uuid' => (string) Str::uuid(),
                        'name' => 'Default',
                        'fill_type' => 'default',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (! Schema::hasTable('puq_vpncp_servers')) {
                Schema::create('puq_vpncp_servers', function (Blueprint $table) {
                    $table->uuid()->primary();
                    $table->string('name')->unique();
                    $table->string('api_url');
                    $table->longText('api_token');
                    $table->boolean('verify_ssl')->default(true);
                    $table->json('description')->nullable();
                    $table->timestamps();
                });
            }

            if (! Schema::hasTable('puq_vpncp_server_group_server')) {
                Schema::create('puq_vpncp_server_group_server', function (Blueprint $table) {
                    $table->uuid()->primary();
                    $table->uuid('server_group_uuid');
                    $table->uuid('server_uuid');
                    $table->json('selected_networks')->nullable();
                    $table->timestamps();

                    $table->unique(['server_group_uuid', 'server_uuid'], 'group_server_unique');
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
            if (Schema::hasTable('puq_vpncp_server_group_server')) {
                Schema::drop('puq_vpncp_server_group_server');
            }
            if (Schema::hasTable('puq_vpncp_servers')) {
                Schema::drop('puq_vpncp_servers');
            }
            if (Schema::hasTable('puq_vpncp_server_groups')) {
                Schema::drop('puq_vpncp_server_groups');
            }

            $this->logInfo('deactivate', 'Success');

            return 'success';
        } catch (QueryException $e) {
            $this->logError('deactivate', 'Error deactivating plugin: '.$e->getMessage());

            return 'Error deactivating plugin: '.$e->getMessage();
        } catch (\Exception $e) {
            $this->logError('deactivate', 'Unexpected error deactivating plugin: '.$e->getMessage());

            return 'Unexpected error deactivating plugin: '.$e->getMessage();
        }
    }

    public function update(): string
    {
        try {
            // Add description to server_groups if missing
            if (Schema::hasTable('puq_vpncp_server_groups') && ! Schema::hasColumn('puq_vpncp_server_groups', 'description')) {
                Schema::table('puq_vpncp_server_groups', function (Blueprint $table) {
                    $table->text('description')->nullable()->after('data_center');
                });
            }

            // Add verify_ssl column if missing
            if (Schema::hasTable('puq_vpncp_servers') && ! Schema::hasColumn('puq_vpncp_servers', 'verify_ssl')) {
                Schema::table('puq_vpncp_servers', function (Blueprint $table) {
                    $table->boolean('verify_ssl')->default(true)->after('api_token');
                });
            }

            // Create pivot table if missing
            if (! Schema::hasTable('puq_vpncp_server_group_server')) {
                Schema::create('puq_vpncp_server_group_server', function (Blueprint $table) {
                    $table->uuid()->primary();
                    $table->uuid('server_group_uuid');
                    $table->uuid('server_uuid');
                    $table->json('selected_networks')->nullable();
                    $table->timestamps();

                    $table->unique(['server_group_uuid', 'server_uuid'], 'group_server_unique');
                });
            }

            // Migrate data from old schema: move group_uuid and selected_networks to pivot table
            if (Schema::hasTable('puq_vpncp_servers') && Schema::hasColumn('puq_vpncp_servers', 'group_uuid')) {
                $servers = DB::table('puq_vpncp_servers')->whereNotNull('group_uuid')->get();
                foreach ($servers as $server) {
                    $exists = DB::table('puq_vpncp_server_group_server')
                        ->where('server_group_uuid', $server->group_uuid)
                        ->where('server_uuid', $server->uuid)
                        ->exists();

                    if (! $exists && DB::table('puq_vpncp_server_groups')->where('uuid', $server->group_uuid)->exists()) {
                        DB::table('puq_vpncp_server_group_server')->insert([
                            'uuid' => (string) Str::uuid(),
                            'server_group_uuid' => $server->group_uuid,
                            'server_uuid' => $server->uuid,
                            'selected_networks' => $server->selected_networks,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Remove old columns
                Schema::table('puq_vpncp_servers', function (Blueprint $table) {
                    $columns = [];
                    if (Schema::hasColumn('puq_vpncp_servers', 'group_uuid')) {
                        $columns[] = 'group_uuid';
                    }
                    if (Schema::hasColumn('puq_vpncp_servers', 'disable')) {
                        $columns[] = 'disable';
                    }
                    if (Schema::hasColumn('puq_vpncp_servers', 'default')) {
                        $columns[] = 'default';
                    }
                    if (Schema::hasColumn('puq_vpncp_servers', 'max_accounts')) {
                        $columns[] = 'max_accounts';
                    }
                    if (Schema::hasColumn('puq_vpncp_servers', 'selected_networks')) {
                        $columns[] = 'selected_networks';
                    }
                    if (! empty($columns)) {
                        $table->dropColumn($columns);
                    }
                });
            }

            return 'success';
        } catch (\Exception $e) {
            $this->logError('update', 'Error: '.$e->getMessage());

            return 'Error: '.$e->getMessage();
        }
    }

    public function getProductPage(): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['config'] = $this->config;

        $product = \App\Models\Product::find($this->product_uuid);
        $data['product'] = $product;
        $data['product_attribute_groups'] = $product->productAttributes()
            ->with('productAttributeGroup')
            ->get()
            ->pluck('productAttributeGroup')
            ->filter()
            ->unique('uuid')
            ->values();

        return $this->view('admin_area.product', $data);
    }

    public function getProductData(array $data = []): array
    {
        $this->product_data = [
            'location_product_option_group_uuid' => $data['location_product_option_group_uuid'] ?? null,
            'protocols_product_attribute_group_uuid' => $data['protocols_product_attribute_group_uuid'] ?? null,
            'b_download' => $data['b_download'] ?? 0,
            'b_upload' => $data['b_upload'] ?? 0,
            'instructions_url' => $data['instructions_url'] ?? '',
        ];

        return $this->product_data;
    }

    public function saveProductData(array $data = []): array
    {
        return [
            'status' => 'success',
            'data' => $this->getProductData($data),
            'code' => 200,
        ];
    }

    public function getServiceData(array $data = []): array
    {
        $this->service_data = [
            'client_name' => $data['client_name'] ?? ($this->service_data['client_name'] ?? ''),
            'network_name' => $data['network_name'] ?? ($this->service_data['network_name'] ?? ''),
            'server_uuid' => $data['server_uuid'] ?? ($this->service_data['server_uuid'] ?? ''),
            'vpn_ip' => $data['vpn_ip'] ?? ($this->service_data['vpn_ip'] ?? ''),
        ];

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
        $data['config'] = $this->config;
        $data['servers'] = PuqVpncpServer::query()->pluck('name', 'uuid')->toArray() ?? [];

        return $this->view('admin_area.service', $data);
    }

    public function saveServiceData(array $data = []): array
    {
        return [
            'status' => 'success',
            'data' => $this->getServiceData($data),
            'code' => 200,
        ];
    }

    // ─── Actions ─────────────────────────────────────────────────

    public function create(): array
    {
        $data = [
            'module' => $this,
            'method' => 'createJob',
            'callback' => 'createCallback',
            'tries' => 1,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
        ];

        $tags = ['create'];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'puqVPNCP-VpnDeploy', $data, $tags);

        return ['status' => 'success'];
    }

    public function createJob(): array
    {
        $log_request = [
            'service_uuid' => $this->service_uuid,
            'product_uuid' => $this->product_uuid,
            'product_data' => $this->product_data,
        ];
        $service = Service::find($this->service_uuid);

        // Resolve server group from location product option
        $group = null;
        $locationOptionGroupUuid = $this->product_data['location_product_option_group_uuid'] ?? null;
        if ($locationOptionGroupUuid) {
            $locationOption = $service->productOptions()
                ->where('product_option_group_uuid', $locationOptionGroupUuid)
                ->first();

            if ($locationOption) {
                $group = PuqVpncpServerGroup::getByLocation($locationOption->value);
            }
        }

        if (! $group) {
            $log_request['sub_action'] = 'Get Server Group';
            $log_request['location_option_group_uuid'] = $locationOptionGroupUuid;
            $log_request['location_option_value'] = $locationOption->value ?? 'N/A';
            $this->logError(__FUNCTION__, $log_request, 'Server group not found for location');

            return ['status' => 'error', 'errors' => ['Server group not found for location']];
        }

        // Use new deployment logic: iterate servers, check license, check networks
        $result = $group->getServerAndNetwork();
        if (! $result) {
            $log_request['sub_action'] = 'Get Server and Network';
            $log_request['group'] = $group->toArray() ?? [];
            $this->logError(__FUNCTION__, $log_request, 'No servers or networks available');

            return ['status' => 'error', 'errors' => ['No servers or networks available']];
        }

        $server = $result['server'];
        $networkName = $result['network'];

        $this->service_data['server_uuid'] = $server->uuid;
        $this->service_data['network_name'] = $networkName;

        $vpncp = $server->getApiClient();

        // Get suggested parameters (name, username, password)
        $suggested = $vpncp->getClientSuggested();
        if ($suggested['status'] !== 'success') {
            $log_request['sub_action'] = 'API Get Suggested';
            $this->logError(__FUNCTION__, $log_request, $suggested['error'] ?? 'Failed to get suggested params');

            return ['status' => 'error', 'errors' => [$suggested['error'] ?? 'Failed to get suggested params']];
        }

        $suggestedData = $suggested['data'] ?? [];

        // Use suggested name or generate one
        $clientName = ! empty($suggestedData['name']) ? $suggestedData['name'] : 'vpn-'.Str::random(6);
        $this->service_data['client_name'] = $clientName;

        // Create client on VPN panel
        $createData = [
            'name' => $clientName,
            'network_name' => $networkName,
            'status' => 'enable',
        ];

        // Use suggested username/password for IKEv2/OpenVPN auth
        if (! empty($suggestedData['username'])) {
            $createData['username'] = $suggestedData['username'];
        }
        if (! empty($suggestedData['password'])) {
            $createData['password'] = $suggestedData['password'];
        }

        $response = $vpncp->createClient($createData);
        if ($response['status'] !== 'success') {
            $log_request['sub_action'] = 'API Create Client';
            $this->logError(__FUNCTION__, $log_request, $response['error']);

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        // Set bandwidth limits via update (create ignores b_download/b_upload)
        // API requires string values — int values are silently ignored
        $bDownload = (string) ($this->product_data['b_download'] ?? 0);
        $bUpload = (string) ($this->product_data['b_upload'] ?? 0);
        if ($bDownload !== '0' || $bUpload !== '0') {
            $bandwidthResponse = $vpncp->updateClient($clientName, [
                'b_download' => $bDownload,
                'b_upload' => $bUpload,
            ]);
            // If bandwidth update fails, roll back the client creation so we don't leave a
            // client provisioned with the wrong limits. Without rollback, billing would
            // charge the upgraded plan while the VPN server runs without caps.
            if ($bandwidthResponse['status'] !== 'success') {
                $log_request['sub_action'] = 'Set Bandwidth';
                $this->logError(__FUNCTION__, $log_request, $bandwidthResponse['error'] ?? 'Failed to set bandwidth');

                try {
                    $vpncp->deleteClient($clientName);
                } catch (\Throwable $e) {
                    // Swallow rollback error — main error below is what the user needs to see.
                }

                return [
                    'status' => 'error',
                    'errors' => [$bandwidthResponse['error'] ?? 'Failed to set bandwidth limits'],
                ];
            }
        }

        // Get assigned VPN IP from response (field is 'ipv4' in API v2)
        $vpnIp = $response['data']['ipv4'] ?? '';
        if (empty($vpnIp)) {
            $clientInfo = $vpncp->getClient($clientName);
            if ($clientInfo['status'] === 'success' && is_array($clientInfo['data'] ?? null)) {
                $vpnIp = $clientInfo['data']['ipv4'] ?? '';
            }
        }
        $this->service_data['vpn_ip'] = $vpnIp;

        $service->setProvisionData($this->service_data);

        return ['status' => 'success'];
    }

    public function retry_deploy(): array
    {
        try {
            $service = Service::find($this->service_uuid);

            // If client was provisioned, delete it from VPN server
            if (! empty($this->service_data['server_uuid']) && ! empty($this->service_data['client_name'])) {
                $server = PuqVpncpServer::query()->find($this->service_data['server_uuid']);
                if ($server) {
                    try {
                        $vpncp = $server->getApiClient();
                        $vpncp->deleteClient($this->service_data['client_name']);
                    } catch (\Exception $e) {
                        // Ignore — client may not exist on server
                    }
                }
            }

            // Clear provision data
            $this->service_data = [
                'client_name' => '',
                'network_name' => '',
                'server_uuid' => '',
                'vpn_ip' => '',
            ];
            $service->setProvisionData($this->service_data);

            // Re-run create
            return $this->create();

        } catch (\Throwable $e) {
            $this->logError('retry_deploy', '', $e->getMessage());

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    public function suspend(): array
    {
        $data = [
            'module' => $this,
            'method' => 'suspendJob',
            'callback' => 'suspendCallback',
            'tries' => 1,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
        ];

        $tags = ['suspend'];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'puqVPNCP-VpnDeploy', $data, $tags);

        return ['status' => 'success'];
    }

    public function suspendJob(): array
    {
        $log_request = [
            'service_uuid' => $this->service_uuid,
            'service_data' => $this->service_data,
            'product_uuid' => $this->product_uuid,
            'product_data' => $this->product_data,
        ];

        // Nothing to do on the remote side if client was never provisioned.
        if (empty($this->service_data['client_name'])) {
            return ['status' => 'success'];
        }

        $server = PuqVpncpServer::query()->find($this->service_data['server_uuid'] ?? null);
        if (! $server) {
            $log_request['sub_action'] = 'Get Server';
            $this->logError(__FUNCTION__, $log_request, 'No servers Available');

            return ['status' => 'error', 'errors' => ['No servers available']];
        }

        $vpncp = $server->getApiClient();

        $response = $vpncp->updateClient($this->service_data['client_name'], ['status' => 'disable']);
        if ($response['status'] !== 'success') {
            $log_request['sub_action'] = 'Disable Client';
            $this->logError(__FUNCTION__, $log_request, $response['error']);

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        return ['status' => 'success'];
    }

    public function unsuspend(): array
    {
        $data = [
            'module' => $this,
            'method' => 'unsuspendJob',
            'callback' => 'unsuspendCallback',
            'tries' => 1,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
        ];

        $tags = ['unsuspend'];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'puqVPNCP-VpnDeploy', $data, $tags);

        return ['status' => 'success'];
    }

    public function unsuspendJob(): array
    {
        $log_request = [
            'service_uuid' => $this->service_uuid,
            'service_data' => $this->service_data,
            'product_uuid' => $this->product_uuid,
            'product_data' => $this->product_data,
        ];

        if (empty($this->service_data['client_name'])) {
            return ['status' => 'success'];
        }

        $server = PuqVpncpServer::query()->find($this->service_data['server_uuid'] ?? null);
        if (! $server) {
            $log_request['sub_action'] = 'Get Server';
            $this->logError(__FUNCTION__, $log_request, 'No servers Available');

            return ['status' => 'error', 'errors' => ['No servers available']];
        }

        $vpncp = $server->getApiClient();

        $response = $vpncp->updateClient($this->service_data['client_name'], ['status' => 'enable']);
        if ($response['status'] !== 'success') {
            $log_request['sub_action'] = 'Enable Client';
            $this->logError(__FUNCTION__, $log_request, $response['error']);

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        return ['status' => 'success'];
    }

    public function idle(): array
    {
        $data = [
            'module' => $this,
            'method' => 'idleJob',
            'callback' => 'idleCallback',
            'tries' => 1,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
        ];

        $tags = ['idle'];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'puqVPNCP-VpnDeploy', $data, $tags);

        return ['status' => 'success'];
    }

    public function idleJob(): array
    {
        $log_request = [
            'service_uuid' => $this->service_uuid,
            'service_data' => $this->service_data,
        ];

        if (empty($this->service_data['client_name'])) {
            return ['status' => 'success'];
        }

        $server = PuqVpncpServer::query()->find($this->service_data['server_uuid'] ?? null);
        if (! $server) {
            $log_request['sub_action'] = 'Get Server';
            $this->logError(__FUNCTION__, $log_request, 'No servers Available');

            return ['status' => 'error', 'errors' => ['No servers available']];
        }

        $vpncp = $server->getApiClient();

        $response = $vpncp->updateClient($this->service_data['client_name'], ['status' => 'disable']);
        if ($response['status'] !== 'success') {
            $log_request['sub_action'] = 'Disable Client';
            $this->logError(__FUNCTION__, $log_request, $response['error']);

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        return ['status' => 'success'];
    }

    /**
     * Ensure provision_status resolves to 'pause' on success or 'error' on failure —
     * prevents the service from being stuck at 'processing' if the job fails.
     */
    public function idleCallback($result, $jobId = null): void
    {
        $service = Service::find($this->service_uuid);
        if (! $service) {
            return;
        }

        if (($result['status'] ?? null) === 'error') {
            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            logActivity(
                'error',
                'Service:'.$service->uuid.' Idle failed. JobId: '.$jobId,
                'Idle',
                null,
                null,
                $service->user_uuid
            );
            $service->setProvisionStatus('error');

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity(
            'info',
            'Service:'.$service->uuid.' Idle successful. JobId: '.$jobId,
            'Idle',
            null,
            null,
            $service->user_uuid
        );
        $service->setProvisionStatus('pause');
    }

    public function unidle(): array
    {
        $data = [
            'module' => $this,
            'method' => 'unidleJob',
            'callback' => 'unidleCallback',
            'tries' => 1,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
        ];

        $tags = ['unidle'];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'puqVPNCP-VpnDeploy', $data, $tags);

        return ['status' => 'success'];
    }

    public function unidleJob(): array
    {
        $log_request = [
            'service_uuid' => $this->service_uuid,
            'service_data' => $this->service_data,
        ];

        if (empty($this->service_data['client_name'])) {
            return ['status' => 'success'];
        }

        $server = PuqVpncpServer::query()->find($this->service_data['server_uuid'] ?? null);
        if (! $server) {
            $log_request['sub_action'] = 'Get Server';
            $this->logError(__FUNCTION__, $log_request, 'No servers Available');

            return ['status' => 'error', 'errors' => ['No servers available']];
        }

        $vpncp = $server->getApiClient();

        $response = $vpncp->updateClient($this->service_data['client_name'], ['status' => 'enable']);
        if ($response['status'] !== 'success') {
            $log_request['sub_action'] = 'Enable Client';
            $this->logError(__FUNCTION__, $log_request, $response['error']);

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        return ['status' => 'success'];
    }

    /**
     * Ensure provision_status resolves to 'completed' on success or 'error' on failure.
     */
    public function unidleCallback($result, $jobId = null): void
    {
        $service = Service::find($this->service_uuid);
        if (! $service) {
            return;
        }

        if (($result['status'] ?? null) === 'error') {
            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            logActivity(
                'error',
                'Service:'.$service->uuid.' Unidle failed. JobId: '.$jobId,
                'Unidle',
                null,
                null,
                $service->user_uuid
            );
            $service->setProvisionStatus('error');

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity(
            'info',
            'Service:'.$service->uuid.' Unidle successful. JobId: '.$jobId,
            'Unidle',
            null,
            null,
            $service->user_uuid
        );
        $service->setProvisionStatus('completed');
    }

    public function termination(): array
    {
        $data = [
            'module' => $this,
            'method' => 'terminationJob',
            'callback' => 'terminationCallback',
            'tries' => 1,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
        ];

        $tags = ['termination'];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'puqVPNCP-VpnDeploy', $data, $tags);

        return ['status' => 'success'];
    }

    public function terminationJob(): array
    {
        $log_request = [
            'service_uuid' => $this->service_uuid,
            'service_data' => $this->service_data,
            'product_uuid' => $this->product_uuid,
            'product_data' => $this->product_data,
        ];

        $service = Service::find($this->service_uuid);

        // If client was never provisioned on a VPN server, just clear local state.
        if (! empty($this->service_data['client_name']) && ! empty($this->service_data['server_uuid'])) {
            $server = PuqVpncpServer::query()->find($this->service_data['server_uuid']);
            if (! $server) {
                $log_request['sub_action'] = 'Get Server';
                $this->logError(__FUNCTION__, $log_request, 'No servers Available');

                return ['status' => 'error', 'errors' => ['No servers available']];
            }

            $vpncp = $server->getApiClient();

            $response = $vpncp->deleteClient($this->service_data['client_name']);
            if ($response['status'] !== 'success') {
                $log_request['sub_action'] = 'Delete Client';
                $this->logError(__FUNCTION__, $log_request, $response['error']);

                return ['status' => 'error', 'errors' => [$response['error']]];
            }
        }

        $this->service_data = [
            'client_name' => '',
            'network_name' => '',
            'server_uuid' => '',
            'vpn_ip' => '',
        ];
        if ($service) {
            $service->setProvisionData($this->service_data);
        }

        return ['status' => 'success'];
    }

    public function cancellation(): array
    {
        $data = [
            'module' => $this,
            'method' => 'cancellationJob',
            'callback' => 'cancellationCallback',
            'tries' => 1,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
        ];

        $tags = ['cancellation'];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'puqVPNCP-VpnDeploy', $data, $tags);

        return ['status' => 'success'];
    }

    public function cancellationJob(): array
    {
        $log_request = [
            'service_uuid' => $this->service_uuid,
            'service_data' => $this->service_data,
            'product_uuid' => $this->product_uuid,
            'product_data' => $this->product_data,
        ];

        $service = Service::find($this->service_uuid);

        // If client was provisioned, delete it from VPN server
        if (! empty($this->service_data['server_uuid']) && ! empty($this->service_data['client_name'])) {
            $server = PuqVpncpServer::query()->find($this->service_data['server_uuid']);
            if ($server) {
                $vpncp = $server->getApiClient();
                $response = $vpncp->deleteClient($this->service_data['client_name']);
                if ($response['status'] !== 'success') {
                    $log_request['sub_action'] = 'Delete Client';
                    $this->logError(__FUNCTION__, $log_request, $response['error']);

                    return ['status' => 'error', 'errors' => [$response['error']]];
                }
            }
        }

        $this->service_data = [
            'client_name' => '',
            'network_name' => '',
            'server_uuid' => '',
            'vpn_ip' => '',
        ];
        $service->setProvisionData($this->service_data);

        return ['status' => 'success'];
    }

    public function change_package(): array
    {
        $data = [
            'module' => $this,
            'method' => 'change_packageJob',
            'callback' => 'change_packageCallback',
            'tries' => 5,
            'backoff' => 30,
            'timeout' => 600,
            'maxExceptions' => 1,
        ];

        $tags = ['change_package'];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('change_package');
        Task::add('ModuleJob', 'puqVPNCP-VpnDeploy', $data, $tags);

        return ['status' => 'success'];
    }

    public function change_packageJob(): array
    {
        $log_request = [
            'service_uuid' => $this->service_uuid,
            'service_data' => $this->service_data,
            'product_uuid' => $this->product_uuid,
            'product_data' => $this->product_data,
        ];

        // Defense in depth: reload service_data and product_data straight from DB before
        // trusting the serialized module instance. Historically the instance serialized into
        // the queue could lose service_data when Service::applyModuleChangePackage ran after
        // a refresh() that didn't re-fire Service::retrieved, resulting in a no-op job with
        // empty client_name. Fetching from DB here guarantees correct data regardless.
        $freshService = Service::find($this->service_uuid);
        if ($freshService) {
            if (is_array($freshService->provision_data)) {
                $this->service_data = array_merge($this->service_data ?? [], $freshService->provision_data);
            }
            if ($freshService->product) {
                $freshConfig = $freshService->product->configuration ?? [];
                if (is_array($freshConfig)) {
                    $this->product_data['b_download'] = $freshConfig['b_download'] ?? ($this->product_data['b_download'] ?? 0);
                    $this->product_data['b_upload'] = $freshConfig['b_upload'] ?? ($this->product_data['b_upload'] ?? 0);
                }
            }
        }

        // No remote client yet — bandwidth will be applied when createJob provisions the client.
        if (empty($this->service_data['client_name'])) {
            return ['status' => 'success'];
        }

        // Serialize concurrent change_package jobs for the same service so a later upgrade
        // can't overwrite bandwidth using stale product_data from an in-flight earlier run.
        $lock = Cache::lock('puqvpncp:change_package:'.$this->service_uuid, 600);
        if (! $lock->get()) {
            $log_request['sub_action'] = 'Acquire Lock';
            $this->logError(__FUNCTION__, $log_request, 'Another change_package job is in progress');

            return ['status' => 'error', 'errors' => ['Another change_package job is in progress']];
        }

        try {

            $server = PuqVpncpServer::query()->find($this->service_data['server_uuid'] ?? null);
            if (! $server) {
                $log_request['sub_action'] = 'Get Server';
                $this->logError(__FUNCTION__, $log_request, 'No servers Available');

                return ['status' => 'error', 'errors' => ['No servers available']];
            }

            $vpncp = $server->getApiClient();

            // API requires string values — int values are silently ignored
            $updateData = [];
            $updateData['b_download'] = (string) ($this->product_data['b_download'] ?? 0);
            $updateData['b_upload'] = (string) ($this->product_data['b_upload'] ?? 0);

            $response = $vpncp->updateClient($this->service_data['client_name'], $updateData);
            if ($response['status'] !== 'success') {
                $log_request['sub_action'] = 'Update Client';
                $this->logError(__FUNCTION__, $log_request, $response['error']);

                return ['status' => 'error', 'errors' => [$response['error']]];
            }

            return ['status' => 'success'];
        } finally {
            $lock->release();
        }
    }

    /**
     * Override parent callback: on error do NOT flip an already-active service to 'error' —
     * the VPN is still running with the old bandwidth. Surface the failure to admin via
     * create_error + activity log so it can be retried, but keep the client view healthy.
     */
    public function change_packageCallback($result, $jobId = null): void
    {
        $service = Service::find($this->service_uuid);
        if (! $service) {
            return;
        }

        if ($result['status'] === 'error') {
            $error = '';
            foreach (($result['errors'] ?? []) as $i => $e) {
                $error .= ($i > 0 ? ', ' : '').$e;
            }

            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            logActivity(
                'error',
                'Service:'.$service->uuid.' Change Package FAILED on VPN server. JobId: '.$jobId.'. Error: '.$error,
                'Change Package',
                null,
                null,
                $service->user_uuid
            );

            // Persist admin-facing error; keep provision_status completed so the running
            // service doesn't appear broken to the client.
            $service->create_error = 'change_package: '.$error;
            $service->setProvisionStatus('completed');
            $service->save();

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity(
            'info',
            'Service:'.$service->uuid.' Change Package Successful. JobId: '.$jobId,
            'Change Package',
            null,
            null,
            $service->user_uuid
        );
        $service->create_error = '';
        $service->setProvisionStatus('completed');
        $service->save();
    }

    // ─── Admin ───────────────────────────────────────────────────

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
                'title' => 'Servers',
                'link' => 'servers',
                'active_links' => ['servers', 'server.tab'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'Server Groups',
                'link' => 'server_groups',
                'active_links' => ['server_groups', 'server_group.tab'],
                'permission' => 'configuration',
            ],
        ];
    }

    public function adminWebRoutes(): array
    {
        return [
            [
                'method' => 'get',
                'uri' => 'servers',
                'permission' => 'configuration',
                'name' => 'servers',
                'controller' => 'puqVpncpController@servers',
            ],
            [
                'method' => 'get',
                'uri' => 'server_groups',
                'permission' => 'configuration',
                'name' => 'server_groups',
                'controller' => 'puqVpncpController@serverGroups',
            ],
            [
                'method' => 'get',
                'uri' => 'server/{uuid}/{tab}',
                'permission' => 'configuration',
                'name' => 'server.tab',
                'controller' => 'puqVpncpController@serverTab',
            ],
            [
                'method' => 'get',
                'uri' => 'server_group/{uuid}/{tab}',
                'permission' => 'configuration',
                'name' => 'server_group.tab',
                'controller' => 'puqVpncpController@serverGroupTab',
            ],
        ];
    }

    public function adminApiRoutes(): array
    {
        return [
            // Servers
            [
                'method' => 'get',
                'uri' => 'servers',
                'permission' => 'configuration',
                'name' => 'servers.get',
                'controller' => 'puqVpncpController@getServers',
            ],
            [
                'method' => 'post',
                'uri' => 'server',
                'permission' => 'configuration',
                'name' => 'server.post',
                'controller' => 'puqVpncpController@postServer',
            ],
            [
                'method' => 'delete',
                'uri' => 'server/{uuid}',
                'permission' => 'configuration',
                'name' => 'server.delete',
                'controller' => 'puqVpncpController@deleteServer',
            ],

            // Groups
            [
                'method' => 'get',
                'uri' => 'server_groups',
                'permission' => 'configuration',
                'name' => 'server_groups.get',
                'controller' => 'puqVpncpController@getServerGroups',
            ],
            [
                'method' => 'post',
                'uri' => 'server_group',
                'permission' => 'configuration',
                'name' => 'server_group.post',
                'controller' => 'puqVpncpController@postServerGroup',
            ],
            [
                'method' => 'get',
                'uri' => 'server_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'server_group.get',
                'controller' => 'puqVpncpController@getServerGroup',
            ],
            [
                'method' => 'put',
                'uri' => 'server_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'server_group.put',
                'controller' => 'puqVpncpController@putServerGroup',
            ],
            [
                'method' => 'delete',
                'uri' => 'server_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'server_group.delete',
                'controller' => 'puqVpncpController@deleteServerGroup',
            ],
            [
                'method' => 'get',
                'uri' => 'server_groups/select',
                'permission' => 'configuration',
                'name' => 'server_groups.select.get',
                'controller' => 'puqVpncpController@getServerGroupsSelect',
            ],

            // Server detail
            [
                'method' => 'get',
                'uri' => 'server/{uuid}',
                'permission' => 'configuration',
                'name' => 'server.get',
                'controller' => 'puqVpncpController@getServer',
            ],
            [
                'method' => 'put',
                'uri' => 'server/{uuid}',
                'permission' => 'configuration',
                'name' => 'server.put',
                'controller' => 'puqVpncpController@putServer',
            ],
            [
                'method' => 'post',
                'uri' => 'server/{uuid}/test_connection',
                'permission' => 'configuration',
                'name' => 'server.test_connection.post',
                'controller' => 'puqVpncpController@postServerTestConnection',
            ],
            [
                'method' => 'get',
                'uri' => 'server_test_connection',
                'permission' => 'configuration',
                'name' => 'server.test_connection.get',
                'controller' => 'puqVpncpController@getServerTestConnection',
            ],
            [
                'method' => 'get',
                'uri' => 'server/{uuid}/networks',
                'permission' => 'configuration',
                'name' => 'server.networks.get',
                'controller' => 'puqVpncpController@getServerNetworks',
            ],

            // Server Group Servers management
            [
                'method' => 'get',
                'uri' => 'server_group/{uuid}/servers',
                'permission' => 'configuration',
                'name' => 'server_group.servers.get',
                'controller' => 'puqVpncpController@getServerGroupServers',
            ],
            [
                'method' => 'post',
                'uri' => 'server_group/{uuid}/server',
                'permission' => 'configuration',
                'name' => 'server_group.server.post',
                'controller' => 'puqVpncpController@postServerGroupServer',
            ],
            [
                'method' => 'put',
                'uri' => 'server_group/{group_uuid}/server/{server_uuid}',
                'permission' => 'configuration',
                'name' => 'server_group.server.put',
                'controller' => 'puqVpncpController@putServerGroupServer',
            ],
            [
                'method' => 'delete',
                'uri' => 'server_group/{group_uuid}/server/{server_uuid}',
                'permission' => 'configuration',
                'name' => 'server_group.server.delete',
                'controller' => 'puqVpncpController@deleteServerGroupServer',
            ],

            // Servers select for dropdown
            [
                'method' => 'get',
                'uri' => 'servers/select',
                'permission' => 'configuration',
                'name' => 'servers.select.get',
                'controller' => 'puqVpncpController@getServersSelect',
            ],

            // Service client info (live data from VPN server)
            [
                'method' => 'get',
                'uri' => 'service_client_info',
                'permission' => 'configuration',
                'name' => 'service_client_info.get',
                'controller' => 'puqVpncpController@getServiceClientInfo',
            ],
        ];
    }

    // ─── Client Area ─────────────────────────────────────────────

    public function getClientAreaMenuConfig(): array
    {
        $menu = [
            'general' => [
                'name' => 'General',
                'template' => 'client_area.general',
            ],
        ];

        if (! empty($this->service_data['client_name'])) {
            $menu['traffic'] = [
                'name' => 'Traffic',
                'template' => 'client_area.traffic',
            ];
        }

        return $menu;
    }

    public function variables_general(): array
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        $serverUuid = $this->service_data['server_uuid'] ?? '';
        $data['server_name'] = '';
        $data['location'] = null;

        if (! empty($serverUuid)) {
            $server = PuqVpncpServer::find($serverUuid);
            $data['server_name'] = $server->name ?? '';

            // Resolve location from server group
            if ($server) {
                $group = $server->serverGroups()->first();
                if ($group) {
                    $country = $group->country;
                    $data['location'] = [
                        'country_name' => $country->name ?? '',
                        'country_code' => $country->code ?? '',
                        'data_center' => $group->data_center ?? '',
                        'group_name' => $group->name ?? '',
                        'description' => $group->description ?? '',
                    ];
                }
            }
        }

        return $data;
    }

    public function variables_traffic(): array
    {
        return [
            'module_type' => $this->module_type,
            'module_name' => $this->module_name,
            'service_uuid' => $this->service_uuid,
            'service_data' => $this->service_data,
        ];
    }

    public function variables_instructions(): array
    {
        return [
            'module_type' => $this->module_type,
            'module_name' => $this->module_name,
            'product_data' => $this->product_data,
            'service_uuid' => $this->service_uuid,
        ];
    }

    // Client area API controllers

    #[OA\Get(
        path: 'client.api.cloud.service.module.get.clientInfoGet',
        description: 'Returns full VPN client information including WireGuard config, OpenVPN profile, IKEv2 profile, and server URL. Supports otl_only mode for one-time link retrieval.',
        summary: 'Get VPN client info',
        security: [['apiKeyAuth' => []]],
        tags: ['VPN Client'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'Service UUID', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'VPN client info returned successfully'),
            new OA\Response(response: 422, description: 'Service configuration error'),
        ]
    )]
    public function controllerClient_clientInfoGet(Request $request): JsonResponse
    {
        if (empty($this->service_data['client_name']) || empty($this->service_data['server_uuid'])) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
            ], 422);
        }

        $server = PuqVpncpServer::query()->find($this->service_data['server_uuid']);
        if (empty($server)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
            ], 422);
        }

        $vpncp = $server->getApiClient();
        $clientName = $this->service_data['client_name'];

        // OTL-only mode
        if ($request->input('otl_only')) {
            $otl = $vpncp->getOTL($clientName);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'otl' => ($otl['status'] === 'success') ? ($otl['data'] ?? '') : '',
                    'server_url' => $server->api_url,
                ],
            ]);
        }

        // Full client info
        $client = $vpncp->getClient($clientName);
        $clientData = ($client['status'] === 'success') ? ($client['data'] ?? []) : [];
        $protocols = $clientData['protocols'] ?? [];

        $wgData = ['config_text' => '', 'config_qr' => ''];
        if (! empty($protocols['wireguard'])) {
            $configText = $vpncp->getConfigText($clientName);
            $configQr = $vpncp->getConfigQR($clientName);
            $wgData['config_text'] = ($configText['status'] === 'success' && ! empty($configText['data'])) ? $configText['data'] : '';
            $wgData['config_qr'] = ($configQr['status'] === 'success' && ! empty($configQr['data'])) ? $configQr['data'] : '';
        }

        $ovpnData = ['available' => ! empty($protocols['openvpn']), 'profile' => ''];
        if (! empty($protocols['openvpn'])) {
            $openvpnProfile = $vpncp->getOpenVPNProfile($clientName);
            $ovpnData['profile'] = ($openvpnProfile['status'] === 'success') ? ($openvpnProfile['data'] ?? '') : '';
        }

        $ikev2Data = ['available' => ! empty($protocols['ikev2']), 'profile' => ''];
        if (! empty($protocols['ikev2'])) {
            $ikev2Profile = $vpncp->getIKEv2Profile($clientName);
            $ikev2Data['profile'] = ($ikev2Profile['status'] === 'success') ? ($ikev2Profile['data'] ?? '') : '';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'client' => $clientData,
                'wireguard' => $wgData,
                'openvpn' => $ovpnData,
                'ikev2' => $ikev2Data,
                'server_url' => $server->api_url,
            ],
        ]);
    }

    #[OA\Get(
        path: 'client.api.cloud.service.module.get.configWireguard',
        description: 'Returns WireGuard configuration text for the VPN client.',
        summary: 'Get WireGuard config',
        security: [['apiKeyAuth' => []]],
        tags: ['VPN Client'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'Service UUID', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'WireGuard config returned')]
    )]
    public function controllerClient_configWireguard(Request $request): JsonResponse
    {
        return $this->proxyClientRequest('getConfigText');
    }

    #[OA\Get(
        path: 'client.api.cloud.service.module.get.configQr',
        description: 'Returns WireGuard configuration as QR code image.',
        summary: 'Get WireGuard QR code',
        security: [['apiKeyAuth' => []]],
        tags: ['VPN Client'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'Service UUID', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'QR code returned')]
    )]
    public function controllerClient_configQr(Request $request): JsonResponse
    {
        return $this->proxyClientRequest('getConfigQR');
    }

    #[OA\Get(
        path: 'client.api.cloud.service.module.get.configOpenvpn',
        description: 'Returns OpenVPN profile for the VPN client.',
        summary: 'Get OpenVPN profile',
        security: [['apiKeyAuth' => []]],
        tags: ['VPN Client'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'Service UUID', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'OpenVPN profile returned')]
    )]
    public function controllerClient_configOpenvpn(Request $request): JsonResponse
    {
        return $this->proxyClientRequest('getOpenVPNProfile');
    }

    #[OA\Get(
        path: 'client.api.cloud.service.module.get.configIkev2',
        description: 'Returns IKEv2 profile for the VPN client.',
        summary: 'Get IKEv2 profile',
        security: [['apiKeyAuth' => []]],
        tags: ['VPN Client'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'Service UUID', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'IKEv2 profile returned')]
    )]
    public function controllerClient_configIkev2(Request $request): JsonResponse
    {
        return $this->proxyClientRequest('getIKEv2Profile');
    }

    #[OA\Get(
        path: 'client.api.cloud.service.module.get.otl',
        description: 'Returns a one-time link (OTL) for VPN client self-service portal access.',
        summary: 'Get one-time link',
        security: [['apiKeyAuth' => []]],
        tags: ['VPN Client'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'Service UUID', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'OTL returned')]
    )]
    public function controllerClient_otl(Request $request): JsonResponse
    {
        return $this->proxyClientRequest('getOTL');
    }

    #[OA\Get(
        path: 'client.api.cloud.service.module.get.traffic',
        description: 'Returns VPN client traffic statistics for a specific month and year.',
        summary: 'Get traffic statistics',
        security: [['apiKeyAuth' => []]],
        tags: ['VPN Traffic'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'Service UUID', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'year', description: 'Year (default: current)', in: 'query', required: false,
                schema: new OA\Schema(type: 'integer', example: 2026)),
            new OA\Parameter(name: 'month', description: 'Month (default: current)', in: 'query', required: false,
                schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Traffic data returned'),
            new OA\Response(response: 422, description: 'Service configuration error'),
        ]
    )]
    public function controllerClient_traffic(Request $request): JsonResponse
    {
        if (empty($this->service_data['client_name']) || empty($this->service_data['server_uuid'])) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
            ], 422);
        }

        $server = PuqVpncpServer::query()->find($this->service_data['server_uuid']);
        if (empty($server)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
            ], 422);
        }

        $vpncp = $server->getApiClient();

        // Validate year/month strictly to prevent arbitrary values being forwarded to the VPN API.
        // Allow the current year ± 1 (traffic data doesn't exist outside that range).
        $currentYear = (int) date('Y');
        $year = (int) $request->input('year', $currentYear);
        $month = (int) $request->input('month', (int) date('m'));

        if ($year < ($currentYear - 1) || $year > ($currentYear + 1) || $month < 1 || $month > 12) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Invalid period')],
            ], 422);
        }

        $response = $vpncp->getClientTraffic($this->service_data['client_name'], $year, $month);

        if ($response['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'data' => $response['data'],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
        ], 422);
    }

    #[OA\Get(
        path: 'client.api.cloud.service.module.get.trafficAvailable',
        description: 'Returns list of available months/years for which traffic data exists.',
        summary: 'Get available traffic periods',
        security: [['apiKeyAuth' => []]],
        tags: ['VPN Traffic'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'Service UUID', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Available periods returned'),
            new OA\Response(response: 422, description: 'Service configuration error'),
        ]
    )]
    public function controllerClient_trafficAvailable(Request $request): JsonResponse
    {
        if (empty($this->service_data['client_name']) || empty($this->service_data['server_uuid'])) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
            ], 422);
        }

        $server = PuqVpncpServer::query()->find($this->service_data['server_uuid']);
        if (empty($server)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
            ], 422);
        }

        $vpncp = $server->getApiClient();
        $response = $vpncp->getClientTrafficAvailable($this->service_data['client_name']);

        if ($response['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'data' => $response['data'],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
        ], 422);
    }

    private function proxyClientRequest(string $apiMethod): JsonResponse
    {
        if (empty($this->service_data['client_name']) || empty($this->service_data['server_uuid'])) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
            ], 422);
        }

        $server = PuqVpncpServer::query()->find($this->service_data['server_uuid']);
        if (empty($server)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
            ], 422);
        }

        $vpncp = $server->getApiClient();
        $response = $vpncp->$apiMethod($this->service_data['client_name']);

        if ($response['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'data' => $response['data'],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'errors' => [__('Product.puqVPNCP.Something went wrong try again later')],
        ], 422);
    }

    private static function sanitizeLicenseData(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        unset($data['key']);

        return $data;
    }

    // ─── Queues ──────────────────────────────────────────────────

    public function queues(): array
    {
        return [
            'VpnDeploy' => [
                'connection' => 'redis',
                'queue' => ['VpnDeploy'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 4,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 600,
                'nice' => 0,
            ],
        ];
    }

    // ─── Scheduler ───────────────────────────────────────────────

    public function scheduler(): array
    {
        return [
            [
                'artisan' => 'SyncServers',
                'cron' => '*/5 * * * *',
                'disable' => false,
            ],
        ];
    }
}
