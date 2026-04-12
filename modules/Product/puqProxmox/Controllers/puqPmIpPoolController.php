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
use Illuminate\Validation\Rule;
use Modules\Product\puqProxmox\Models\PuqPmIpPool;
use Modules\Product\puqProxmox\Models\puqPmLxcInstanceNet;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class puqPmIpPoolController extends Controller
{
    public function ipPools(Request $request): View
    {
        $title = __('Product.puqProxmox.IP Pools');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.ip_pools.ip_pools', compact('title'));
    }

    #[OA\Get(path: 'admin.api.Product.puqProxmox.ip_pools.get', description: 'Returns a list of IP pools.', summary: 'Get IP pools list', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], responses: [new OA\Response(response: 200, description: 'IP pools list returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function getIpPools(Request $request): JsonResponse
    {
        $query = PuqPmIpPool::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('type', 'like', "%{$search}%")
                                ->orWhere('first_ip', 'like', "%{$search}%")
                                ->orWhere('last_ip', 'like', "%{$search}%")
                                ->orWhere('gateway', 'like', "%{$search}%")
                                ->orWhere('dns', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('used_count', function ($model) {
                    return $model->getUsedIpCount();
                })
                ->addColumn('count', function ($model) {
                    return $model->getIpCount();
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqProxmox.ip_pool', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.ip_pool.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(path: 'admin.api.Product.puqProxmox.ip_pool.post', description: 'Creates a new IP pool.', summary: 'Create IP pool', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name', 'type', 'first_ip', 'last_ip', 'gateway', 'mask'], properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'type', type: 'string'), new OA\Property(property: 'first_ip', type: 'string'), new OA\Property(property: 'last_ip', type: 'string'), new OA\Property(property: 'gateway', type: 'string'), new OA\Property(property: 'mask', type: 'integer'), new OA\Property(property: 'dns', type: 'string')], type: 'object')), responses: [new OA\Response(response: 200, description: 'IP pool created successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function postIpPool(Request $request): JsonResponse
    {
        $type = $request->input('type');

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => ['required', Rule::in(['ipv4', 'ipv6'])],
            'first_ip' => ['required', $type === 'ipv6' ? 'ipv6' : 'ipv4'],
            'last_ip' => ['required', $type === 'ipv6' ? 'ipv6' : 'ipv4'],
            'gateway' => ['required', $type === 'ipv6' ? 'ipv6' : 'ipv4'],
            'mask' => ['required', 'integer', 'min:1', $type === 'ipv6' ? 'max:128' : 'max:32'],
            'dns' => [
                'nullable', 'string', function ($attribute, $value, $fail) use ($type) {
                    $ips = array_map('trim', explode(',', $value));
                    foreach ($ips as $ip) {
                        if (!$ip) {
                            continue;
                        }
                        if (!filter_var($ip, FILTER_VALIDATE_IP,
                            $type === 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4)) {
                            $fail(__('Product.puqProxmox.Invalid DNS IP').": $ip");
                        }
                    }
                },
            ],
        ], [
            'name.required' => __('Product.puqProxmox.Name is required'),
            'type.required' => __('Product.puqProxmox.Type is required'),
            'type.in' => __('Product.puqProxmox.Invalid IP type'),
            'first_ip.required' => __('Product.puqProxmox.First IP is required'),
            'first_ip.ipv4' => __('Product.puqProxmox.First IP must be IPv4'),
            'first_ip.ipv6' => __('Product.puqProxmox.First IP must be IPv6'),
            'last_ip.required' => __('Product.puqProxmox.Last IP is required'),
            'gateway.required' => __('Product.puqProxmox.Gateway is required'),
            'mask.required' => __('Product.puqProxmox.Mask is required'),
            'mask.integer' => __('Product.puqProxmox.Mask must be a number'),
            'mask.min' => __('Product.puqProxmox.Mask is too small'),
            'mask.max' => __('Product.puqProxmox.Mask is too large'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $first = $request->input('first_ip');
        $last = $request->input('last_ip');
        $gateway = $request->input('gateway');
        $mask = (int) $request->input('mask');

        // Extra IP logic moved to model
        if (!PuqPmIpPool::isValidRange($first, $last, $type)) {
            return response()->json(['message' => ['first_ip' => [__('Product.puqProxmox.First IP must be less than or equal to Last IP')]]],
                422);
        }

        if (!PuqPmIpPool::sameSubnet($first, $last, $gateway, $type, $mask)) {
            return response()->json(['message' => ['gateway' => [__('Product.puqProxmox.All IPs must be in the same subnet')]]],
                422);
        }

        if (PuqPmIpPool::ipInRange($gateway, $first, $last, $type)) {
            return response()->json(['message' => ['gateway' => [__('Product.puqProxmox.Gateway must not be inside the IP pool')]]],
                422);
        }

        $model = new PuqPmIpPool;
        $model->name = $request->input('name');
        $model->type = $type;
        $model->first_ip = $first;
        $model->last_ip = $last;
        $model->mask = $mask;
        $model->gateway = $gateway;
        $model->dns = $request->input('dns');
        $model->compressIps(); // ← compress IPv6 before save
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(path: 'admin.api.Product.puqProxmox.ip_pool.delete', description: 'Deletes a specific IP pool.', summary: 'Delete IP pool', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the IP pool', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], responses: [new OA\Response(response: 200, description: 'IP pool deleted successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object'))])]
    public function deleteIpPool(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmIpPool::find($uuid);

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


    #[OA\Get(path: 'admin.api.Product.puqProxmox.ip_pool.get', description: 'Returns details of a specific IP pool.', summary: 'Get IP pool details', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the IP pool', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], responses: [new OA\Response(response: 200, description: 'IP pool details returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function getIpPool(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmIpPool::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $data['count'] = $model->getIpCount();
        $data['used_count'] = $model->getUsedIpCount();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    #[OA\Put(path: 'admin.api.Product.puqProxmox.ip_pool.put', description: 'Updates a specific IP pool.', summary: 'Update IP pool', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the IP pool', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')), responses: [new OA\Response(response: 200, description: 'IP pool updated successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function putIpPool(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmIpPool::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $type = $request->input('type');

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => ['required', Rule::in(['ipv4', 'ipv6'])],
            'first_ip' => ['required', $type === 'ipv6' ? 'ipv6' : 'ipv4'],
            'last_ip' => ['required', $type === 'ipv6' ? 'ipv6' : 'ipv4'],
            'gateway' => ['required', $type === 'ipv6' ? 'ipv6' : 'ipv4'],
            'mask' => ['required', 'integer', 'min:1', $type === 'ipv6' ? 'max:128' : 'max:32'],
            'dns' => [
                'nullable', 'string', function ($attribute, $value, $fail) use ($type) {
                    $ips = array_map('trim', explode(',', $value));
                    foreach ($ips as $ip) {
                        if (!$ip) {
                            continue;
                        }
                        if (!filter_var($ip, FILTER_VALIDATE_IP,
                            $type === 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4)) {
                            $fail(__('Product.puqProxmox.Invalid DNS IP').": $ip");
                        }
                    }
                },
            ],
        ], [
            'name.required' => __('Product.puqProxmox.Name is required'),
            'type.required' => __('Product.puqProxmox.Type is required'),
            'type.in' => __('Product.puqProxmox.Invalid IP type'),
            'first_ip.required' => __('Product.puqProxmox.First IP is required'),
            'first_ip.ipv4' => __('Product.puqProxmox.First IP must be IPv4'),
            'first_ip.ipv6' => __('Product.puqProxmox.First IP must be IPv6'),
            'last_ip.required' => __('Product.puqProxmox.Last IP is required'),
            'gateway.required' => __('Product.puqProxmox.Gateway is required'),
            'mask.required' => __('Product.puqProxmox.Mask is required'),
            'mask.integer' => __('Product.puqProxmox.Mask must be a number'),
            'mask.min' => __('Product.puqProxmox.Mask is too small'),
            'mask.max' => __('Product.puqProxmox.Mask is too large'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $first = $request->input('first_ip');
        $last = $request->input('last_ip');
        $gateway = $request->input('gateway');
        $mask = (int) $request->input('mask');

        // Extra IP logic moved to model
        if (!PuqPmIpPool::isValidRange($first, $last, $type)) {
            return response()->json(['message' => ['first_ip' => __('Product.puqProxmox.First IP must be less than or equal to Last IP')]],
                422);
        }

        if (!PuqPmIpPool::sameSubnet($first, $last, $gateway, $type, $mask)) {
            return response()->json(['message' => ['gateway' => __('Product.puqProxmox.All IPs must be in the same subnet')]],
                422);
        }

        if (PuqPmIpPool::ipInRange($gateway, $first, $last, $type)) {
            return response()->json(['message' => ['gateway' => __('Product.puqProxmox.Gateway must not be inside the IP pool')]],
                422);
        }

        $model->name = $request->input('name');
        $model->type = $type;
        $model->first_ip = $first;
        $model->last_ip = $last;
        $model->mask = $mask;
        $model->gateway = $gateway;
        $model->dns = $request->input('dns');
        $model->compressIps(); // ← compress IPv6 before save
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function ipPool(Request $request, $uuid): View
    {
        $title = __('Product.puqProxmox.IP Pool');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.ip_pools.ip_pool', compact('title', 'uuid'));
    }

    #[OA\Get(path: 'admin.api.Product.puqProxmox.ip_pools.select.get', description: 'Returns IP pools for select dropdown.', summary: 'Get IP pools select', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], responses: [new OA\Response(response: 200, description: 'IP pools select returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function getIpPoolsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (!empty($search)) {
            $models = PuqPmIpPool::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqPmIpPool::query()->get();
        }

        $results = [
            [
                'id' => '0',
                'text' => __('Product.puqProxmox.No choice'),
            ],
        ];
        foreach ($models->toArray() ?? [] as $model) {
            $results[] = [
                'id' => $model['uuid'],
                'text' => $model['name'].' ('.$model['first_ip'].'-'.$model['last_ip'].')',
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

    #[OA\Get(path: 'admin.api.Product.puqProxmox.ip_pool.used_ips.get', description: 'Returns used IPs for a specific IP pool.', summary: 'Get used IPs', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], parameters: [new OA\Parameter(name: 'uuid', description: 'UUID of the IP pool', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))], responses: [new OA\Response(response: 200, description: 'Used IPs list returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function getUsedIps(Request $request, $uuid): JsonResponse
    {
        $ip_pool = PuqPmIpPool::query()->where('uuid', $uuid)->first();


        $query = puqPmLxcInstanceNet::query();

        if($ip_pool->type === 'ipv4'){
            $query->where('puq_pm_ipv4_pool_uuid', $uuid)
            ->select('puq_pm_lxc_instance_nets.*','puq_pm_lxc_instance_nets.ipv4 as ip');
        }else{
            $query->where('puq_pm_ipv6_pool_uuid', $uuid)
                ->select('puq_pm_lxc_instance_nets.*','puq_pm_lxc_instance_nets.ipv6 as ip');
        }
        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('ipv4', 'like', "%{$search}%")
                                ->orWhere('ipv6', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('service_uuid', function ($model) {
                    return optional($model->puqPmLxcInstance)->service_uuid;
                })
                ->make(true),
        ], 200);
    }

}
