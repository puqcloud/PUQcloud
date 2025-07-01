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

use App\Models\Service;
use App\Models\Task;
use App\Modules\Product;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Product\puqNextcloud\Models\PuqNextcloudServer;
use Modules\Product\puqNextcloud\Models\puqNextcloudServerGroup;

class puqNextcloud extends Product
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
            if (! Schema::hasTable('puq_nextcloud_servers')) {
                Schema::create('puq_nextcloud_servers', function (Blueprint $table) {
                    $table->uuid()->primary();
                    $table->uuid('group_uuid');
                    $table->string('name')->unique();
                    $table->string('host');
                    $table->string('username');
                    $table->string('password');
                    $table->boolean('active')->default(true);
                    $table->boolean('default')->default(false);
                    $table->integer('max_accounts')->default(0);
                    $table->boolean('ssl')->default(true);
                    $table->integer('port')->default(443);
                    $table->timestamps();
                });
            }

            if (! Schema::hasTable('puq_nextcloud_server_groups')) {
                Schema::create('puq_nextcloud_server_groups', function (Blueprint $table) {
                    $table->uuid()->primary();
                    $table->string('name')->unique();
                    $table->string('fill_type')->default('default'); // lowest or default
                    $table->timestamps();
                });

                $groupExists = DB::table('puq_nextcloud_server_groups')->where('name', 'Default')->exists();

                if (! $groupExists) {
                    DB::table('puq_nextcloud_server_groups')->insert([
                        'uuid' => (string) Str::uuid(),
                        'name' => 'Default',
                        'fill_type' => 'default',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
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
            if (Schema::hasTable('puq_nextcloud_servers')) {
                Schema::drop('puq_nextcloud_servers');
            }
            if (Schema::hasTable('puq_nextcloud_server_groups')) {
                Schema::drop('puq_nextcloud_server_groups');
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
        return 'success';
    }

    public function getProductPage(): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['groups'] = PuqNextcloudServerGroup::query()->pluck('name', 'uuid')->toArray() ?? [];
        $data['config'] = $this->config;

        return $this->view('admin_area.product', $data);
    }

    public function getProductData(array $data = []): array
    {
        $this->product_data = [
            'disk_space_size' => $data['disk_space_size'] ?? 0,
            'username_prefix' => $data['username_prefix'] ?? '',
            'username_suffix' => $data['username_suffix'] ?? '',
            'nextcloud_user_group' => $data['nextcloud_user_group'] ?? 'PUQcloud',
            'group_uuid' => $data['group_uuid'] ?? '',
        ];

        return $this->product_data;
    }

    public function saveProductData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'disk_space_size' => 'required',
        ], [
            'disk_space_size.required' => __('Product.puqNextcloud.The Disk Space Size field is required'),
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ];
        }

        return [
            'status' => 'success',
            'data' => $this->getProductData($data),
            'code' => 200,
        ];

    }

    public function getServiceData(array $data = []): array
    {
        $this->service_data = [
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'server_uuid' => $data['server_uuid'] ?? '',
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
        $data['servers'] = PuqNextcloudServer::query()->pluck('name', 'uuid')->toArray() ?? [];

        return $this->view('admin_area.service', $data);
    }

    public function saveServiceData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => __('Product.puqNextcloud.The Username field is required'),
            'password.required' => __('Product.puqNextcloud.The Password field is required'),
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ];
        }

        return [
            'status' => 'success',
            'data' => $this->getServiceData($data),
            'code' => 200,
        ];

    }

    public function create(): array
    {
        $data = [
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'createJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'create',
        ];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'Module', $data, $tags);

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

        $group = PuqNextcloudServerGroup::find($this->product_data['group_uuid']);
        if (! $group) {
            $log_request['sub_action'] = 'Get Server Group';
            $this->logError(__FUNCTION__, $log_request, 'Server group not found');
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => ['Server Group not found']];
        }

        $server = $group->getServer();
        if (! $server) {
            $log_request['sub_action'] = 'Get Server';
            $log_request['group'] = $group->toArray() ?? [];
            $this->logError(__FUNCTION__, $log_request, 'No servers Available');
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => ['No servers available']];
        }

        $this->service_data['server_uuid'] = $server->uuid;
        $this->service_data['username'] = $this->product_data['username_prefix'].(string) random_int(100000, 999999).$this->product_data['username_suffix'];
        $this->service_data['password'] = generateStrongPassword(10);

        $nextcloud = new puqNextcloudClient($server->toArray() ?? []);

        $service->setProvisionData($this->service_data);
        $log_request['service_data'] = $this->service_data;

        // Create User
        $data = [
            'userid' => $this->service_data['username'],
            'password' => $this->service_data['password'],
        ];
        $response = $nextcloud->apiRequest('/cloud/users', 'POST', $data);
        if ($response['status'] != 'success') {
            $log_request['sub_action'] = 'API Create User';
            $this->logError(__FUNCTION__, $log_request, $response['error']);
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        // Set User Quota
        $data = [
            'key' => 'quota',
            'value' => $this->product_data['disk_space_size'].' GB',
        ];

        $response = $nextcloud->apiRequest('/cloud/users/'.$this->service_data['username'], 'PUT', $data);
        if ($response['status'] != 'success') {
            $log_request['sub_action'] = 'API Set User Quota';
            $this->logError(__FUNCTION__, $log_request, $response['error']);
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        // Create and Set User Group
        $data = [
            'groupid' => $this->product_data['nextcloud_user_group'],
        ];

        $nextcloud->apiRequest('/cloud/groups', 'POST', $data);
        $response = $nextcloud->apiRequest('/cloud/users/'.$this->service_data['username'].'/groups', 'POST', $data);
        if ($response['status'] != 'success') {
            $log_request['sub_action'] = 'API Set User Group';
            $this->logError(__FUNCTION__, $log_request, $response['error']);
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        $service->setProvisionStatus('completed');

        return ['status' => 'success'];
    }

    public function suspend(): array
    {
        $data = [
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'suspendJob',       // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'suspend',
        ];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'Module', $data, $tags);

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

        $service = Service::find($this->service_uuid);

        $server = PuqNextcloudServer::query()->find($this->service_data['server_uuid']);
        if (! $server) {
            $log_request['sub_action'] = 'Get Server';
            $this->logError(__FUNCTION__, $log_request, 'No servers Available');
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => ['No servers available']];
        }

        $nextcloud = new puqNextcloudClient($server->toArray() ?? []);

        // Disable User
        $response = $nextcloud->apiRequest('/cloud/users/'.$this->service_data['username'].'/disable', 'PUT', []);
        if ($response['status'] != 'success') {
            $log_request['sub_action'] = 'Disable User';
            $this->logError(__FUNCTION__, $log_request, $response['error']);
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        $service->setProvisionStatus('suspended');

        return ['status' => 'success'];
    }

    public function unsuspend(): array
    {
        $data = [
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'unsuspendJob',     // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'unsuspend',
        ];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'Module', $data, $tags);

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

        $service = Service::find($this->service_uuid);

        $server = PuqNextcloudServer::query()->find($this->service_data['server_uuid']);
        if (! $server) {
            $log_request['sub_action'] = 'Get Server';
            $this->logError(__FUNCTION__, $log_request, 'No servers Available');
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => ['No servers available']];
        }

        $nextcloud = new puqNextcloudClient($server->toArray() ?? []);

        // Enable User
        $response = $nextcloud->apiRequest('/cloud/users/'.$this->service_data['username'].'/enable', 'PUT', []);
        if ($response['status'] != 'success') {
            $log_request['sub_action'] = 'Enable User';
            $this->logError(__FUNCTION__, $log_request, $response['error']);
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        $service->setProvisionStatus('completed');

        return ['status' => 'success'];
    }

    public function idle(): array
    {
        $data = [
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'idleJob',          // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'idle',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function idleJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('pause');

        return ['status' => 'success'];
    }

    public function unidle(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'unidleJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'unidle',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function unidleJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('completed');

        return ['status' => 'success'];
    }

    public function termination(): array
    {
        $data = [
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'terminationJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'termination',
        ];

        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        Task::add('ModuleJob', 'Module', $data, $tags);

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

        $server = PuqNextcloudServer::query()->find($this->service_data['server_uuid']);
        if (! $server) {
            $log_request['sub_action'] = 'Get Server';
            $this->logError(__FUNCTION__, $log_request, 'No servers Available');
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => ['No servers available']];
        }

        $nextcloud = new puqNextcloudClient($server->toArray() ?? []);

        // Delete User
        $response = $nextcloud->apiRequest('/cloud/users/'.$this->service_data['username'], 'DELETE', []);
        if ($response['status'] != 'success') {
            $log_request['sub_action'] = 'Delete User';
            $this->logError(__FUNCTION__, $log_request, $response['error']);
            $service->setProvisionStatus('error');

            return ['status' => 'error', 'errors' => [$response['error']]];
        }

        $this->service_data = [
            'username' => '',
            'password' => '',
            'server_uuid' => '',
        ];
        $service->setProvisionData($this->service_data);

        $service->setProvisionStatus('terminated');

        return ['status' => 'success'];
    }

    public function cancellation(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'cancellationJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'cancellation',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function cancellationJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('not_found');

        return ['status' => 'success'];
    }

    public function change_package(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'change_packageJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'change_package',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function change_packageJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('completed');

        return ['status' => 'success'];
    }

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
                'active_links' => ['servers', 'server'],
                'permission' => 'configuration',
            ],
            [
                'title' => 'Server Groups',
                'link' => 'server_groups',
                'active_links' => ['server_groups'],
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
                'controller' => 'puqNextcloudController@servers',
            ],
            [
                'method' => 'get',
                'uri' => 'server_groups',
                'permission' => 'configuration',
                'name' => 'server_groups',
                'controller' => 'puqNextcloudController@serverGroups',
            ],

            [
                'method' => 'get',
                'uri' => 'server/{uuid}',
                'permission' => 'configuration',
                'name' => 'server',
                'controller' => 'puqNextcloudController@server',
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
                'controller' => 'puqNextcloudController@getServers',
            ],
            [
                'method' => 'post',
                'uri' => 'server',
                'permission' => 'configuration',
                'name' => 'server.post',
                'controller' => 'puqNextcloudController@postServer',
            ],
            [
                'method' => 'delete',
                'uri' => 'server/{uuid}',
                'permission' => 'configuration',
                'name' => 'server.delete',
                'controller' => 'puqNextcloudController@deleteServer',
            ],

            // Groups
            [
                'method' => 'get',
                'uri' => 'server_groups',
                'permission' => 'configuration',
                'name' => 'server_groups.get',
                'controller' => 'puqNextcloudController@getServerGroups',
            ],
            [
                'method' => 'post',
                'uri' => 'server_group',
                'permission' => 'configuration',
                'name' => 'server_group.post',
                'controller' => 'puqNextcloudController@postServerGroup',
            ],
            [
                'method' => 'get',
                'uri' => 'server_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'server_group.put',
                'controller' => 'puqNextcloudController@getServerGroup',
            ],
            [
                'method' => 'put',
                'uri' => 'server_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'server_group.put',
                'controller' => 'puqNextcloudController@putServerGroup',
            ],
            [
                'method' => 'delete',
                'uri' => 'server_group/{uuid}',
                'permission' => 'configuration',
                'name' => 'server_group.delete',
                'controller' => 'puqNextcloudController@deleteServerGroup',
            ],
            [
                'method' => 'get',
                'uri' => 'server_groups/select',
                'permission' => 'configuration',
                'name' => 'server_groups.select.get',
                'controller' => 'puqNextcloudController@getServerGroupsSelect',
            ],

            // Server
            [
                'method' => 'get',
                'uri' => 'server/{uuid}',
                'permission' => 'configuration',
                'name' => 'server.get',
                'controller' => 'puqNextcloudController@getServer',
            ],
            [
                'method' => 'put',
                'uri' => 'server/{uuid}',
                'permission' => 'configuration',
                'name' => 'server.put',
                'controller' => 'puqNextcloudController@putServer',
            ],
            [
                'method' => 'post',
                'uri' => 'server/{uuid}/test_connection',
                'permission' => 'configuration',
                'name' => 'server.test_connection.post',
                'controller' => 'puqNextcloudController@postServerTestConnection',
            ],
            [
                'method' => 'get',
                'uri' => 'server_test_connection',
                'permission' => 'configuration',
                'name' => 'server.test_connection.get',
                'controller' => 'puqNextcloudController@getServerTestConnection',
            ],
            [
                'method' => 'get',
                'uri' => 'service/{uuid}/user_quota',
                'permission' => 'configuration',
                'name' => 'service.user_quota.get',
                'controller' => 'puqNextcloudController@getServiceUserQuota',
            ],

        ];
    }

    public function getClientAreaMenuConfig(): array
    {
        return [
            'general' => [
                'name' => 'General',
                'template' => 'client_area.general',
            ],
        ];
    }

    public function variables_general(): array
    {
        $server = puqNextcloudServer::find($this->service_data['server_uuid']);
        $data['server_url'] = '';
        if ($server) {

            if ($server->ssl) {
                $data['server_url'] = 'https://'.$server->host;
                if ($server->port !== 443) {
                    $data['server_url'] .= ':'.$server->port;
                }
            } else {
                $data['server_url'] = 'http://'.$server->host;
                if ($server->port !== 80) {
                    $data['server_url'] .= ':'.$server->port;
                }
            }
            $data['server_url'] .= '/';
        }

        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;

        return $data;
    }

    public function controllerClient_user_quota(Request $request): JsonResponse
    {

        if (empty($this->service_data['username']) or empty($this->service_data['server_uuid'])) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqNextcloud.Something went wrong try again later')],
            ], 422);
        }

        $server = PuqNextcloudServer::query()->find($this->service_data['server_uuid']);

        if (empty($server)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqNextcloud.Something went wrong try again later')],
            ], 422);
        }

        $nextcloud = new puqNextcloudClient($server->toArray() ?? []);

        $response = $nextcloud->apiRequest('/cloud/users/'.$this->service_data['username'], 'GET', []);

        if ($response['status'] == 'success') {
            return response()->json([
                'status' => 'success',
                'data' => $response['data']['quota'],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'errors' => [__('Product.puqNextcloud.Something went wrong try again later')],
        ], 422);
    }
}
