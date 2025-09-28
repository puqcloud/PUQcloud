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
            'icon',
            'uuid',
            'status',
            'client_label',
            'price',
            'product_name',
            'urls',
            'custom_fields',
            'termination_time',
            'cancellation_time',
            'suspended_reason',
            'create_error',
            'termination_request',
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
            ->addColumn('icon', function ($service) {
                $product = $service->product;

                return $product->images['icon'] ?? '';
            })
            ->addColumn('price', function ($service) {
                $price = $service->price;
                $currency = $price->currency;
                $price_total = $service->getPriceTotal();

                return [
                    'code' => $currency->code ?? '',
                    'prefix' => $currency->prefix ?? '',
                    'suffix' => $currency->suffix ?? '',
                    'amount' => $price_total['base'],
                    'amount_str' => formatCurrency($price_total['base'], $currency, $currency->code),
                    'period' => $price->period,
                    'hourly_billing' => $service->product->hourly_billing,
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
            ->addColumn('custom_fields', function ($service) use ($user) {
                $custom_fields = [];

                foreach ($service->productOptions as $product_option) {
                    $product_option_group = $product_option->productOptionGroup;
                    $product_option->setLocale($user->language);
                    $product_option_group->setLocale($user->language);
                    $custom_fields[$product_option_group->name] = $product_option->name;
                }

                return $custom_fields;
            })
            ->only($allowedKeys);

        $data = $dataTable->toArray();

        $allCustomFields = [];

        foreach ($data['data'] as $service) {
            foreach ($service['custom_fields'] as $key => $value) {
                if (! in_array($key, $allCustomFields)) {
                    $allCustomFields[] = $key;
                }
            }
        }

        foreach ($data['data'] as &$service) {
            foreach ($allCustomFields as $key) {
                if (! array_key_exists($key, $service['custom_fields'])) {
                    $service['custom_fields'][$key] = '';
                }
            }
        }

        $data['columns'] = array_merge(['icon', 'client_label', 'status'], $allCustomFields, ['price', 'urls']);

        return response()->json([
            'data' => ['original' => $data],
        ], 200);
    }
}
