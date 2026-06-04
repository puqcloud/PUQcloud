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

namespace Modules\Product\puqProxmox\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmDnsZone;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class puqPmDnsZoneController extends Controller
{
    public function dnsZones(Request $request): View
    {
        $title = __('Product.puqProxmox.DNS Zones');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.dns_zones.dns_zones', compact('title'));
    }

    #[OA\Get(path: 'admin.api.Product.puqProxmox.dns_zones.get', description: 'Returns a list of DNS zones.', summary: 'Get DNS zones list', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], responses: [new OA\Response(response: 200, description: 'DNS zones list returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
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

    #[OA\Post(path: 'admin.api.Product.puqProxmox.dns_zone.post', description: 'Creates a new DNS zone.', summary: 'Create DNS zone', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name'], properties: [new OA\Property(property: 'name', type: 'string')], type: 'object')), responses: [new OA\Response(response: 200, description: 'DNS zone created successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
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


    #[OA\Delete(path: 'admin.api.Product.puqProxmox.dns_zone.delete', description: 'Deletes a specific DNS zone.', summary: 'Delete DNS zone', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], responses: [new OA\Response(response: 200, description: 'DNS zone deleted successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object'))])]
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

    #[OA\Get(path: 'admin.api.Product.puqProxmox.dns_zone.get', description: 'Returns details of a specific DNS zone.', summary: 'Get DNS zone details', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], responses: [new OA\Response(response: 200, description: 'DNS zone details returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
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

    #[OA\Put(path: 'admin.api.Product.puqProxmox.dns_zone.put', description: 'Updates a specific DNS zone.', summary: 'Update DNS zone', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name', 'ttl'], properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'ttl', type: 'integer')], type: 'object')), responses: [new OA\Response(response: 200, description: 'DNS zone updated successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
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
            'ttl' => 'required|integer|min:30|max:86400',
        ], [
            'name.required' => __('Product.puqProxmox.Name is required'),
            'name.regex' => __('Product.puqProxmox.Invalid zone format'),
            'name.unique' => __('Product.puqProxmox.Zone already exists'),
            'ttl.required' => __('Product.puqProxmox.The DNS Record TTL field is required'),
            'ttl.integer' => __('Product.puqProxmox.The DNS Record TTL must be an integer'),
            'ttl.min' => __('Product.puqProxmox.The DNS Record TTL must be at least 30 seconds'),
            'ttl.max' => __('Product.puqProxmox.The DNS Record TTL cannot exceed 86400 seconds'),
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
        $model->ttl = $request->input('ttl');

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

    #[OA\Get(path: 'admin.api.Product.puqProxmox.dns_zones.forward.select.get', description: 'Returns forward DNS zones for select dropdown.', summary: 'Get forward DNS zones select', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], responses: [new OA\Response(response: 200, description: 'Forward DNS zones select returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
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

    #[OA\Get(path: 'admin.api.Product.puqProxmox.dns_zones.reverse.select.get', description: 'Returns reverse DNS zones for select dropdown.', summary: 'Get reverse DNS zones select', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], responses: [new OA\Response(response: 200, description: 'Reverse DNS zones select returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
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


    #[OA\Get(path: 'admin.api.Product.puqProxmox.dns_zone.records.get', description: 'Returns DNS records for a specific DNS zone.', summary: 'Get DNS zone records', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], responses: [new OA\Response(response: 200, description: 'DNS zone records returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
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

    #[OA\Put(path: 'admin.api.Product.puqProxmox.dns_zone.push_records.put', description: 'Pushes DNS records for a specific DNS zone.', summary: 'Push DNS zone records', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], responses: [new OA\Response(response: 200, description: 'DNS records pushed successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object'))])]
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
