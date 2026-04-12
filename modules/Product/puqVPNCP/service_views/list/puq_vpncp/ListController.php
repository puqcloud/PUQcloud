<?php

use App\Models\Service;
use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;

class ListController
{
    public function __construct() {}

    public function controller_ServiceList(Request $request, $product_group)
    {
        $client = app('client');
        $user = app('user');

        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $allowedKeys = [
            'uuid',
            'status',
            'client_label',
            'product_name',
            'price',
            'urls',
            'termination_time',
            'cancellation_time',
            'suspended_reason',
            'create_error',
            'termination_request',
            'error',
            'b_download',
            'b_upload',
            'client_name',
            'location',
            'billing_mode',
            'billing_timestamp',
            'hourly_billing',
        ];

        $query = Service::clientServicesByGroup($client, $product_group->uuid)->whereIn('status', ['pending', 'active', 'suspended']);

        if (! $request->has('order')) {
            $query->orderBy('created_at', 'desc');
        }

        $dataTable = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && ! empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('status', 'like', "%{$search}%")
                            ->orWhere('uuid', 'like', "%{$search}%")
                            ->orWhere('client_label', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('product_name', function ($service) {
                return $service->product->name;
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
            ->addColumn('price', function ($service) {
                $price = $service->price;
                $currency = $price->currency;
                $price_total = $service->getPriceTotal();

                return $currency->prefix.' '.number_format($price_total['base'], 2).' '.$currency->suffix.' / '.__('main.'.$price->period);
            })
            ->addColumn('billing_mode', function ($service) {
                return $service->billing_mode;
            })
            ->addColumn('billing_timestamp', function ($service) {
                return $service->billing_timestamp
                    ? \Carbon\Carbon::parse($service->billing_timestamp)->format('Y-m-d H:i:s')
                    : null;
            })
            ->addColumn('hourly_billing', function ($service) {
                // hourly_billing is a product-level flag, only meaningful for monthly prepaid billing
                return (bool) ($service->product->hourly_billing ?? false);
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
            ->addColumn('b_download', function ($service) {
                return $service->product->configuration['b_download'] ?? '0';
            })
            ->addColumn('b_upload', function ($service) {
                return $service->product->configuration['b_upload'] ?? '0';
            })
            ->addColumn('client_name', function ($service) {
                return $service->provision_data['client_name'] ?? '';
            })
            ->addColumn('location', function ($service) use ($user) {
                $product = $service->product;
                $location_product_option_group_uuid = data_get($product,
                    'module.module.product_data.location_product_option_group_uuid');
                $location_product_option = $service->productOptions()->where('product_option_group_uuid',
                    $location_product_option_group_uuid)->first();

                if ($location_product_option) {
                    $location_product_option->setLocale($user->language);
                }

                $location_product_img_url = data_get($location_product_option, 'images.icon');

                return [
                    'img_url' => $location_product_img_url ?? '',
                    'country' => $location_product_option?->name ?? '',
                    'data_center' => strtolower($location_product_option?->value ?? ''),
                ];
            })
            ->only($allowedKeys);

        $data = $dataTable->toArray();

        return response()->json([
            'data' => ['original' => $data],
        ], 200);
    }
}
