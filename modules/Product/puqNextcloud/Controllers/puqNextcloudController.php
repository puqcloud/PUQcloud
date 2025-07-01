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

namespace Modules\Product\puqNextcloud\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqNextcloud\Models\PuqNextcloudServer;
use Modules\Product\puqNextcloud\Models\PuqNextcloudServerGroup;
use puqNextcloudClient;
use Yajra\DataTables\DataTables;

class puqNextcloudController extends Controller
{
    public function servers(Request $request): View
    {
        $title = __('Product.puqNextcloud.Servers');

        return view_admin_module('Product', 'puqNextcloud', 'admin_area.servers', compact('title'));
    }

    public function getServers(Request $request): JsonResponse
    {
        $query = PuqNextcloudServer::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('host', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('group', function ($model) {
                    $group = $model->puqNextcloudServerGroup;

                    return $group->name;
                })
                ->addColumn('use_accounts', function ($model) {
                    return $model->getUseAccounts();
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqNextcloud.server', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqNextcloud.server.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postServer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_nextcloud_servers,name',
        ], [
            'name.required' => __('Product.puqNextcloud.The Name field is required'),
            'name.unique' => __('Product.puqNextcloud.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqNextcloudServer;

        if (! empty($request->input('name'))) {
            $model->name = $request->input('name');
        }

        $group = PuqNextcloudServerGroup::query()->first();
        $model->group_uuid = $group->uuid;
        $model->host = '';
        $model->username = '';
        $model->password = '';

        if ($group->puqNextcloudServers()->count() == 0) {
            $model->default = true;
        }
        $model->active = false;

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqNextcloud.Created successfully'),
            'data' => $model,
        ]);
    }

    public function deleteServer(Request $request, $uuid): JsonResponse
    {
        $model = PuqNextcloudServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('Product.puqNextcloud.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqNextcloud.Deleted successfully'),
        ]);
    }

    public function serverGroups(Request $request): View
    {
        $title = __('Product.puqNextcloud.Server Groups');

        return view_admin_module('Product', 'puqNextcloud', 'admin_area.server_groups', compact('title'));
    }

    public function getServerGroups(Request $request): JsonResponse
    {
        $query = PuqNextcloudServerGroup::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('fill_type', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('servers', function ($model) {
                    $servers = [];
                    foreach ($model->puqNextcloudServers as $server) {
                        $servers[] = [
                            'name' => $server->name,
                            'url' => route('admin.web.Product.puqNextcloud.server', $server->uuid),
                        ];
                    }

                    return $servers;
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['put'] = route('admin.api.Product.puqNextcloud.server_group.put', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqNextcloud.server_group.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getServerGroupsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $models = PuqNextcloudServerGroup::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqNextcloudServerGroup::query()->get();
        }

        $results = [];
        foreach ($models->toArray() ?? [] as $model) {
            $results[] = [
                'id' => $model['uuid'],
                'text' => $model['name'],
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function postServerGroup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_nextcloud_server_groups,name',
        ], [
            'name.required' => __('Product.puqNextcloud.The Name field is required'),
            'name.unique' => __('Product.puqNextcloud.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqNextcloudServerGroup;

        if (! empty($request->input('name'))) {
            $model->name = $request->input('name');
        }
        $model->fill_type = 'default';
        if (! empty($request->input('fill_type'))) {
            $model->fill_type = $request->input('fill_type');
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqNextcloud.Created successfully'),
            'data' => $model,
        ]);
    }

    public function getServerGroup(Request $request, $uuid): JsonResponse
    {
        $model = PuqNextcloudServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model,
        ]);
    }

    public function putServerGroup(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_nextcloud_server_groups,name,'.$uuid.',uuid',
        ], [
            'name.required' => __('Product.puqNextcloud.The Name field is required'),
            'name.unique' => __('Product.puqNextcloud.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = PuqNextcloudServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        if (! empty($request->input('name'))) {
            $model->name = $request->input('name');
        }
        $model->fill_type = 'default';
        if (! empty($request->input('fill_type'))) {
            $model->fill_type = $request->input('fill_type');
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqNextcloud.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteServerGroup(Request $request, $uuid): JsonResponse
    {
        $model = PuqNextcloudServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        if ($model->puqNextcloudServers()->count() != 0) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Group has servers')],
            ], 422);
        }

        if (PuqNextcloudServerGroup::query()->count() == 1) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.There must be at least one group left')],
            ], 422);
        }

