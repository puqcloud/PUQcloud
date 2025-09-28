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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\puqPmLxcInstanceNet;
use Modules\Product\puqProxmox\Models\PuqPmMacPool;
use Yajra\DataTables\DataTables;

class puqPmMacPoolController extends Controller
{
    public function macPools(Request $request): View
    {
        $title = __('Product.puqProxmox.MAC Pools');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.mac_pools.mac_pools', compact('title'));
    }

    public function getMacPools(Request $request): JsonResponse
    {
        $query = PuqPmMacPool::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('first_mac', 'like', "%{$search}%")
                                ->orWhere('last_mac', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('used_count', function ($model) {
                    return $model->getUsedMacCount();
                })
                ->addColumn('count', function ($model) {
                    return $model->getMacCount();
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqProxmox.mac_pool', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.mac_pool.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postMacPool(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'first_mac' => ['required', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'last_mac' => ['required', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
        ], [
            'name.required' => __('Product.puqProxmox.Name is required'),
            'first_mac.required' => __('Product.puqProxmox.First MAC is required'),
            'last_mac.required' => __('Product.puqProxmox.Last MAC is required'),
            'first_mac.regex' => __('Product.puqProxmox.First MAC must be a valid MAC address'),
            'last_mac.regex' => __('Product.puqProxmox.Last MAC must be a valid MAC address'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $first = strtolower($request->input('first_mac'));
        $last = strtolower($request->input('last_mac'));

        $macToInt = fn($mac) => hexdec(str_replace([':', '-'], '', $mac));

        if ($macToInt($first) > $macToInt($last)) {
            return response()->json(['message' => ['first_mac' => [__('Product.puqProxmox.First MAC must be less than or equal to Last MAC')]]],
                422);
        }

        $model = new PuqPmMacPool;
        $model->name = $request->input('name');
        $model->first_mac = str_replace('-', ':', $first);
        $model->last_mac = str_replace('-', ':', $last);
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function deleteMacPool(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmMacPool::find($uuid);

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

    public function getMacPool(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmMacPool::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $data['used_count'] = $model->getUsedMacCount();
        $data['count'] = $model->getMacCount();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putMacPool(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmMacPool::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'first_mac' => ['required', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'last_mac' => ['required', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
        ], [
            'name.required' => __('Product.puqProxmox.Name is required'),
            'first_mac.required' => __('Product.puqProxmox.First MAC is required'),
            'last_mac.required' => __('Product.puqProxmox.Last MAC is required'),
            'first_mac.regex' => __('Product.puqProxmox.First MAC must be a valid MAC address'),
            'last_mac.regex' => __('Product.puqProxmox.Last MAC must be a valid MAC address'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $first = strtolower($request->input('first_mac'));
        $last = strtolower($request->input('last_mac'));

        $macToInt = fn($mac) => hexdec(str_replace([':', '-'], '', $mac));

        if ($macToInt($first) > $macToInt($last)) {
            return response()->json(['message' => ['first_mac' => [__('Product.puqProxmox.First MAC must be less than or equal to Last MAC')]]],
                422);
        }

        $model->name = $request->input('name');
        $model->first_mac = str_replace('-', ':', $first);
        $model->last_mac = str_replace('-', ':', $last);
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function macPool(Request $request, $uuid): View
    {
        $title = __('Product.puqProxmox.MAC Pool');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.mac_pools.mac_pool', compact('title', 'uuid'));
    }

    public function getMacPoolsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (!empty($search)) {
            $models = PuqPmMacPool::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqPmMacPool::query()->get();
        }

        $results = [];
        foreach ($models->toArray() ?? [] as $model) {
            $results[] = [
                'id' => $model['uuid'],
                'text' => $model['name'].' ('.$model['first_mac'].'-'.$model['last_mac'].')',
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

    public function getUsedMacs(Request $request, $uuid): JsonResponse
    {
        $query = puqPmLxcInstanceNet::query()->where('puq_pm_mac_pool_uuid', $uuid);

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('mac', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('service_uuid', function ($model) {
                    $puq_pm_lxc_instance = $model->puqPmLxcInstance;
                    return $puq_pm_lxc_instance->service_uuid;
                })
                ->make(true),
        ], 200);
    }
}
