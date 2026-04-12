<?php

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\puqProxmox\Models\PuqPmLxcInstance;
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
            'icon',
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
            ->addColumn('icon', function ($service) {
                $product = $service->product;

                return $product->images['icon'] ?? '';
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
                    'addition_disk' => $lxc_instance->mp_size ?? '',
                    'addition_disk_attributes' => !empty($lxc_instance?->mp_size) ? $additional_disk_attributes : [],
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
}
