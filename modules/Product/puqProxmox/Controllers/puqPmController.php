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
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class puqPmController extends Controller
{

    public function settings(Request $request): View
    {
        $title = __('Product.puqProxmox.Settings');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.settings.settings', compact('title'));
    }


    public function getSettings(Request $request): JsonResponse
    {
        $settings = [
            'global_private_network' => SettingService::get('Product.puqProxmox.global_private_network'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $settings,
        ]);
    }


    public function putSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'global_private_network' => [
                'required',
                'regex:/^((25[0-5]|2[0-4][0-9]|1?[0-9]{1,2})\.){3}(25[0-5]|2[0-4][0-9]|1?[0-9]{1,2})\/([0-9]|[1-2][0-9]|3[0-2])$/',
            ],
        ], [
            'global_private_network.required' => __('Product.puqProxmox.The Global Private Network field is required'),
            'global_private_network.regex' => __('Product.puqProxmox.The Global Private Network must be a valid CIDR, e.g., 192.168.0.0/24'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        SettingService::set('Product.puqProxmox.global_private_network', $request->get('global_private_network'));

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Saved successfully'),
        ]);
    }





//    public function clusters(Request $request): View
//    {
//        $title = __('Product.puqProxmox.Clusters');
//
//        return view_admin_module('Product', 'puqProxmox', 'admin_area.clusters.clusters', compact('title'));
//    }
//
//    public function getClusters(Request $request): JsonResponse
//    {
//        $query = PuqPmCluster::query();
//
//        return response()->json([
//            'data' => DataTables::of($query)
//                ->filter(function ($query) use ($request) {
//                    if ($request->has('search') && !empty($request->search['value'])) {
//                        $search = $request->search['value'];
//                        $query->where(function ($q) use ($search) {
//                            $q->where('name', 'like', "%{$search}%");
//                        });
//                    }
//                })
//                ->addColumn('cluster_group', function ($model) {
//                    $cluster_group = $model->puqPmClusterGroup;
//                    return $cluster_group->name;
//                })
//                ->addColumn('use_accounts', function ($model) {
//                    return $model->getUseAccounts();
//                })
//                ->addColumn('urls', function ($model) {
//                    $urls = [];
//                    //$urls['edit'] = route('admin.web.Product.puqProxmox.server', $model->uuid);
//                    //$urls['delete'] = route('admin.api.Product.puqProxmox.server.delete', $model->uuid);
//                    return $urls;
//                })
//                ->make(true),
//        ], 200);
//    }
//
//    public function postServer(Request $request): JsonResponse
//    {
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|unique:puq_Proxmox_servers,name',
//        ], [
//            'name.required' => __('Product.puqProxmox.The Name field is required'),
//            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json([
//                'message' => $validator->errors(),
//            ], 422);
//        }
//
//        $model = new PuqProxmoxServer;
//
//        if (!empty($request->input('name'))) {
//            $model->name = $request->input('name');
//        }
//
//        $group = PuqProxmoxServerGroup::query()->first();
//        $model->group_uuid = $group->uuid;
//        $model->host = '';
//        $model->username = '';
//        $model->password = '';
//
//        if ($group->puqProxmoxServers()->count() == 0) {
//            $model->default = true;
//        }
//        $model->active = false;
//
//        $model->save();
//        $model->refresh();
//
//        return response()->json([
//            'status' => 'success',
//            'message' => __('Product.puqProxmox.Created successfully'),
//            'data' => $model,
//        ]);
//    }
//
//    public function deleteServer(Request $request, $uuid): JsonResponse
//    {
//        $model = PuqProxmoxServer::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        try {
//            $deleted = $model->delete();
//            if (!$deleted) {
//                return response()->json([
//                    'errors' => [__('Product.puqProxmox.Deletion failed')],
//                ], 500);
//            }
//        } catch (\Exception $e) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Deletion failed:') . ' ' . $e->getMessage()],
//            ], 500);
//        }
//
//        return response()->json([
//            'status' => 'success',
//            'message' => __('Product.puqProxmox.Deleted successfully'),
//        ]);
//    }
//
//
//    // --------------------------------------------------------------------------------------------------
//    public function clusterGroups(Request $request): View
//    {
//        $title = __('Product.puqProxmox.Cluster Groups');
//
//        return view_admin_module('Product', 'puqProxmox', 'admin_area.cluster_groups.cluster_groups', compact('title'));
//    }
//
//
//    public function getClusterGroups(Request $request): JsonResponse
//    {
//        $query = PuqPmClusterGroup::query();
//
//        return response()->json([
//            'data' => DataTables::of($query)
//                ->filter(function ($query) use ($request) {
//                    if ($request->has('search') && !empty($request->search['value'])) {
//                        $search = $request->search['value'];
//                        $query->where(function ($q) use ($search) {
//                            $q->where('name', 'like', "%{$search}%")
//                                ->orWhere('uuid', 'like', "%{$search}%")
//                                ->orWhere('fill_type', 'like', "%{$search}%");
//                        });
//                    }
//                })
//                ->addColumn('servers', function ($model) {
//                    $servers = [];
//                    foreach ($model->puqProxmoxServers as $server) {
//                        $servers[] = [
//                            'name' => $server->name,
//                            'url' => route('admin.web.Product.puqProxmox.server', $server->uuid),
//                        ];
//                    }
//
//                    return $servers;
//                })
//                ->addColumn('urls', function ($model) {
//                    $urls = [];
//                    $urls['put'] = route('admin.api.Product.puqProxmox.server_group.put', $model->uuid);
//                    $urls['delete'] = route('admin.api.Product.puqProxmox.server_group.delete', $model->uuid);
//
//                    return $urls;
//                })
//                ->make(true),
//        ], 200);
//    }
//
//    public function getServerGroupsSelect(Request $request): JsonResponse
//    {
//        $search = $request->input('q');
//
//        if (!empty($search)) {
//            $models = PuqProxmoxServerGroup::query()->where('name', 'like', '%' . $search . '%')->get();
//        } else {
//            $models = PuqProxmoxServerGroup::query()->get();
//        }
//
//        $results = [];
//        foreach ($models->toArray() ?? [] as $model) {
//            $results[] = [
//                'id' => $model['uuid'],
//                'text' => $model['name'],
//            ];
//        }
//
//        return response()->json(['data' => [
//            'results' => $results,
//            'pagination' => [
//                'more' => false,
//            ],
//        ]], 200);
//    }
//
//    public function postClusterGroup(Request $request): JsonResponse
//    {
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|unique:puq_Proxmox_server_groups,name',
//        ], [
//            'name.required' => __('Product.puqProxmox.The Name field is required'),
//            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json([
//                'message' => $validator->errors(),
//            ], 422);
//        }
//
//        $model = new PuqPmClusterGroup();
//
//        if (!empty($request->input('name'))) {
//            $model->name = $request->input('name');
//        }
//        $model->fill_type = 'default';
//        if (!empty($request->input('fill_type'))) {
//            $model->fill_type = $request->input('fill_type');
//        }
//
//        $model->save();
//        $model->refresh();
//
//        return response()->json([
//            'status' => 'success',
//            'message' => __('Product.puqProxmox.Created successfully'),
//            'data' => $model,
//        ]);
//    }
//
//    public function getServerGroup(Request $request, $uuid): JsonResponse
//    {
//        $model = PuqProxmoxServerGroup::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        return response()->json([
//            'status' => 'success',
//            'data' => $model,
//        ]);
//    }
//
//    public function putServerGroup(Request $request, $uuid): JsonResponse
//    {
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|unique:puq_Proxmox_server_groups,name,' . $uuid . ',uuid',
//        ], [
//            'name.required' => __('Product.puqProxmox.The Name field is required'),
//            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json([
//                'message' => $validator->errors(),
//            ], 422);
//        }
//
//        $model = PuqProxmoxServerGroup::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        if (!empty($request->input('name'))) {
//            $model->name = $request->input('name');
//        }
//        $model->fill_type = 'default';
//        if (!empty($request->input('fill_type'))) {
//            $model->fill_type = $request->input('fill_type');
//        }
//
//        $model->save();
//        $model->refresh();
//
//        return response()->json([
//            'status' => 'success',
//            'message' => __('Product.puqProxmox.Updated successfully'),
//            'data' => $model,
//        ]);
//    }
//
//    public function deleteServerGroup(Request $request, $uuid): JsonResponse
//    {
//        $model = PuqProxmoxServerGroup::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        if ($model->puqProxmoxServers()->count() != 0) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Group has servers')],
//            ], 422);
//        }
//
//        if (PuqProxmoxServerGroup::query()->count() == 1) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.There must be at least one group left')],
//            ], 422);
//        }
//
//        try {
//            $deleted = $model->delete();
//            if (!$deleted) {
//                return response()->json([
//                    'errors' => [__('Product.puqProxmox.Deletion failed')],
//                ], 500);
//            }
//        } catch (\Exception $e) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Deletion failed:') . ' ' . $e->getMessage()],
//            ], 500);
//        }
//
//        return response()->json([
//            'status' => 'success',
//            'message' => __('Product.puqProxmox.Deleted successfully'),
//        ]);
//    }
//
//    public function server(Request $request, $uuid): View
//    {
//        $title = __('Product.puqProxmox.Server');
//
//        return view_admin_module('Product', 'puqProxmox', 'admin_area.server_edit', compact('title', 'uuid'));
//    }
//
//    public function getServer(Request $request, $uuid): JsonResponse
//    {
//        $model = PuqProxmoxServer::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        $model->password = '**********';
//
//        $group = $model->puqProxmoxServerGroup;
//        $model->group_data = ['id' => $group->uuid, 'text' => $group->name];
//
//        return response()->json([
//            'status' => 'success',
//            'data' => $model,
//        ]);
//    }
//
//    public function putServer(Request $request, $uuid): JsonResponse
//    {
//        $model = PuqProxmoxServer::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|unique:puq_Proxmox_servers,name,' . $model->uuid . ',uuid',
//            'username' => 'required',
//            'host' => 'required',
//            'max_accounts' => 'required|integer|min:0',
//            'port' => 'required|integer|min:1|max:65535',
//        ], [
//            'name.required' => __('Product.puqProxmox.The Name field is required'),
//            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
//            'username.required' => __('Product.puqProxmox.The Username field is required'),
//            'host.required' => __('Product.puqProxmox.The Host field is required'),
//            'max_accounts.required' => __('Product.puqProxmox.The Max Accounts field is required'),
//            'max_accounts.integer' => __('Product.puqProxmox.Max Accounts must be an integer'),
//            'max_accounts.min' => __('Product.puqProxmox.Max Accounts must be at least 0'),
//            'port.required' => __('Product.puqProxmox.The Port field is required'),
//            'port.integer' => __('Product.puqProxmox.Port must be an integer'),
//            'port.min' => __('Product.puqProxmox.Port must be at least 1'),
//            'port.max' => __('Product.puqProxmox.Port must be less than or equal to 65535'),
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json([
//                'message' => $validator->errors(),
//            ], 422);
//        }
//
//        $model->name = $request->input('name');
//        $model->host = $request->input('host');
//        $model->username = $request->input('username');
//        if ($request->input('password') != '**********') {
//            $model->password = Crypt::encryptString($request->input('password'));
//        }
//        $model->max_accounts = $request->input('max_accounts');
//        $model->port = $request->input('port');
//        $model->group_uuid = $request->input('group_uuid');
//
//        $model->ssl = false;
//        if ($request->input('ssl') == 'yes') {
//            $model->ssl = true;
//        }
//
//        $model->active = false;
//        if ($request->input('active') == 'yes') {
//            $model->active = true;
//        }
//
//        if ($request->input('default') == 'yes') {
//            $model->puqProxmoxServerGroup->puqProxmoxServers()->update(['default' => false]);
//            $model->default = true;
//        }
//
//        $model->save();
//
//        return response()->json([
//            'status' => 'success',
//            'message' => __('Product.puqProxmox.Updated successfully'),
//            'data' => $model,
//        ]);
//    }
//
//    public function postServerTestConnection(Request $request, $uuid): JsonResponse
//    {
//        $model = PuqProxmoxServer::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|unique:puq_Proxmox_servers,name,' . $model->uuid . ',uuid',
//            'username' => 'required',
//            'host' => 'required',
//            'max_accounts' => 'required|integer|min:0',
//            'port' => 'required|integer|min:1|max:65535',
//        ], [
//            'name.required' => __('Product.puqProxmox.The Name field is required'),
//            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
//            'username.required' => __('Product.puqProxmox.The Username field is required'),
//            'host.required' => __('Product.puqProxmox.The Host field is required'),
//            'max_accounts.required' => __('Product.puqProxmox.The Max Accounts field is required'),
//            'max_accounts.integer' => __('Product.puqProxmox.Max Accounts must be an integer'),
//            'max_accounts.min' => __('Product.puqProxmox.Max Accounts must be at least 0'),
//            'port.required' => __('Product.puqProxmox.The Port field is required'),
//            'port.integer' => __('Product.puqProxmox.Port must be an integer'),
//            'port.min' => __('Product.puqProxmox.Port must be at least 1'),
//            'port.max' => __('Product.puqProxmox.Port must be less than or equal to 65535'),
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json([
//                'message' => $validator->errors(),
//            ], 422);
//        }
//
//        $model->name = $request->input('name');
//        $model->host = $request->input('host');
//        $model->username = $request->input('username');
//        if ($request->input('password') != '**********') {
//            $model->password = Crypt::encryptString($request->input('password'));
//        }
//        $model->max_accounts = $request->input('max_accounts');
//        $model->port = $request->input('port');
//        $model->group_uuid = $request->input('group_uuid');
//
//        $model->ssl = false;
//        if ($request->input('ssl') == 'yes') {
//            $model->ssl = true;
//        }
//
//        $model->active = false;
//        if ($request->input('active') == 'yes') {
//            $model->active = true;
//        }
//
//        $Proxmox = new puqProxmoxClient(
//            $model->toArray() ?? [],
//        );
//
//        $response = $Proxmox->apiTestConnection();
//
//        if ($response['status'] != 'success') {
//            return response()->json([
//                'status' => 'error',
//                'errors' => [$response['error'] ?? ''],
//            ], 422);
//        }
//
//        return response()->json([
//            'status' => 'success',
//            'message' => __('Product.puqProxmox.Successfully'),
//            'data' => $response['data'],
//        ]);
//    }
//
//    public function getServerTestConnection(Request $request): JsonResponse
//    {
//
//        $uuid = $request->get('uuid');
//        $model = PuqProxmoxServer::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        $Proxmox = new puqProxmoxClient(
//            $model->toArray() ?? [],
//        );
//
//        $response = $Proxmox->apiTestConnection();
//
//        if ($response['status'] != 'success') {
//            return response()->json([
//                'status' => 'error',
//                'errors' => [$response['error'] ?? ''],
//            ], 422);
//        }
//
//        return response()->json([
//            'status' => 'success',
//            'data' => $response['data'],
//        ]);
//    }
//
//    public function getServiceUserQuota(Request $request, $uuid): JsonResponse
//    {
//        $model = Service::find($uuid);
//
//        if (empty($model)) {
//            return response()->json([
//                'errors' => [__('Product.puqProxmox.Not found')],
//            ], 404);
//        }
//
//        $service_data = $model->provision_data;
//        $server = PuqProxmoxServer::query()->find($service_data['server_uuid']);
//
//        if (!$server) {
//            return response()->json([
//                'status' => 'error',
//                'errors' => [__('Product.puqProxmox.Something went wrong try again later')],
//            ], 422);
//        }
//
//        $Proxmox = new puqProxmoxClient($server->toArray() ?? []);
//        $response = $Proxmox->apiRequest('/cloud/users/' . $service_data['username'], 'GET', []);
//
//        if ($response['status'] == 'success') {
//            return response()->json([
//                'status' => 'success',
//                'data' => $response['data']['quota'],
//            ]);
//        }
//
//        return response()->json([
//            'status' => 'error',
//            'errors' => [__('Product.puqProxmox.Something went wrong try again later')],
//        ], 422);
//
//    }
}
