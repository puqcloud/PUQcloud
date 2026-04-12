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

namespace Modules\Product\puqProxmox\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmAccessServer;
use Modules\Product\puqProxmox\Models\PuqPmCluster;
use Modules\Product\puqProxmox\Models\PuqPmClusterGroup;
use Modules\Product\puqProxmox\Models\PuqPmIpPool;
use Modules\Product\puqProxmox\Models\PuqPmMacPool;
use Modules\Product\puqProxmox\Models\PuqPmNode;
use Modules\Product\puqProxmox\Models\PuqPmPrivateNetwork;
use Modules\Product\puqProxmox\Models\PuqPmPublicNetwork;
use Modules\Product\puqProxmox\Models\PuqPmStorage;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class puqPmClusterController extends Controller
{
    public function clusters(Request $request): View
    {
        $title = __('Product.puqProxmox.Clusters');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.clusters.clusters', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.clusters.get',
        description: 'Returns a list of clusters.',
        summary: 'Get clusters list',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Clusters list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClusters(Request $request): JsonResponse
    {
        $query = PuqPmCluster::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('cluster_group', function ($model) {
                    $cluster_group = $model->puqPmClusterGroup;

                    return $cluster_group->name;
                })
                ->addColumn('use_accounts', function ($model) {
                    return $model->getUseAccounts();
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route(
                        'admin.web.Product.puqProxmox.cluster.tab',
                        ['uuid' => $model->uuid, 'tab' => 'general']
                    );

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function clusterTab(Request $request, $uuid, $tab): View|RedirectResponse
    {

        $cluster = PuqPmCluster::findOrFail($uuid);

        $validTabs = [
            'general',
            'nodes',
            'storages',
            'public_networks',
            'private_networks',
            'env_variables'
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route(
                'admin.web.Product.puqProxmox.cluster.tab',
                ['uuid' => $cluster->uuid, 'tab' => 'general']
            );
        }

        $title = $cluster->name;

        return view_admin_module(
            'Product',
            'puqProxmox',
            'admin_area.clusters.cluster_'.$tab,
            compact('title', 'uuid', 'tab', 'cluster')
        );
    }

    #[OA\Post(
        path: 'admin.api.Product.puqProxmox.cluster.post',
        description: 'Creates a new cluster.',
        summary: 'Create cluster',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cluster created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postCluster(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_pm_clusters,name',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmCluster;
        $model->name = $request->input('name');

        $cluster_group = PuqPmClusterGroup::query()->first();

        $model->puq_pm_cluster_group_uuid = $cluster_group->uuid;

        if ($cluster_group->puqPmClusters()->count() == 0) {
            $model->default = true;
        }
        $model->disable = true;

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.cluster.get',
        description: 'Returns details of a specific cluster.',
        summary: 'Get cluster details',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the cluster',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cluster details returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getCluster(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $cluster_group = $model->puqPmClusterGroup;

        $data['puq_pm_cluster_group_data'] = [
            'id' => $cluster_group->uuid,
            'text' => $cluster_group->name,
        ];
        $data['description'] = json_decode($model->description, true);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqProxmox.cluster.put',
        description: 'Updates a specific cluster.',
        summary: 'Update cluster',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the cluster',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'puq_pm_cluster_group_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'default', type: 'string'),
                    new OA\Property(property: 'disable', type: 'string'),
                    new OA\Property(property: 'vncwebproxy_domain', type: 'string'),
                    new OA\Property(property: 'vncwebproxy_api_key', type: 'string'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cluster updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putCluster(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_pm_clusters,name,'.$uuid.',uuid',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->vncwebproxy_domain = $request->input('vncwebproxy_domain');
        $model->vncwebproxy_api_key = $request->input('vncwebproxy_api_key');

        $cluster_group = PuqPmClusterGroup::find($request->input('puq_pm_cluster_group_uuid'));

        if (empty($cluster_group)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $model->puq_pm_cluster_group_uuid = $cluster_group->uuid;

        $model->default = false;
        if ($request->input('default') == 'yes') {
            $model->default = true;
        }

        $model->disable = false;
        if ($request->input('disable') == 'yes') {
            $model->disable = true;
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqProxmox.cluster.env_variables.put',
        description: 'Updates environment variables for a specific cluster.',
        summary: 'Update cluster environment variables',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the cluster',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'env_variables', type: 'string', description: 'JSON-encoded array of environment variables'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Environment variables updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putClusterEnvVariables(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $envVariables = $request->input('env_variables');
        $decodedEnv = [];

        if (!empty($envVariables)) {
            $decodedEnv = json_decode($envVariables, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedEnv)) {
                return response()->json([
                    'message' => ['env_variables' => [__('Product.puqProxmox.Environment Variables must be valid JSON array')]],
                ], 422);
            }

            $keys = [];
            foreach ($decodedEnv as $index => $item) {
                $key = $item['key'] ?? null;
                if (empty($key)) {
                    return response()->json([
                        'message' => [
                            'env_variables' => [
                                __('Product.puqProxmox.Environment Variable key is required at index :index',
                                    ['index' => $index]),
                            ],
                        ],
                    ], 422);
                }
                if (in_array($key, $keys)) {
                    return response()->json([
                        'message' => [
                            'env_variables' => [
                                __('Product.puqProxmox.Duplicate Environment Variable key ":key"', ['key' => $key]),
                            ],
                        ],
                    ], 422);
                }
                $keys[] = $key;
            }
        }
        $model->env_variables = $decodedEnv;

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.Product.puqProxmox.cluster.delete',
        description: 'Deletes a specific cluster.',
        summary: 'Delete cluster',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the cluster',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cluster deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteCluster(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'redirect' => route('admin.web.Product.puqProxmox.clusters'),
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.cluster.vncwebproxy_test_connection.get',
        description: 'Tests the VNC web proxy connection for a specific cluster.',
        summary: 'Test VNC web proxy connection',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the cluster',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection test result returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClusterVncwebproxyTestConnection(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->vncwebproxyTestConnection();
        if ($data['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $data['errors'],
            ], 412);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data['data'],
        ]);
    }

    // Access Servers ------------------------------------------------------------------------------
    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.cluster.access_servers.get',
        description: 'Returns access servers for a specific cluster.',
        summary: 'Get cluster access servers',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the cluster',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Access servers list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClusterAccessServers(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmAccessServers();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhere('ssh_host', 'like', "%{$search}%")
                                ->orWhere('ssh_username', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['test_connection'] = route('admin.api.Product.puqProxmox.access_server.test_connection.get',
                        $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.access_server.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.Product.puqProxmox.access_server.post',
        description: 'Creates a new access server.',
        summary: 'Create access server',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'ssh_host', 'ssh_port', 'ssh_username', 'ssh_password', 'puq_pm_cluster_uuid', 'api_port', 'api_host', 'api_token', 'api_token_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'ssh_host', type: 'string'),
                    new OA\Property(property: 'ssh_port', type: 'integer'),
                    new OA\Property(property: 'ssh_username', type: 'string'),
                    new OA\Property(property: 'ssh_password', type: 'string'),
                    new OA\Property(property: 'puq_pm_cluster_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'api_port', type: 'integer'),
                    new OA\Property(property: 'api_host', type: 'string'),
                    new OA\Property(property: 'api_token', type: 'string'),
                    new OA\Property(property: 'api_token_id', type: 'string'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Access server created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postAccessServer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:puq_pm_access_servers,name',
            'description' => 'nullable|string|max:255',
            'ssh_host' => [
                'required',
                'string',
                'regex:/^([a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/',
            ],
            'ssh_port' => 'required|integer|min:1|max:65535',
            'ssh_username' => 'required|string|max:255',
            'ssh_password' => 'required|string|min:1|max:255',
            'puq_pm_cluster_uuid' => 'required|uuid|exists:puq_pm_clusters,uuid',
            'api_port' => 'required|integer|min:1|max:65535',
            'api_host' => [
                'required',
                'string',
                'regex:/^([a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/',
            ],
            'api_token' => 'required|string|min:1|max:255',
            'api_token_id' => [
                'required',
                'regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+![a-zA-Z0-9._-]+$/',
            ],
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'ssh_host.required' => __('Product.puqProxmox.The SSH Host is required'),
            'ssh_host.regex' => __('Product.puqProxmox.The SSH Host must be a valid IP or domain name'),
            'ssh_port.required' => __('Product.puqProxmox.The SSH Port is required'),
            'ssh_port.integer' => __('Product.puqProxmox.The SSH Port must be a number'),
            'ssh_username.required' => __('Product.puqProxmox.The SSH Username is required'),
            'ssh_password.required' => __('Product.puqProxmox.The SSH Password is required'),
            'puq_pm_cluster_uuid.required' => __('Product.puqProxmox.The Cluster UUID is required'),
            'puq_pm_cluster_uuid.exists' => __('Product.puqProxmox.The selected cluster does not exist'),
            'api_host.required' => __('Product.puqProxmox.The API Host is required'),
            'api_host.regex' => __('Product.puqProxmox.The API Host must be a valid IP or domain name'),
            'api_port.required' => __('Product.puqProxmox.The API Port is required'),
            'api_port.integer' => __('Product.puqProxmox.The API Port must be a number'),
            'api_token.required' => __('Product.puqProxmox.The API Token is required'),
            'api_token_id.regex' => __('Product.puqProxmox.Invalid Proxmox token format. Expected format: username@realm!tokenid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $cluster = PuqPmCluster::find($request->input('puq_pm_cluster_uuid'));

        if (empty($cluster)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $model = new PuqPmAccessServer;
        $model->name = $request->input('name');
        $model->description = $request->input('description');
        $model->ssh_host = $request->input('ssh_host');
        $model->ssh_username = $request->input('ssh_username');
        $model->ssh_port = $request->input('ssh_port');
        $model->ssh_password = Crypt::encryptString($request->input('ssh_password'));
        $model->puq_pm_cluster_uuid = $request->input('puq_pm_cluster_uuid');

        $model->api_port = $request->input('api_port');
        $model->api_host = $request->input('api_host');
        $model->api_token = Crypt::encryptString($request->input('api_token'));
        $model->api_token_id = $request->input('api_token_id');

        // API and SSH
        $test_connection = $model->testConnection();

        if ($test_connection['status'] != 'success') {
            return response()->json([
                'status' => 'error',
                'errors' => $test_connection['errors'],
            ], 502);
        }

        if ($cluster->puqPmAccessServers()->count() == 0 or empty($cluster->description)) {
            $cluster->description = json_encode($test_connection['data']);
            $cluster->save();
        } else {
            $description = json_decode($cluster->description, true);

            if (!is_array($description) || empty($description['cluster'])) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.Cluster is not created — only one node is allowed')],
                ], 422);
            }

            if ($description['cluster'] !== $test_connection['data']['cluster']) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.This node does not belong to an already added cluster')],
                ], 409);
            }
        }

        $model->api_response_time = $test_connection['data']['api_response_time'];
        $model->ssh_response_time = $test_connection['data']['ssh_response_time'];

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.Product.puqProxmox.access_server.delete',
        description: 'Deletes a specific access server.',
        summary: 'Delete access server',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the access server',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Access server deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteAccessServer(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAccessServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.access_server.test_connection.get',
        description: 'Tests the connection to a specific access server.',
        summary: 'Test access server connection',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the access server',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection test result returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getAccessServerTestConnection(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAccessServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $test_connection = $model->testConnection();

        if ($test_connection['status'] == 'success') {

            $model->api_response_time = $test_connection['data']['api_response_time'];
            $model->ssh_response_time = $test_connection['data']['ssh_response_time'];
            $model->save();

            return response()->json([
                'status' => 'success',
                'message' => __('Product.puqProxmox.Successfully'),
                'data' => $test_connection['data'],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'errors' => $test_connection['errors'],
        ], 502);
    }

    // Nodes --------------------------------------------------------------------------------------
    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.cluster.nodes.get',
        description: 'Returns nodes for a specific cluster.',
        summary: 'Get cluster nodes',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the cluster',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nodes list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClusterNodes(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmNodes();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('status', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('tags', function ($model) {
                    return $model->puqPmTags()->pluck('name')->toArray();
                })
                ->addColumn('model', function ($model) {
                    return class_basename(get_class($model));
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['delete'] = route('admin.api.Product.puqProxmox.node.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Delete(
        path: 'admin.api.Product.puqProxmox.node.delete',
        description: 'Deletes a specific node.',
        summary: 'Delete node',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the node',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Node deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteNode(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmNode::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    // Storages -----------------------------------------------------------------------------------
    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.cluster.storages.get',
        description: 'Returns storages for a specific cluster.',
        summary: 'Get cluster storages',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the cluster',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Storages list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClusterStorages(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmStorages()
            ->join('puq_pm_nodes', 'puq_pm_nodes.uuid', '=', 'puq_pm_storages.puq_pm_node_uuid')
            ->select('puq_pm_storages.*', 'puq_pm_nodes.name as node_name');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_storages.name', 'like', "%{$search}%")
                                ->orWhere('puq_pm_storages.status', 'like', "%{$search}%")
                                ->orWhere('puq_pm_storages.plugintype', 'like', "%{$search}%")
                                ->orWhere('puq_pm_storages.content', 'like', "%{$search}%")
                                ->orWhere('puq_pm_nodes.name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('tags', function ($model) {
                    return $model->puqPmTags()->pluck('name')->toArray();
                })
                ->addColumn('model', function ($model) {
                    return class_basename(get_class($model));
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['delete'] = route('admin.api.Product.puqProxmox.storage.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Delete(
        path: 'admin.api.Product.puqProxmox.storage.delete',
        description: 'Deletes a specific storage.',
        summary: 'Delete storage',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the storage',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Storage deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteStorage(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmStorage::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    // Public Networks ----------------------------------------------------------------------------
    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.cluster.public_networks.get',
        description: 'Returns public networks for a specific cluster.',
        summary: 'Get cluster public networks',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the cluster', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Public networks list returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object')),
        ]
    )]
    public function getClusterPublicNetworks(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmPublicNetworks()
            ->leftJoin('puq_pm_mac_pools', 'puq_pm_mac_pools.uuid', '=', 'puq_pm_public_networks.puq_pm_mac_pool_uuid')
            ->leftJoin('puq_pm_ip_pools', 'puq_pm_ip_pools.uuid', '=', 'puq_pm_public_networks.puq_pm_ip_pool_uuid')
            ->select(
                'puq_pm_public_networks.*',
                'puq_pm_ip_pools.name as ip_pool_name',
                'puq_pm_ip_pools.first_ip as first_ip',
                'puq_pm_ip_pools.last_ip as last_ip',
                'puq_pm_mac_pools.name as mac_pool_name',
                'puq_pm_mac_pools.first_mac as first_mac',
                'puq_pm_mac_pools.last_mac as last_mac',
            );

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_public_networks.name', 'like', "%{$search}%")
                                ->orWhere('puq_pm_public_networks.bridge', 'like', "%{$search}%")
                                ->orWhere('puq_pm_ip_pools.name', 'like', "%{$search}%")
                                ->orWhere('puq_pm_ip_pools.first_ip', 'like', "%{$search}%")
                                ->orWhere('puq_pm_ip_pools.last_ip', 'like', "%{$search}%")
                                ->orWhere('puq_pm_mac_pools.name', 'like', "%{$search}%")
                                ->orWhere('puq_pm_mac_pools.first_mac', 'like', "%{$search}%")
                                ->orWhere('puq_pm_mac_pools.last_mac', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('tags', function ($model) {
                    return $model->puqPmTags()->pluck('name')->toArray();
                })
                ->addColumn('model', function ($model) {
                    return class_basename(get_class($model));
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.api.Product.puqProxmox.public_network.get', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.public_network.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.public_network.get',
        description: 'Returns details of a specific public network.',
        summary: 'Get public network details',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the public network', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Public network details returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'object')], type: 'object')),
        ]
    )]
    public function getPublicNetwork(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmPublicNetwork::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();

        $ip_pool = $model->puqPmIpPool;

        $data['puq_pm_ip_pool_data'] = [
            'id' => '0',
            'text' => __('Product.puqProxmox.No choice'),
        ];

        if ($ip_pool) {
            $data['puq_pm_ip_pool_data'] = [
                'id' => $ip_pool->uuid,
                'text' => $ip_pool->name.' ('.$ip_pool->first_ip.'-'.$ip_pool->last_ip.')',
            ];
        }

        $mac_pool = $model->puqPmMacPool;

        if ($mac_pool) {
            $data['puq_pm_mac_pool_data'] = [
                'id' => $mac_pool->uuid,
                'text' => $mac_pool->name.' ('.$mac_pool->first_mac.'-'.$mac_pool->last_mac.')',
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    #[OA\Post(
        path: 'admin.api.Product.puqProxmox.public_network.post',
        description: 'Creates a new public network.',
        summary: 'Create public network',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'puq_pm_cluster_uuid', 'puq_pm_mac_pool_uuid', 'bridge', 'vlan_tag'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'puq_pm_cluster_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'puq_pm_mac_pool_uuid', type: 'string'),
                    new OA\Property(property: 'puq_pm_ip_pool_uuid', type: 'string'),
                    new OA\Property(property: 'bridge', type: 'string'),
                    new OA\Property(property: 'vlan_tag', type: 'integer'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Public network created successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object')], type: 'object')),
        ]
    )]
    public function postPublicNetwork(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:puq_pm_public_networks,name',
            'puq_pm_cluster_uuid' => 'required|uuid|exists:puq_pm_clusters,uuid',
            'puq_pm_mac_pool_uuid' => [
                'required', 'string', function ($attribute, $value, $fail) {
                    if ($value !== '0' && !PuqPmMacPool::where('uuid', $value)->exists()) {
                        $fail(__('Product.puqProxmox.Selected MAC pool does not exist'));
                    }
                },
            ],
            'puq_pm_ip_pool_uuid' => [
                'nullable', 'string', function ($attribute, $value, $fail) {
                    if ($value !== '0' && !PuqPmIpPool::where('uuid', $value)->exists()) {
                        $fail(__('Product.puqProxmox.Selected IP pool does not exist'));
                    }
                },
            ],
            'bridge' => 'required|string|max:255',
            'vlan_tag' => 'required|integer|min:0|max:4095',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'puq_pm_cluster_uuid.required' => __('Product.puqProxmox.Cluster is required'),
            'puq_pm_cluster_uuid.exists' => __('Product.puqProxmox.Selected cluster does not exist'),
            'bridge.required' => __('Product.puqProxmox.Bridge is required'),
            'vlan_tag.required' => __('Product.puqProxmox.VLAN tag is required'),
            'vlan_tag.integer' => __('Product.puqProxmox.VLAN tag must be an integer'),
            'vlan_tag.min' => __('Product.puqProxmox.VLAN tag must be at least 0'),
            'vlan_tag.max' => __('Product.puqProxmox.VLAN tag must be no more than 4095'),
            'puq_pm_mac_pool_uuid.required' => __('Product.puqProxmox.The MAC Pool field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmPublicNetwork;
        $model->name = $request->input('name');
        $model->puq_pm_cluster_uuid = $request->input('puq_pm_cluster_uuid');
        $model->puq_pm_mac_pool_uuid = $request->input('puq_pm_mac_pool_uuid');

        $model->puq_pm_ip_pool_uuid = null;
        if ($request->input('puq_pm_ip_pool_uuid') !== '0') {
            $model->puq_pm_ip_pool_uuid = $request->input('puq_pm_ip_pool_uuid');
        }

        $model->bridge = $request->input('bridge');
        $model->vlan_tag = (int) $request->input('vlan_tag');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqProxmox.public_network.put',
        description: 'Updates a specific public network.',
        summary: 'Update public network',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the public network', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'Public network updated successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object')], type: 'object')),
        ]
    )]
    public function putPublicNetwork(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmPublicNetwork::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:puq_pm_public_networks,name,'.$uuid.',uuid',
            'puq_pm_cluster_uuid' => 'required|uuid|exists:puq_pm_clusters,uuid',
            'puq_pm_mac_pool_uuid' => [
                'required', 'string', function ($attribute, $value, $fail) {
                    if ($value !== '0' && !PuqPmMacPool::where('uuid', $value)->exists()) {
                        $fail(__('Product.puqProxmox.Selected MAC pool does not exist'));
                    }
                },
            ],
            'puq_pm_ip_pool_uuid' => [
                'nullable', 'string', function ($attribute, $value, $fail) {
                    if ($value !== '0' && !PuqPmIpPool::where('uuid', $value)->exists()) {
                        $fail(__('Product.puqProxmox.Selected IP pool does not exist'));
                    }
                },
            ],
            'bridge' => 'required|string|max:255',
            'vlan_tag' => 'required|integer|min:0|max:4095',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'puq_pm_cluster_uuid.required' => __('Product.puqProxmox.Cluster is required'),
            'puq_pm_cluster_uuid.exists' => __('Product.puqProxmox.Selected cluster does not exist'),
            'bridge.required' => __('Product.puqProxmox.Bridge is required'),
            'vlan_tag.required' => __('Product.puqProxmox.VLAN tag is required'),
            'vlan_tag.integer' => __('Product.puqProxmox.VLAN tag must be an integer'),
            'vlan_tag.min' => __('Product.puqProxmox.VLAN tag must be at least 0'),
            'vlan_tag.max' => __('Product.puqProxmox.VLAN tag must be no more than 4095'),
            'puq_pm_mac_pool_uuid.required' => __('Product.puqProxmox.The MAC Pool field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->puq_pm_cluster_uuid = $request->input('puq_pm_cluster_uuid');
        $model->puq_pm_mac_pool_uuid = $request->input('puq_pm_mac_pool_uuid');


        $model->puq_pm_ip_pool_uuid = null;
        if ($request->input('puq_pm_ip_pool_uuid') !== '0') {
            $model->puq_pm_ip_pool_uuid = $request->input('puq_pm_ip_pool_uuid');
        }

        $model->bridge = $request->input('bridge');
        $model->vlan_tag = (int) $request->input('vlan_tag');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.Product.puqProxmox.public_network.delete',
        description: 'Deletes a specific public network.',
        summary: 'Delete public network',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the public network', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Public network deleted successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object')),
        ]
    )]
    public function deletePublicNetwork(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmPublicNetwork::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    // Private Networks ----------------------------------------------------------------------------
    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.cluster.private_networks.get',
        description: 'Returns private networks for a specific cluster.',
        summary: 'Get cluster private networks',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the cluster', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Private networks list returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object')),
        ]
    )]
    public function getClusterPrivateNetworks(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmPrivateNetworks()
            ->leftJoin('puq_pm_mac_pools', 'puq_pm_mac_pools.uuid', '=', 'puq_pm_private_networks.puq_pm_mac_pool_uuid')
            ->select(
                'puq_pm_private_networks.*',
                'puq_pm_mac_pools.name as mac_pool_name',
                'puq_pm_mac_pools.first_mac as first_mac',
                'puq_pm_mac_pools.last_mac as last_mac',
            );

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_private_networks.name', 'like', "%{$search}%")
                                ->orWhere('puq_pm_private_networks.bridge', 'like', "%{$search}%")
                                ->orWhere('puq_pm_private_networks.type', 'like', "%{$search}%")
                                ->orWhere('puq_pm_mac_pools.name', 'like', "%{$search}%")
                                ->orWhere('puq_pm_mac_pools.first_mac', 'like', "%{$search}%")
                                ->orWhere('puq_pm_mac_pools.last_mac', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('tags', function ($model) {
                    return $model->puqPmTags()->pluck('name')->toArray();
                })
                ->addColumn('model', function ($model) {
                    return class_basename(get_class($model));
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.api.Product.puqProxmox.private_network.get', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.private_network.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.private_network.get',
        description: 'Returns details of a specific private network.',
        summary: 'Get private network details',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the private network', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Private network details returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'object')], type: 'object')),
        ]
    )]
    public function getPrivateNetwork(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmPrivateNetwork::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();

        $mac_pool = $model->puqPmMacPool;

        if ($mac_pool) {
            $data['puq_pm_mac_pool_data'] = [
                'id' => $mac_pool->uuid,
                'text' => $mac_pool->name.' ('.$mac_pool->first_mac.'-'.$mac_pool->last_mac.')',
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    #[OA\Post(
        path: 'admin.api.Product.puqProxmox.private_network.post',
        description: 'Creates a new private network.',
        summary: 'Create private network',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name', 'puq_pm_cluster_uuid', 'puq_pm_mac_pool_uuid', 'bridge', 'type'], properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'puq_pm_cluster_uuid', type: 'string', format: 'uuid'), new OA\Property(property: 'puq_pm_mac_pool_uuid', type: 'string'), new OA\Property(property: 'bridge', type: 'string'), new OA\Property(property: 'type', type: 'string')], type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'Private network created successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object')], type: 'object')),
        ]
    )]
    public function postPrivateNetwork(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:puq_pm_private_networks,name',
            'puq_pm_cluster_uuid' => 'required|uuid|exists:puq_pm_clusters,uuid',
            'puq_pm_mac_pool_uuid' => [
                'required', 'string', function ($attribute, $value, $fail) {
                    if ($value !== '0' && !PuqPmMacPool::where('uuid', $value)->exists()) {
                        $fail(__('Product.puqProxmox.Selected MAC pool does not exist'));
                    }
                },
            ],
            'bridge' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'puq_pm_cluster_uuid.required' => __('Product.puqProxmox.Cluster is required'),
            'puq_pm_cluster_uuid.exists' => __('Product.puqProxmox.Selected cluster does not exist'),
            'bridge.required' => __('Product.puqProxmox.Bridge is required'),
            'type.required' => __('Product.puqProxmox.Type is required'),
            'puq_pm_mac_pool_uuid.required' => __('Product.puqProxmox.The MAC Pool field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmPrivateNetwork();
        $model->name = $request->input('name');
        $model->puq_pm_cluster_uuid = $request->input('puq_pm_cluster_uuid');
        $model->type = $request->input('type');
        $model->puq_pm_mac_pool_uuid = $request->input('puq_pm_mac_pool_uuid');
        $model->bridge = $request->input('bridge');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqProxmox.private_network.put',
        description: 'Updates a specific private network.',
        summary: 'Update private network',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the private network', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'Private network updated successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object')], type: 'object')),
        ]
    )]
    public function putPrivateNetwork(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmPrivateNetwork::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:puq_pm_private_networks,name,'.$uuid.',uuid',
            'puq_pm_cluster_uuid' => 'required|uuid|exists:puq_pm_clusters,uuid',
            'puq_pm_mac_pool_uuid' => [
                'required', 'string', function ($attribute, $value, $fail) {
                    if ($value !== '0' && !PuqPmMacPool::where('uuid', $value)->exists()) {
                        $fail(__('Product.puqProxmox.Selected MAC pool does not exist'));
                    }
                },
            ],
            'bridge' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'puq_pm_cluster_uuid.required' => __('Product.puqProxmox.Cluster is required'),
            'puq_pm_cluster_uuid.exists' => __('Product.puqProxmox.Selected cluster does not exist'),
            'bridge.required' => __('Product.puqProxmox.Bridge is required'),
            'type.required' => __('Product.puqProxmox.Type is required'),
            'puq_pm_mac_pool_uuid.required' => __('Product.puqProxmox.The MAC Pool field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->puq_pm_cluster_uuid = $request->input('puq_pm_cluster_uuid');
        $model->puq_pm_mac_pool_uuid = $request->input('puq_pm_mac_pool_uuid');

        $model->type = $request->input('type');
        $model->bridge = $request->input('bridge');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.Product.puqProxmox.private_network.delete',
        description: 'Deletes a specific private network.',
        summary: 'Delete private network',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the private network', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Private network deleted successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object')),
        ]
    )]
    public function deletePrivateNetwork(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmPrivateNetwork::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }


    // Sync ----------------------------------------------------------------------------------------

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.sync.cluster.info.get',
        description: 'Synchronizes cluster info from Proxmox.',
        summary: 'Sync cluster info',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the cluster', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cluster info synced successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object')),
        ]
    )]
    public function getSyncClusterInfo(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($model->puqPmAccessServers()->count() == 0) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.There are no Access Servers')],
            ], 500);
        }
        $response = $model->getSyncClusterInfo();
        if ($response['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $response['errors'],
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.sync.cluster.nodes.get',
        description: 'Synchronizes cluster nodes from Proxmox.',
        summary: 'Sync cluster nodes',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the cluster', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cluster nodes synced successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object')),
        ]
    )]
    public function getSyncClusterNodes(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($model->puqPmAccessServers()->count() == 0) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.There are no Access Servers')],
            ], 500);
        }
        $response = $model->getSyncClusterNodes();
        if ($response['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $response['errors'],
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.sync.cluster.storages.get',
        description: 'Synchronizes cluster storages from Proxmox.',
        summary: 'Sync cluster storages',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the cluster', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cluster storages synced successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object')),
        ]
    )]
    public function getSyncClusterStorages(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($model->puqPmAccessServers()->count() == 0) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.There are no Access Servers')],
            ], 500);
        }
        $response = $model->getSyncClusterStorages();
        if ($response['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $response['errors'],
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.sync.cluster.storages.sync_templates.get',
        description: 'Synchronizes LXC templates to cluster storages.',
        summary: 'Sync templates to cluster storages',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the cluster', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Templates syncing started successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object')),
        ]
    )]
    public function getSyncClusterStoragesSyncTemplates(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmCluster::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($model->puqPmAccessServers()->count() == 0) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.There are no Access Servers')],
            ], 500);
        }
        $response = $model->syncLxcTemplatesToStorages();
        if ($response['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $response['errors'],
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Templates syncing started successfully!'),
        ]);
    }

}
