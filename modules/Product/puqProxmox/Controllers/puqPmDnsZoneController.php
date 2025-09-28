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
use Modules\Product\puqProxmox\Models\PuqPmDnsZone;
use Yajra\DataTables\DataTables;

class puqPmDnsZoneController extends Controller
{
    public function dnsZones(Request $request): View
    {
        $title = __('Product.puqProxmox.DNS Zones');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.dns_zones.dns_zones', compact('title'));
    }

    public function getDnsZones(Request $request): JsonResponse
    {
        $query = PuqPmDnsZone::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('count', function ($model) {
                    return $model->getRecordCount();
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqProxmox.dns_zone', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.dns_zone.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postDnsZone(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'regex:/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.(?!-)[A-Za-z0-9-]{1,63}(?<!-))*\.?$/',
                'unique:puq_pm_dns_zones,name',
            ],
        ], [
            'name.required' => __('Product.puqProxmox.Name is required'),
            'name.regex' => __('Product.puqProxmox.Invalid zone format'),
            'name.unique' => __('Product.puqProxmox.Zone already exists'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $model = new PuqPmDnsZone;
        $model->name = $request->input('name');
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function deleteDnsZone(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmDnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (! $deleted) {
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

    public function getDnsZone(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmDnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $data['count'] = $model->getRecordCount();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putDnsZone(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmDnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'regex:/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.(?!-)[A-Za-z0-9-]{1,63}(?<!-))*\.?$/',
                'unique:puq_pm_dns_zones,name,'.$uuid.',uuid',
            ],
        ], [
            'name.required' => __('Product.puqProxmox.Name is required'),
            'name.regex' => __('Product.puqProxmox.Invalid zone format'),
            'name.unique' => __('Product.puqProxmox.Zone already exists'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $model->name = $request->input('name');
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function dnsZone(Request $request, $uuid): View
    {
        $title = __('Product.puqProxmox.DNS Zone');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.dns_zones.dns_zone', compact('title', 'uuid'));
    }

    public function getDnsZonesForwardSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        $query = PuqPmDnsZone::query()
            ->where('name', 'not like', '%.in-addr.arpa')
            ->where('name', 'not like', '%.ip6.arpa');

        if (! empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $models = $query->get();

        $results = $models->map(function ($model) {
            return [
                'id' => $model->uuid,
                'text' => $model->name,
            ];
        });

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ]);
    }

    public function getDnsZonesReverseSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        $query = PuqPmDnsZone::query()
            ->where(function ($q) {
                $q->where('name', 'like', '%.in-addr.arpa')
                    ->orWhere('name', 'like', '%.ip6.arpa');
            });

        if (! empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $models = $query->get();

        $results = $models->map(function ($model) {
            return [
                'id' => $model->uuid,
                'text' => $model->name,
            ];
        });

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ]);
    }

}
