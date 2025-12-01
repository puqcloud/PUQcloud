<?php

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrderController
{
    public function __construct()
    {
    }


    public function controller_GetLocations(Request $request, $product_group): JsonResponse
    {
        $client = app('client');
        $currency = $client->currency;

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

            $price_model = $product_option->prices()
                ->where('currency_uuid', $currency->uuid)
                ->where('period', 'monthly')
                ->first();

            $price = [];
            if ($price_model) {
                if ($price_model->setup) {
                    $price['setup'] = $currency->prefix.' '.number_format($price_model->setup, 2).' '.$currency->suffix;
                }
                if ($price_model->base) {
                    $price['base'] = $currency->prefix.' '.number_format($price_model->base, 2).' '.$currency->suffix;
                }
            }
            $icon = data_get($product_option, 'images.icon');
            $locations[strtolower($product_option->value)] = [
                'key' => $product_option->key,
                'name' => $product_option->name,
                'value' => strtolower($product_option->value),
                'icon' => $icon,
                'price' => $price,
            ];
        }

        ksort($locations);

        return response()->json([
            'status' => 'success',
            'data' => array_values($locations),
        ]);
    }

    public function controller_GetProducts(Request $request, $product_group): JsonResponse
    {

        $client = app('client');
        $currency = $client->currency;

        $location_key = $request->get('location');

        $location_product_option_group_uuids = ProductOption::query()->where('value',
            $location_key)->pluck('product_option_group_uuid')->toArray();

        $product_models = $product_group->products()
            ->whereHas('productOptionGroups', function ($query) use ($location_product_option_group_uuids) {
                $query->whereIn('product_option_group_uuid', $location_product_option_group_uuids);
            })
            ->get()
            ->sortBy(function ($product) {
                return $product->pivot->order;
            });

        $products = [];

        foreach ($product_models as $product_model) {

            $price_model = $product_model->prices()
                ->where('currency_uuid', $currency->uuid)
                ->where('period', 'monthly')
                ->first();

            $price = 0;
            $price_string = '';
            if ($price_model) {
                if ($price_model->base) {
                    $price = $price_model->base ?? 0;
                    $price_string = $currency->prefix.' '.number_format($price_model->base ?? 0,
                            2).' '.$currency->suffix;
                }
            } else {
                $price = 0;
                $price_string = (string) $currency->prefix.' '.number_format(0, 2).' '.$currency->suffix;
            }

            $products[] = [
                'uuid' => $product_model->uuid,
                'name' => $product_model->name,
                'short_description' => $product_model->short_description,
                'description' => $product_model->description,
                'icon' => $product_model->images['icon'] ?? '',
                'price' => $price,
                'price_string' => $price_string,
                'product_price_uuid' => $price_model->uuid ?? '',
            ];
        }

        return response()->json([
            'data' => [
                'original' => [
                    'data' => $products,
                ],
            ],
        ]);
    }


    public function controller_ProductOptionGroupsByProduct(Request $request): JsonResponse
    {
        $product_uuid = $request->input('product_uuid');
        $product_price_uuid = $request->input('product_price_uuid');

        $product = Product::with('productOptionGroups.productOptions.prices')->find($product_uuid);
        $price = Price::find($product_price_uuid);

        if (empty($product) || empty($price)) {
            return response()->json([
                'data' => [],
            ]);
        }

        $groups = $this->getOptionGroupsWithPrices($product, $price);

        return response()->json([
            'data' => $groups,
        ]);
    }

    private function getOptionGroupsWithPrices(Product $product, Price $price): Collection
    {
        $currency = $price->currency;
        $configuration = $product->configuration;

        $user = app('user');

        $product_data = data_get($product, 'module.module.product_data');

        $option_types = [];
        $option_types['location'] = [
            'uuid' => $product_data['location_product_option_group_uuid'] ?? null,
        ];


        $cpu_attributes = $attributes['cpu'] ?? [];
        $ram_attributes = $attributes['ram'] ?? [];
        $system_disk_attributes = $attributes['rootfs'] ?? [];
        $additional_disk_attributes = $attributes['mp'] ?? [];
        $backup_count_attributes = $attributes['backup_count'] ?? [];

        $option_types['cpu'] = [
            'uuid' => $product_data['cpu_cores_product_option_group_uuid'] ?? null,
            'attributes' => $cpu_attributes,
        ];

        $option_types['ram'] = [
            'uuid' => $product_data['memory_product_option_group_uuid'] ?? null,
            'attributes' => $ram_attributes,
        ];

        $option_types['system_disk'] = [
            'uuid' => $product_data['rootfs_size_product_option_group_uuid'] ?? null,
            'attributes' => $system_disk_attributes,
        ];

        $option_types['disk'] = [
            'uuid' => $product_data['mp_size_product_option_group_uuid'] ?? null,
            'attributes' => $additional_disk_attributes,
        ];

        $option_types['backup_count'] = [
            'uuid' => $product_data['backup_count_product_option_group_uuid'] ?? null,
            'attributes' => $backup_count_attributes,
        ];

        return $product->productOptionGroups->sortBy(function ($group) {
            return $group->pivot->order ?? 0;
        })->values()->map(function ($group) use ($price, $currency, $user, $option_types) {
            $options = $group->productOptions->sortBy(function ($option) {
                return $option->order ?? 0;
            })->map(function ($option) use ($price, $currency, $user, $option_types) {
                $option->setLocale($user->language);
                $label = $option->name;

                $matched_price = $option->prices->first(function ($p) use ($price, $option_types) {
                    return $p->currency_uuid === $price->currency_uuid &&
                        $p->period === $price->period;
                });

                if ($matched_price) {
                    $prise_string = $currency->prefix.' '.$matched_price->base.' '.$currency->suffix;
                }

                return [
                    'uuid' => $option->uuid,
                    'key' => $label,
                    'value' => $option->value,
                    'price' => $matched_price->base ?? '',
                    'prise_string' => $prise_string ?? '',
                ];
            })->values();

            $group->setLocale($user->language);

            $type = 'other';
            $attributes = [];
            foreach ($option_types as $option_typ => $value) {
                if ($group->uuid === $value['uuid']) {
                    $type = $option_typ;
                    $attributes = $value['attributes'] ?? [];
                }
            }

            return [
                'uuid' => $group->uuid,
                'type' => $type,
                'attributes' => $attributes,
                'name' => $group->name,
                'short_description' => $group->short_description,
                'product_options' => $options,
            ];
        })->values();
    }

    public function controller_CalculateSummary(Request $request, $product_group): JsonResponse
    {
        $user = app('user');

        $productUuid = $request->input('selected_product_uuid');
        $productPriceUuid = $request->input('selected_product_price_uuid');
        $selected_location = $request->input('selectedLocation');
        $optionUuids = $request->all();

        $product = Product::where('uuid', $productUuid)->firstOrFail();
        $price = $product->prices()->where('uuid', $productPriceUuid)->firstOrFail();


        $location_product_option_group_uuid = data_get($product,
            'module.module.product_data.location_product_option_group_uuid');
        $location_product_option_group = $product->productOptionGroups()->where('uuid',
            $location_product_option_group_uuid)->first();

        if ($location_product_option_group) {
            $location_product_option_uuid = $location_product_option_group->productOptions()->where('value',
                $selected_location)->first()->uuid;
            $optionUuids[] = $location_product_option_uuid; //??????????????????????
        }

        $currency = $price->currency;

        $product->setLocale($user->language);
        $product_group->setLocale($user->language);

        $options = ProductOption::whereIn('uuid', $optionUuids)
            ->orderByRaw('FIELD(uuid, "'.implode('","', $optionUuids).'")')
            ->get();

        $product_option_groups = $product->productOptionGroups()->orderBy('order')->pluck('uuid')->toArray();

        $options = $options->sortBy(function ($option) use ($product_option_groups) {
            return array_search($option->product_option_group_uuid, $product_option_groups);
        })->values();

        $total = (float) $price->base;
        $setup_fee = (float) $price->setup;

        $optionsOutput = [];

        foreach ($options as $option) {
            $option->setLocale($user->language);
            $option_price = $option->prices()
                ->where('period', $price->period)
                ->where('currency_uuid', $price->currency_uuid)
                ->first();

            $base = 0.00;

            if ($option_price) {
                if ($option_price->base) {
                    $base = (float) $option_price->base;
                    $total += $base;
                }

                if ($option_price->setup_fee) {
                    $setup_fee += (float) $option_price->setup_fee;
                }
            }

            $optionsOutput[] = [
                'group_label' => $option->productOptionGroup->name,
                'label' => $option->name,
                'price' => $currency->prefix.' '.number_format($base, 2).' '.$currency->suffix,
            ];
        }

        return response()->json([
            'data' => [
                'product_name' => $product->name,
                'product_group' => $product_group->name,
                'price' => $currency->prefix.' '.number_format($price->base, 2).' '.$currency->suffix,
                'options' => $optionsOutput,
                'setup_fee' => $currency->prefix.' '.number_format($setup_fee, 2).' '.$currency->suffix,
                'period' => __('main.'.$price->period),
                'total' => $currency->prefix.' '.number_format($total, 2).' '.$currency->suffix,
            ],
        ]);
    }


    public function controller_CreateService(Request $request, $product_group): JsonResponse
    {

        $expected_fields = [
            "selected_product_uuid",
            "selected_product_price_uuid",
            "location_product_option_uuid",
            "cpu_cores_product_option_uuid",
            "memory_product_option_uuid",
            "mp_size_product_option_uuid",
            "backup_count_product_option_group_uuid",
        ];

        $client = app('client');

        $product = Product::query()->where('uuid', $request->get('selected_product_uuid'))->first();
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The selected product was not found')],
            ], 404);
        }

        $product_price = $product->prices()->where('uuid', $request->get('selected_product_price_uuid'))->first();
        if (!$product_price) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The selected product price was not found')],
            ], 404);
        }

        $product_data = data_get($product, 'module.module.product_data');

        $option_uuids = [];


        // Location
        if (!$product->hasProductOption(
            $product_data['location_product_option_group_uuid'] ?? null,
            $request->get('location_product_option_uuid'))) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The selected Location is not available')],
            ], 404);
        }
        $option_uuids[$product_data['location_product_option_group_uuid']] = $request->get('location_product_option_uuid');

        // CPU
        if (!empty($product_data['cpu_cores_product_option_group_uuid'])) {
            if (!$product->hasProductOption(
                $product_data['cpu_cores_product_option_group_uuid'] ?? null,
                $request->get('cpu_cores_product_option_uuid'))) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.The selected CPU Cores is not available')],
                ], 404);
            }
            $option_uuids[$product_data['cpu_cores_product_option_group_uuid']] = $request->get('cpu_cores_product_option_uuid');
        }

        // RAM
        if (!empty($product_data['memory_product_option_group_uuid'])) {
            if (!$product->hasProductOption(
                $product_data['memory_product_option_group_uuid'] ?? null,
                $request->get('memory_product_option_uuid'))) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.The selected RAM is not available')],
                ], 404);
            }
            $option_uuids[$product_data['memory_product_option_group_uuid']] = $request->get('memory_product_option_uuid');
        }

        // Additional Disk
        if (!empty($product_data['mp_size_product_option_group_uuid'])) {
            if (!$product->hasProductOption(
                $product_data['mp_size_product_option_group_uuid'] ?? null,
                $request->get('mp_size_product_option_uuid'))) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.The selected Additional Disk Size is not available')],
                ], 404);
            }
            $option_uuids[$product_data['mp_size_product_option_group_uuid']] = $request->get('mp_size_product_option_uuid');
        }

        // Backup Count
        if (!empty($product_data['backup_count_product_option_group_uuid'])) {
            if (!$product->hasProductOption(
                $product_data['backup_count_product_option_group_uuid'] ?? null,
                $request->get('backup_count_product_option_uuid'))) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.The selected Backup Count is not available')],
                ], 404);
            }
            $option_uuids[$product_data['backup_count_product_option_group_uuid']] = $request->get('backup_count_product_option_uuid');
        }

        // Add addition product options
        foreach ($request->all() as $key => $value) {
            if (!in_array($key, $expected_fields)) {
                if ($product->hasProductOption($key, $value)) {
                    $option_uuids[$key] = $value;
                }
            }
        }

        $data = [
            'client_uuid' => $client->uuid,
            'product_uuid' => $product->uuid,
            'product_price_uuid' => $product_price->uuid,
            'option_uuids' => $option_uuids,
        ];

        $result = Service::createFromArray($data);

        if ($result['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'],
            ],
                $result['code']
            );
        }

        $service = $result['data'];

        $service = Service::find($service->uuid);
        $service->create();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'redirect' => route('client.web.panel.cloud.group', $product_group->uuid),
        ]);
    }
}