        try {
            $deleted = $model->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('Product.puqNextcloud.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqNextcloud.Deleted successfully'),
        ]);
    }

    public function server(Request $request, $uuid): View
    {
        $title = __('Product.puqNextcloud.Server');

        return view_admin_module('Product', 'puqNextcloud', 'admin_area.server_edit', compact('title', 'uuid'));
    }

    public function getServer(Request $request, $uuid): JsonResponse
    {
        $model = PuqNextcloudServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        $model->password = '**********';

        $group = $model->puqNextcloudServerGroup;
        $model->group_data = ['id' => $group->uuid, 'text' => $group->name];

        return response()->json([
            'status' => 'success',
            'data' => $model,
        ]);
    }

    public function putServer(Request $request, $uuid): JsonResponse
    {
        $model = PuqNextcloudServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_nextcloud_servers,name,'.$model->uuid.',uuid',
            'username' => 'required',
            'host' => 'required',
            'max_accounts' => 'required|integer|min:0',
            'port' => 'required|integer|min:1|max:65535',
        ], [
            'name.required' => __('Product.puqNextcloud.The Name field is required'),
            'name.unique' => __('Product.puqNextcloud.This Name is already taken'),
            'username.required' => __('Product.puqNextcloud.The Username field is required'),
            'host.required' => __('Product.puqNextcloud.The Host field is required'),
            'max_accounts.required' => __('Product.puqNextcloud.The Max Accounts field is required'),
            'max_accounts.integer' => __('Product.puqNextcloud.Max Accounts must be an integer'),
            'max_accounts.min' => __('Product.puqNextcloud.Max Accounts must be at least 0'),
            'port.required' => __('Product.puqNextcloud.The Port field is required'),
            'port.integer' => __('Product.puqNextcloud.Port must be an integer'),
            'port.min' => __('Product.puqNextcloud.Port must be at least 1'),
            'port.max' => __('Product.puqNextcloud.Port must be less than or equal to 65535'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->host = $request->input('host');
        $model->username = $request->input('username');
        if ($request->input('password') != '**********') {
            $model->password = Crypt::encryptString($request->input('password'));
        }
        $model->max_accounts = $request->input('max_accounts');
        $model->port = $request->input('port');
        $model->group_uuid = $request->input('group_uuid');

        $model->ssl = false;
        if ($request->input('ssl') == 'yes') {
            $model->ssl = true;
        }

        $model->active = false;
        if ($request->input('active') == 'yes') {
            $model->active = true;
        }

        if ($request->input('default') == 'yes') {
            $model->puqNextcloudServerGroup->puqNextcloudServers()->update(['default' => false]);
            $model->default = true;
        }

        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqNextcloud.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function postServerTestConnection(Request $request, $uuid): JsonResponse
    {
        $model = PuqNextcloudServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_nextcloud_servers,name,'.$model->uuid.',uuid',
            'username' => 'required',
            'host' => 'required',
            'max_accounts' => 'required|integer|min:0',
            'port' => 'required|integer|min:1|max:65535',
        ], [
            'name.required' => __('Product.puqNextcloud.The Name field is required'),
            'name.unique' => __('Product.puqNextcloud.This Name is already taken'),
            'username.required' => __('Product.puqNextcloud.The Username field is required'),
            'host.required' => __('Product.puqNextcloud.The Host field is required'),
            'max_accounts.required' => __('Product.puqNextcloud.The Max Accounts field is required'),
            'max_accounts.integer' => __('Product.puqNextcloud.Max Accounts must be an integer'),
            'max_accounts.min' => __('Product.puqNextcloud.Max Accounts must be at least 0'),
            'port.required' => __('Product.puqNextcloud.The Port field is required'),
            'port.integer' => __('Product.puqNextcloud.Port must be an integer'),
            'port.min' => __('Product.puqNextcloud.Port must be at least 1'),
            'port.max' => __('Product.puqNextcloud.Port must be less than or equal to 65535'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->host = $request->input('host');
        $model->username = $request->input('username');
        if ($request->input('password') != '**********') {
            $model->password = Crypt::encryptString($request->input('password'));
        }
        $model->max_accounts = $request->input('max_accounts');
        $model->port = $request->input('port');
        $model->group_uuid = $request->input('group_uuid');

        $model->ssl = false;
        if ($request->input('ssl') == 'yes') {
            $model->ssl = true;
        }

        $model->active = false;
        if ($request->input('active') == 'yes') {
            $model->active = true;
        }

        $nextcloud = new PuqNextcloudClient(
            $model->toArray() ?? [],
        );

        $response = $nextcloud->apiTestConnection();

        if ($response['status'] != 'success') {
            return response()->json([
                'status' => 'error',
                'errors' => [$response['error'] ?? ''],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqNextcloud.Successfully'),
            'data' => $response['data'],
        ]);
    }

    public function getServerTestConnection(Request $request): JsonResponse
    {

        $uuid = $request->get('uuid');
        $model = PuqNextcloudServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        $nextcloud = new PuqNextcloudClient(
            $model->toArray() ?? [],
        );

        $response = $nextcloud->apiTestConnection();

        if ($response['status'] != 'success') {
            return response()->json([
                'status' => 'error',
                'errors' => [$response['error'] ?? ''],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => $response['data'],
        ]);
    }

    public function getServiceUserQuota(Request $request, $uuid): JsonResponse
    {
        $model = Service::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqNextcloud.Not found')],
            ], 404);
        }

        $service_data = $model->provision_data;
        $server = PuqNextcloudServer::query()->find($service_data['server_uuid']);

        if (! $server) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqNextcloud.Something went wrong try again later')],
            ], 422);
        }

        $nextcloud = new puqNextcloudClient($server->toArray() ?? []);
        $response = $nextcloud->apiRequest('/cloud/users/'.$service_data['username'], 'GET', []);

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
