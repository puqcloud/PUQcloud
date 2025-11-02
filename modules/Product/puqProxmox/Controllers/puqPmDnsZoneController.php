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
use App\Models\DnsZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('dns_manager', function ($model) {
                    $dns_zone = $model->getDnsZone();
                    if ($dns_zone) {
                        return [
                            'name' => $dns_zone->name ?? null,
                            'uuid' => $dns_zone->uuid ?? null,
                            'web_url' => route('admin.web.dns_zone', $dns_zone->uuid),
                            'api_url' => route('admin.api.dns_zone.get', $dns_zone->uuid),
                            'record_count' => $dns_zone->getDnsRecordCount() ?? 0,
                        ];
                    }

                    return null;
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
        $name = $request->input('name');

        $isRdns = str_ends_with($name, '.in-addr.arpa') || str_ends_with($name, '.ip6.arpa');

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

        if ($isRdns) {
            if (str_ends_with($name, '.in-addr.arpa')) {
                $parts = explode('.', str_replace('.in-addr.arpa', '', $name));
                if (count($parts) !== 3) {
                    return response()->json([
                        'message' => __('Product.puqProxmox.IPv4 rDNS must be /24 (3 octets)'),
                    ], 422);
                }
            } else {
                $parts = explode('.', str_replace('.ip6.arpa', '', $name));
                $nibbles = count($parts);

                if ($nibbles < 16 || $nibbles > 28 || ($nibbles % 4) !== 0) {
                    return response()->json([
                        'message' => __('Product.puqProxmox.IPv6 rDNS must be between /64 and /112 with step of 4 nibbles'),
                    ], 422);
                }
            }
        }

        $model = new PuqPmDnsZone;
        $model->name = $name;
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

        $dns_zone = $model->getDnsZone();
        $data['dns_manager'] = [];
        if ($dns_zone) {
            $data['dns_manager'] = [
                'name' => $dns_zone->name ?? null,
                'uuid' => $dns_zone->uuid ?? null,
                'web_url' => route('admin.web.dns_zone', $dns_zone->uuid),
                'api_url' => route('admin.api.dns_zone.get', $dns_zone->uuid),
                'record_count' => $dns_zone->getDnsRecordCount() ?? 0,
            ];
        }

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

        $name = $request->input('name');

        $isRdns = str_ends_with($name, '.in-addr.arpa') || str_ends_with($name, '.ip6.arpa');

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

        if ($isRdns) {
            if (str_ends_with($name, '.in-addr.arpa')) {
                $parts = explode('.', str_replace('.in-addr.arpa', '', $name));
                if (count($parts) !== 3) {
                    return response()->json([
                        'message' => __('Product.puqProxmox.IPv4 rDNS must be /24 (3 octets)'),
                    ], 422);
                }
            } else {
                $parts = explode('.', str_replace('.ip6.arpa', '', $name));
                $nibbles = count($parts);

                if ($nibbles < 16 || $nibbles > 28 || ($nibbles % 4) !== 0) {
                    return response()->json([
                        'message' => __('Product.puqProxmox.IPv6 rDNS must be between /64 and /112 with step of 4 nibbles'),
                    ], 422);
                }
            }
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

        if (!empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
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

        if (!empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
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


    public function getDnsZoneRecords(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmDnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $model_type = $model->getZoneType();
        $query = null;

        if (in_array($model_type, ['reverse_ipv4', 'reverse_ipv6'])) {
            $ip_pools = $model->getMatchingIpPools()->pluck('uuid')->toArray();

            $query = DB::table('puq_pm_lxc_instance_nets')
                ->join('puq_pm_lxc_instances', 'puq_pm_lxc_instances.uuid', '=',
                    'puq_pm_lxc_instance_nets.puq_pm_lxc_instance_uuid')
                ->where('puq_pm_lxc_instance_nets.type', 'public');

            if ($model_type === 'reverse_ipv4') {
                $query->whereIn('puq_pm_ipv4_pool_uuid', $ip_pools)
                    ->select(
                        'puq_pm_lxc_instance_nets.ipv4 as ip',
                        'puq_pm_lxc_instance_nets.rdns_v4 as content',
                        'puq_pm_lxc_instances.hostname',
                        DB::raw("'reverse_ipv4' as zone_type"),
                        DB::raw("'puq_pm_lxc_instance_nets' as table_name")
                    );
            } else {
                $query->whereIn('puq_pm_ipv6_pool_uuid', $ip_pools)
                    ->select(
                        'puq_pm_lxc_instance_nets.ipv6 as ip',
                        'puq_pm_lxc_instance_nets.rdns_v6 as content',
                        'puq_pm_lxc_instances.hostname',
                        DB::raw("'reverse_ipv6' as zone_type"),
                        DB::raw("'puq_pm_lxc_instance_nets' as table_name")
                    );
            }

            $query = DB::query()->fromSub($query, 'combined');
        }

        if ($model_type === 'forward') {
            $ipv4Query = DB::table('puq_pm_lxc_instances')
                ->join('puq_pm_lxc_instance_nets', 'puq_pm_lxc_instance_nets.puq_pm_lxc_instance_uuid', '=',
                    'puq_pm_lxc_instances.uuid')
                ->where('puq_pm_lxc_instances.puq_pm_dns_zone_uuid', $model->uuid)
                ->where('puq_pm_lxc_instance_nets.type', 'public')
                ->whereNotNull('puq_pm_lxc_instance_nets.ipv4')
                ->select(
                    'puq_pm_lxc_instance_nets.ipv4 as ip',
                    DB::raw("'' as content"),
                    'puq_pm_lxc_instances.hostname',
                    DB::raw("'forward' as zone_type"),
                    DB::raw("'puq_pm_lxc_instance_nets' as table_name")
                );

            $ipv6Query = DB::table('puq_pm_lxc_instances')
                ->join('puq_pm_lxc_instance_nets', 'puq_pm_lxc_instance_nets.puq_pm_lxc_instance_uuid', '=',
                    'puq_pm_lxc_instances.uuid')
                ->where('puq_pm_lxc_instances.puq_pm_dns_zone_uuid', $model->uuid)
                ->where('puq_pm_lxc_instance_nets.type', 'public')
                ->whereNotNull('puq_pm_lxc_instance_nets.ipv6')
                ->select(
                    'puq_pm_lxc_instance_nets.ipv6 as ip',
                    DB::raw("'' as content"),
                    'puq_pm_lxc_instances.hostname',
                    DB::raw("'forward' as zone_type"),
                    DB::raw("'puq_pm_lxc_instance_nets' as table_name")
                );

            $query = DB::query()->fromSub(
                $ipv4Query->unionAll($ipv6Query),
                'combined'
            );
        }

        return response()->json([
            'data' => DataTables::of($query)
                ->editColumn('ip', fn($row) => $row->ip)
                ->editColumn('content', fn($row) => $row->content)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('ip', 'like', "%{$search}%")
                                ->orWhere('hostname', 'like', "%{$search}%")
                                ->orWhere('content', 'like', "%{$search}%");
                        });
                    }
                })
                ->make(true),
        ], 200);
    }

    public function putDnsZonePushRecords(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmDnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $push = $model->pushZone();

        if ($push['status'] == 'error') {
            return response()->json([
                'errors' => $push['errors'],
            ], $push['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Pushed successfully'),
        ]);
    }

}
