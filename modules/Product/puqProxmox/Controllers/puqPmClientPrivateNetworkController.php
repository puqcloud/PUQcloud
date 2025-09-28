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
use Modules\Product\puqProxmox\Models\PuqPmClientPrivateNetwork;
use Yajra\DataTables\DataTables;

class puqPmClientPrivateNetworkController extends Controller
{

    public function clientPrivateNetworks(Request $request): View
    {
        $title = __('Product.puqProxmox.Client Private Networks');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.client_private_networks.client_private_networks',
            compact('title'));
    }

    public function getClientPrivateNetworks(Request $request): JsonResponse
    {
        $query = PuqPmClientPrivateNetwork::query()
            ->join('clients', 'clients.uuid', '=', 'puq_pm_client_private_networks.client_uuid')
            ->select(
                'puq_pm_client_private_networks.*',
                'clients.firstname as client_firstname',
                'clients.lastname as client_lastname',
                'clients.company_name as client_company_name'
            );

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_client_private_networks.name', 'like', "%{$search}%")
                                ->orWhere('puq_pm_client_private_networks.client_uuid', 'like', "%{$search}%")
                                ->orWhere('puq_pm_client_private_networks.uuid', 'like', "%{$search}%")
                                ->orWhere('puq_pm_client_private_networks.bridge', 'like', "%{$search}%")
                                ->orWhere('puq_pm_client_private_networks.vlan_tag', 'like', "%{$search}%")
                                ->orWhere('puq_pm_client_private_networks.ipv4_network', 'like', "%{$search}%")
                                ->orWhere('clients.firstname', 'like', "%{$search}%")
                                ->orWhere('clients.lastname', 'like', "%{$search}%")
                                ->orWhere('clients.company_name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('puq_pm_cluster_group_data', function ($model) {
                    if ($model->type == 'local_private') {
                        return $model->puqPmClusterGroup->toArray();
                    }

                    return null;
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['delete'] = route('admin.api.Product.puqProxmox.client_private_network.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postClientPrivateNetwork(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'client_uuid' => [
                'required',
                'uuid',
            ],
            'type' => [
                'required',
                Rule::in(['local_private', 'global_private']),
            ],
            'puq_pm_cluster_group_uuid' => [
                Rule::requiredIf(fn() => $request->input('type') === 'local_private'),
                'nullable', 'uuid',
            ],
            'bridge' => [
                'required',
                'string',
                'max:50',
            ],
            'vlan_tag' => [
                'required',
                'integer',
                'min:1',
                'max:4096',
            ],
            'ipv4_network' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !str_contains($value, '/')) {
                        $fail(__('Product.puqProxmox.Invalid CIDR format'));

                        return;
                    }
                    [$ip, $mask] = explode('/', $value);
                    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $fail(__('Product.puqProxmox.Invalid CIDR IP'));

                        return;
                    }
                    if ($mask < 0 || $mask > 32) {
                        $fail(__('Product.puqProxmox.Invalid CIDR mask'));
                    }
                    $ipLong = ip2long($ip);
                    $maskLong = ~((1 << (32 - $mask)) - 1);
                    if (($ipLong & $maskLong) !== $ipLong) {
                        $fail(__('Product.puqProxmox.IP must be network address'));
                    }
                },
            ],
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
            'client_uuid.required' => __('Product.puqProxmox.Client is required'),
            'client_uuid.uuid' => __('Product.puqProxmox.Invalid Client UUID'),
            'type.required' => __('Product.puqProxmox.Type is required'),
            'type.in' => __('Product.puqProxmox.Invalid Type'),
            'puq_pm_cluster_group_uuid.required' => __('Product.puqProxmox.Cluster Group is required for local type'),
            'bridge.required' => __('Product.puqProxmox.Bridge is required'),
            'vlan_tag.required' => __('Product.puqProxmox.Vlan Tag is required'),
            'ipv4_network.required' => __('Product.puqProxmox.IPv4 Network is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $query = PuqPmClientPrivateNetwork::where('bridge', $request->input('bridge'))
            ->where('vlan_tag', $request->input('vlan_tag'))
            ->where('type', $request->input('type'));

        if ($request->input('type') === 'local_private') {
            $query->where('puq_pm_cluster_group_uuid', $request->input('puq_pm_cluster_group_uuid'));
        }

        if ($query->exists()) {
            return response()->json([
                'message' => [
                    'bridge' => [__('Product.puqProxmox.Network with same bridge, vlan, and type already exists')],
                    'vlan_tag' => [__('Product.puqProxmox.Network with same bridge, vlan, and type already exists')],
                    'type' => [__('Product.puqProxmox.Network with same bridge, vlan, and type already exists')],
                    'puq_pm_cluster_group_uuid' => [__('Product.puqProxmox.Network with same bridge, vlan, and type already exists')],
                ],
            ], 422);
        }

        $model = new PuqPmClientPrivateNetwork();
        $model->fill($request->only([
            'name',
            'client_uuid',
            'type',
            'puq_pm_cluster_group_uuid',
            'bridge',
            'vlan_tag',
            'ipv4_network',
        ]));

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function deleteClientPrivateNetwork(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmClientPrivateNetwork::find($uuid);

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

}
