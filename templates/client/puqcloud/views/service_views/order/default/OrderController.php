<?php

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderController
{
    public function __construct()
    {
    }

    public function controller_ProductsSelect(Request $request, $product_group): JsonResponse
    {
        $search = $request->input('q');

        $existingProductUuids = DB::table('product_x_product_group')
            ->where('product_group_uuid', $product_group->uuid)
            ->pluck('product_uuid')
            ->toArray();

        $products = Product::whereIn('uuid', $existingProductUuids)->get();

        if (!empty($search)) {
            $products = $products->filter(function ($product) use ($search) {
                $search = strtolower($search);

                return str_contains(strtolower($product->uuid), $search)
                    || str_contains(strtolower($product->key), $search)
                    || str_contains(strtolower($product->name), $search);
            });
        }

        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->uuid,
                'text' => $product->name,
            ];
        }

        return response()->json([
            'data' => [
                'results' => array_values($results),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ]);
    }

    public function controller_ProductPricesSelect(Request $request, $product_group): JsonResponse
    {
        $client = app('client');

        $product_uuid = $request->input('product_uuid');
        $product = Product::find($product_uuid);

        $prices = $product->prices()->where('currency_uuid', $client->currency_uuid)->get();

        $periodOrder = [
            'one-time',
            'hourly',
            'daily',
            'weekly',
            'bi-weekly',
            'monthly',
            'quarterly',
            'semi-annually',
            'annually',
            'biennially',
            'triennially',
        ];

        $prices = $prices->sort(function ($a, $b) use ($periodOrder) {
            return array_search($a->period, $periodOrder) <=> array_search($b->period, $periodOrder);
        });

        $results = [];
        foreach ($prices as $price) {
            $currency = $price->currency;
            $results[] = [
                'id' => $price->uuid,
                'text' => $currency->prefix.' '.$price->base.' '.$currency->suffix.' / '.__('main.'.$price->period),
            ];
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => ['more' => false],
            ],
        ], 200);
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
        $user = app('user');

        return $product->productOptionGroups->sortBy(function ($group) {
            return $group->pivot->order ?? 0;
        })->values()->map(function ($group) use ($price, $currency, $user) {
            $options = $group->productOptions->sortBy(function ($option) {
                return $option->order ?? 0;
            })->map(function ($option) use ($price, $currency, $user) {
                $option->setLocale($user->language);
                $label = $option->name;

                $matched_price = $option->prices->first(function ($p) use ($price) {
                    return $p->currency_uuid === $price->currency_uuid &&
                        $p->period === $price->period;
                });

                if ($matched_price) {
                    $label .= ' - '.$currency->prefix.' '.$matched_price->base.' '.$currency->suffix.' / '.__('main.'.$matched_price->period);
                }

                return [
                    'uuid' => $option->uuid,
                    'key' => $label,
                ];
            })->values();

            $group->setLocale($user->language);

            return [
                'uuid' => $group->uuid,
                'key' => $group->key,
                'name' => $group->name,
                'short_description' => $group->short_description,
                'product_options' => $options,
            ];
        })->values();
    }

    public function controller_CalculateSummary(Request $request, $product_group)
    {
        $user = app('user');
        $productUuid = $request->input('product_uuid');
        $productPriceUuid = $request->input('product_price_uuid');
        $optionUuids = $request->input('option_uuids', []);

        $product = Product::where('uuid', $productUuid)->firstOrFail();
        $price = Price::where('uuid', $productPriceUuid)->firstOrFail();
        $currency = $price->currency;

        $product->setLocale($user->language);
        $product_group->setLocale($user->language);

        $options = ProductOption::whereIn('uuid', $optionUuids)
            ->orderByRaw('FIELD(uuid, "'.implode('","', $optionUuids).'")')
            ->get();

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
            "product_uuid", "product_price_uuid",
        ];

        $client = app('client');

        $product = Product::query()->where('uuid', $request->get('product_uuid'))->first();
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The selected product was not found')],
            ], 404);
        }

        $product_price = $product->prices()->where('uuid', $request->get('product_price_uuid'))->first();
        if (!$product_price) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The selected product price was not found')],
            ], 404);
        }

        $option_uuids = [];
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

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'redirect' => route('client.web.panel.cloud.group', $product_group->uuid),
        ]);
    }
}
