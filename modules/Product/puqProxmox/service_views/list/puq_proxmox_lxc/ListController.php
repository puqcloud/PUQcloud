<?php

use App\Models\ProductOption;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Product\puqProxmox\Models\PuqPmClientPrivateNetwork;
use Modules\Product\puqProxmox\Models\PuqPmClusterGroup;
use Modules\Product\puqProxmox\Models\PuqPmLxcInstance;
use Modules\Product\puqProxmox\Models\PuqPmSshPublicKey;
use Yajra\DataTables\Facades\DataTables;

class ListController
{
    public function __construct()
    {
    }

    public function controller_ServiceList(Request $request, $product_group): JsonResponse
    {
        $client = app('client');
        $user = app('user');

        if (!$client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $allowedKeys = [
            'uuid',
            'status',
            'client_label',
            'product_name',
            'urls',
            'termination_time',
            'cancellation_time',
            'suspended_reason',
            'error',
            'termination_request',
            'provision_status',
            'params',
        ];

        $query = Service::clientServicesByGroup($client, $product_group->uuid)->whereIn('status',
            ['pending', 'active', 'suspended']);

        $query->orderBy('created_at', 'desc');

        $dataTable = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('status', 'like', "%{$search}%")
                            ->orWhere('uuid', 'like', "%{$search}%")
                            ->orWhere('client_label', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('error', function ($service) {
                if ($service->create_error == 'Insufficient funds') {
                    return __('main.Insufficient funds');
                }
                if (!empty($service->create_error)) {
                    return __('error.Something went wrong');
                }

                return '';
            })
            ->addColumn('product_name', function ($service) {
                return $service->product->name;
            })
            ->addColumn('params', function ($service) {

                $lxc_instance = PuqPmLxcInstance::query()->where('service_uuid', $service->uuid)->first();

                $cpu_attributes = [];
                $ram_attributes = [];
                $system_disk_attributes = [];
                $additional_disk_attributes = [];

                if ($lxc_instance) {
                    $attributes = $lxc_instance->getLxcAttributes() ?? [];

                    $cpu_attributes = $attributes['cpu'] ?? [];
                    $ram_attributes = $attributes['ram'] ?? [];
                    $system_disk_attributes = $attributes['rootfs'] ?? [];
                    $additional_disk_attributes = $attributes['mp'] ?? [];

                }

                $product = $service->product;
                $location_product_option_group_uuid = data_get($product,
                    'module.module.product_data.location_product_option_group_uuid');
                $location_product_option = $service->productOptions()->where('product_option_group_uuid',
                    $location_product_option_group_uuid)->first();
                $location_product_img_url = data_get($location_product_option, 'images.icon');

                return [
                    'cpu' => $lxc_instance->cores ?? '',
                    'clu_attributes' => !empty($lxc_instance?->cores) ? $cpu_attributes : [],
                    'ram' => $lxc_instance->memory ?? '',
                    'ram_attributes' => !empty($lxc_instance?->memory) ? $ram_attributes : [],
                    'system_disk' => $lxc_instance->rootfs_size ?? '',
                    'system_disk_attributes' => !empty($lxc_instance?->rootfs_size) ? $system_disk_attributes : [],
                    'addition_disk' => $lxc_instance->mp_size ?? '',
                    'addition_disk_attributes' => !empty($lxc_instance?->mp_size) ? $additional_disk_attributes : [],
                    'domain' => $lxc_instance?->getDomain() ?? '',
                    'hostname' => $lxc_instance->hostname ?? '',
                    'ip_v4' => $lxc_instance?->getIPv4() ?? '',
                    'ipv6' => $lxc_instance?->getIPv6() ?? '',
                    'location' => [
                        'img_url' => $location_product_img_url ?? '',
                        'country' => $location_product_option?->name,
                        'data_center' => strtolower($location_product_option?->value) ?? '',
                    ],
                ];
            })
            ->addColumn('termination_time', function ($service) {
                return $service->getTerminationTime();
            })
            ->addColumn('cancellation_time', function ($service) {
                return $service->getCancellationTime();
            })
            ->addColumn('urls', function ($service) {
                return [
                    'manage' => route('client.web.panel.cloud.service', $service->uuid),
                ];
            })
            ->only($allowedKeys);

        $data = $dataTable->toArray();

        return response()->json([
            'data' => ['original' => $data],
        ], 200);
    }

    public function controller_GetSshPublicKeys(Request $request, $product_group): JsonResponse
    {
        $client = app('client');

        if (!$client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $allowedKeys = [
            'uuid',
            'name',
            'info',
            'urls',
        ];

        $query = PuqPmSshPublicKey::query()
            ->where('client_uuid', $client->uuid)
            ->select('name', 'uuid', 'public_key');

        if (!$request->has('order')) {
            $query->orderBy('created_at', 'desc');
        }

        $dataTable = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('uuid', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('info', function ($model) {
                try {
                    return $model->getInfo();
                } catch (\Exception $e) {
                    return [
                        'type' => 'unknown',
                        'fingerprint' => null,
                        'comment' => null,
                    ];
                }
            })
            ->addColumn('urls', function ($model) use ($product_group) {
                return [
                    'delete' => route('client.api.cloud.group.list.delete', [
                            'uuid' => $product_group->uuid, 'method' => 'DeleteSshPublicKey',
                        ]).'?ssh_public_key_uuid='.$model->uuid,
                ];
            })
            ->only($allowedKeys);

        $data = $dataTable->toArray();

        return response()->json([
            'data' => ['original' => $data],
        ], 200);
    }

    public function controller_PostSshPublicKey(Request $request, $product_group): JsonResponse
    {
        $client = app('client');

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'public_key' => [
                'required',
                'regex:/^(ssh-(rsa|ed25519|dss|ecdsa)|ecdsa-sha2-nistp(256|384|521))\s+[A-Za-z0-9+\/=]+(\s.*)?$/',
            ],
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
            'public_key.required' => __('Product.puqProxmox.SSH Public Key is required'),
            'public_key.regex' => __('Product.puqProxmox.Invalid SSH Public Key format'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmSshPublicKey;
        $model->name = $request->input('name');
        $model->client_uuid = $client->uuid;
        $model->public_key = $request->input('public_key');
        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Added Successfully'),
        ]);
    }

    public function controller_DeleteSshPublicKey(Request $request, $product_group): JsonResponse
    {
        $client = app('client');

        $model = PuqPmSshPublicKey::query()
            ->where('client_uuid', $client->uuid)
            ->where('uuid', request('ssh_public_key_uuid'))
            ->first();

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

    public function controller_GetPrivateNetworks(Request $request, $product_group): JsonResponse
    {
        $client = app('client');

        if (!$client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $client = app('client');
        $locations = [];
        $products = $product_group->products;
        $product_option_group_uuid = [];
        foreach ($products as $product) {
            $uuid = data_get($product, 'module.module.product_data.location_product_option_group_uuid');
            if ($uuid && !in_array($uuid, $product_option_group_uuid)) {
                $product_option_group_uuid[] = $uuid;
            }
        }
        $product_options = ProductOption::query()->whereIn('product_option_group_uuid',
            $product_option_group_uuid)->orderBy('order')->get();

        $cluster_groups = [];
        $cluster_group_models = PuqPmClusterGroup::query()->get();
        foreach ($cluster_group_models as $cluster_group) {
            $cluster_groups[$cluster_group->getLocation()] = $cluster_group;
        }

        foreach ($product_options as $product_option) {
            $value = mb_strtolower($product_option->value);
            $cluster_group = $cluster_groups[$value] ?? null;
            if ($cluster_group) {
                $locations[$cluster_group->uuid] = [
                    'name' => $product_option->name,
                    'value' => $value,
                ];
            }
        }

        $allowedKeys = [
            'uuid',
            'name',
            'type',
            'location',
            'location_data',
            'ipv4_network',
            'urls',
        ];

        $query = PuqPmClientPrivateNetwork::query()
            ->where('client_uuid', $client->uuid);

        if (!$request->has('order')) {
            $query->orderBy('created_at', 'desc');
        }

        $dataTable = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('uuid', 'like', "%{$search}%")
                            ->orWhere('ipv4_network', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('location', function ($model) use ($locations) {
                return $locations[$model->puq_pm_cluster_group_uuid]['value'] ?? '';
            })
            ->addColumn('location_data', function ($model) use ($locations) {
                return $locations[$model->puq_pm_cluster_group_uuid] ?? [];
            })
            ->addColumn('urls', function ($model) use ($product_group) {
                $urls['delete'] =
                    route('client.api.cloud.group.list.delete', [
                        'uuid' => $product_group->uuid, 'method' => 'DeletePrivateNetwork',
                    ]).'?private_network_uuid='.$model->uuid;

                return [];
            })
            ->only($allowedKeys);

        $data = $dataTable->toArray();

        return response()->json([
            'data' => ['original' => $data],
        ], 200);
    }

    public function controller_GetPrivateNetworkLocationsSelect(Request $request, $product_group): JsonResponse
    {
        $search = $request->input('q');

        $client = app('client');
        $already_locations = [];
        $private_networks = PuqPmClientPrivateNetwork::query()
            ->where('client_uuid', $client->uuid)
            ->where('type', 'local')
            ->get();
        foreach ($private_networks as $network) {
            $already_locations[] = mb_strtolower($network->getLocation());
        }

        $locations = [];
        $products = $product_group->products;
        $product_option_group_uuid = [];
        foreach ($products as $product) {
            $uuid = data_get($product, 'module.module.product_data.location_product_option_group_uuid');
            if ($uuid && !in_array($uuid, $product_option_group_uuid)) {
                $product_option_group_uuid[] = $uuid;
            }
        }
        $product_options = ProductOption::query()->whereIn('product_option_group_uuid',
            $product_option_group_uuid)->orderBy('order')->get();

        foreach ($product_options as $product_option) {
            $valueLower = mb_strtolower($product_option->value);

            if (!in_array($valueLower, $already_locations)) {
                if ($search) {
                    $searchLower = mb_strtolower($search);
                    if (strpos($valueLower, $searchLower) === false &&
                        strpos(mb_strtolower($product_option->name), $searchLower) === false) {
                        continue;
                    }
                }

                $locations[mb_strtolower($product_option->value)] = [
                    'id' => $product_option->value,
                    'text' => $product_option->name.' ('.$product_option->value.')',
                ];
            }
        }

        return response()->json([
            'data' => [
                'results' => array_values($locations),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ]);
    }

    public function controller_PostPrivateNetwork(Request $request, $product_group)
    {
        $client = app('client');

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'type' => [
                'required',
                Rule::in(['local', 'global']),
            ],
            'location' => [
                Rule::requiredIf(fn() => $request->input('type') === 'local'),
                'nullable',
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
            'type.required' => __('Product.puqProxmox.Type is required'),
            'type.in' => __('Product.puqProxmox.Invalid Type'),
            'location.required' => __('Product.puqProxmox.Location is required for local type'),
            'ipv4_network.required' => __('Product.puqProxmox.IPv4 Network is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->input('type') === 'local') {
            $location = $request->input('location');
            $cluster_group = PuqPmClusterGroup::getByLocation($location);

            if (!$cluster_group) {
                return response()->json([
                    'message' => ['location' => [__('Product.puqProxmox.Invalid Location')]],
                ], 422);
            }

            $exists = PuqPmClientPrivateNetwork::query()
                ->where('type', 'local')
                ->where('client_uuid', $client->uuid)
                ->where('puq_pm_cluster_group_uuid', $cluster_group->uuid)
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => [
                        'type' => [__('Product.puqProxmox.Local Private Network already exists')],
                        'location' => [__('Product.puqProxmox.Local Private Network already exists')],
                    ],
                ], 422);
            }

            $new_client_global_private_network = $cluster_group->getNewClientLocalPrivateNetwork();
            if (!$new_client_global_private_network) {
                return response()->json([
                    'message' => [
                        'type' => [__('Product.puqProxmox.Local Private Network is not available in this location at the moment')],
                        'location' => [__('Product.puqProxmox.Local Private Network is not available in this location at the moment')],
                    ],
                ], 422);
            }

            $model = new PuqPmClientPrivateNetwork;

            $model->name = $request->input('name');
            $model->type = 'local';
            $model->client_uuid = $client->uuid;
            $model->puq_pm_cluster_group_uuid = $cluster_group->uuid;
            $model->bridge = $new_client_global_private_network['bridge'];
            $model->vlan_tag = $new_client_global_private_network['vlan_tag'];
            $model->ipv4_network = $request->input('ipv4_network');

            $model->save();
            $model->refresh();
        }

        if ($request->input('type') === 'global') {

            $exists = PuqPmClientPrivateNetwork::query()
                ->where('client_uuid', $client->uuid)
                ->where('type', 'global')
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => [
                        'type' => [__('Product.puqProxmox.Global Private Network already exists')],
                        'location' => [__('Product.puqProxmox.Global Private Network already exists')],
                    ],
                ], 422);
            }

            $new_client_global_private_network = PuqPmClientPrivateNetwork::getNewClientGlobalPrivateNetwork();
            if (!$new_client_global_private_network) {
                return response()->json([
                    'message' => [
                        'type' => [__('Product.puqProxmox.Global Private Network is currently unavailable')],
                        'location' => [__('Product.puqProxmox.Global Private Network is currently unavailable')],
                    ],
                ], 422);
            }

            $model = new PuqPmClientPrivateNetwork;

            $model->name = $request->input('name');
            $model->type = 'global';
            $model->client_uuid = $client->uuid;
            $model->bridge = $new_client_global_private_network['bridge'];
            $model->vlan_tag = $new_client_global_private_network['vlan_tag'];
            $model->ipv4_network = $request->input('ipv4_network');

            $model->save();
            $model->refresh();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Added Successfully'),
        ]);
    }

    //    public function controller_DeletePrivateNetwork(Request $request, $product_group)
    //    {
    //        $client = app('client');
    //
    //        $model = PuqPmClientPrivateNetwork::query()
    //            ->where('client_uuid', $client->uuid)
    //            ->where('uuid', request('private_network_uuid'))
    //            ->first();
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
    //                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
    //            ], 500);
    //        }
    //
    //        return response()->json([
    //            'status' => 'success',
    //            'message' => __('Product.puqProxmox.Deleted successfully'),
    //        ]);
    //    }
}
