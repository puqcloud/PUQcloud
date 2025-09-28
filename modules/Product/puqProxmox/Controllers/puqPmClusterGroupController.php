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
use App\Models\HomeCompany;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmClusterGroup;
use Yajra\DataTables\DataTables;

class puqPmClusterGroupController extends Controller
{
    public function clusterGroups(Request $request): View
    {
        $title = __('Product.puqProxmox.Cluster Groups');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.cluster_groups.cluster_groups', compact('title'));
    }

    public function getClusterGroups(Request $request): JsonResponse
    {
        $query = PuqPmClusterGroup::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('fill_type', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('clusters', function ($model) {
                    $clusters = [];
                    foreach ($model->puqPmClusters as $cluster) {
                        $clusters[] = [
                            'name' => $cluster->name,
                            'url' => route('admin.web.Product.puqProxmox.cluster.tab',
                                ['uuid' => $cluster->uuid, 'tab' => 'general']),
                        ];
                    }

                    return $clusters;
                })
                ->addColumn('country', function ($model) {
                    return $model->country->name ?? '';
                })
                ->addColumn('region', function ($model) {
                    return $model->region->name ?? '';
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqProxmox.cluster_group.tab',
                        ['uuid' => $model->uuid, 'tab' => 'general']);

                    //$urls['delete'] = route('admin.api.Product.puqProxmox.cluster_group.delete', $model->uuid);
                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getClusterGroupsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (!empty($search)) {
            $models = PuqPmClusterGroup::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqPmClusterGroup::query()->get();
        }

        $results = [];
        foreach ($models->toArray() ?? [] as $model) {
            $results[] = [
                'id' => $model['uuid'],
                'text' => $model['name'],
            ];
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function postClusterGroup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_pm_cluster_groups,name',
            'fill_type' => 'required|in:default,lowest',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'fill_type.required' => __('Product.puqProxmox.The Fill Type field is required'),
            'fill_type.in' => __('Product.puqProxmox.The Fill Type must be default or lowest'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmClusterGroup();

        if (!empty($request->input('name'))) {
            $model->name = $request->input('name');
        }

        $model->fill_type = 'default';
        if (!empty($request->input('fill_type'))) {
            $model->fill_type = $request->input('fill_type');
        }

        $home_company = HomeCompany::query()->where('default', true)->first();
        $model->country_uuid = $home_company->country_uuid;
        $model->region_uuid = $home_company->region_uuid;

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }


    public function clusterGroupTab(Request $request, $uuid, $tab): View|RedirectResponse
    {

        $cluster_group = PuqPmClusterGroup::findOrFail($uuid);

        $validTabs = [
            'general',
            'actions',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route(
                'admin.web.Product.puqProxmox.cluster_group.tab',
                ['uuid' => $cluster_group->uuid, 'tab' => 'general']
            );
        }

        $title = $cluster_group->name;

        return view_admin_module(
            'Product',
            'puqProxmox',
            'admin_area.cluster_groups.cluster_group_'.$tab,
            compact('title', 'uuid', 'tab', 'cluster_group')
        );
    }

    public function getClusterGroup(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmClusterGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();

        if ($model->country) {
            $data['country_data'] = ['id' => $model->country->uuid ?? null, 'text' => $model->country->name ?? null];
        }

        if ($model->region) {
            $data['region_data'] = ['id' => $model->region->uuid ?? null, 'text' => $model->region->name ?? null];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putClusterGroup(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_pm_cluster_groups,name,'.$uuid.',uuid',
            'fill_type' => 'required|in:default,lowest',
            'local_private_network' => [
                'required',
                'regex:/^((25[0-5]|2[0-4][0-9]|1?[0-9]{1,2})\.){3}(25[0-5]|2[0-4][0-9]|1?[0-9]{1,2})\/([0-9]|[1-2][0-9]|3[0-2])$/'
            ],
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'fill_type.required' => __('Product.puqProxmox.The Fill Type field is required'),
            'fill_type.in' => __('Product.puqProxmox.The Fill Type must be default or lowest'),
            'local_private_network.required' => __('Product.puqProxmox.The Local Private Network field is required'),
            'local_private_network.regex' => __('Product.puqProxmox.The Local Private Network must be a valid CIDR, e.g., 192.168.0.0/24'),

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = PuqPmClusterGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $model->name = $request->input('name');
        $model->fill_type = $request->input('fill_type');

        $model->data_center = $request->input('data_center');
        $model->description = $request->input('description');
        $model->country_uuid = $request->input('country_uuid');
        $model->region_uuid = $request->input('region_uuid');
        $model->fill_type = $request->input('fill_type');
        $model->local_private_network = $request->input('local_private_network');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteClusterGroup(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmClusterGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($model->puqPmClusters()->count() != 0) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Cluster Group has Clusters')],
            ], 422);
        }

        if (PuqPmClusterGroup::query()->count() == 1) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.There must be at least one Cluster Group left')],
            ], 422);
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
            'redirect' => route('admin.web.Product.puqProxmox.cluster_groups'),
        ]);
    }

}
