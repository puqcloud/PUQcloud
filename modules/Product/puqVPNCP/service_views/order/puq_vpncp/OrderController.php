<?php

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * puqVPNCP OrderController.
 *
 * Implements the VPN-specific order flow (location picker → products filtered by location →
 * options → summary). Generic bits (select2 product/price lists, option groups, standard
 * summary math) delegate to Product / Service model helpers so only the VPN-specific glue
 * (location option group resolution, bandwidth + protocols metadata) lives here.
 */
class OrderController
{
    public function __construct() {}

    /**
     * List available VPN locations across products in the group.
     * Delegates to ProductGroup::getProductDataOptions() — shared with puqProxmox and any other
     * module that has a location picker.
     */
    public function controller_GetLocations(Request $request, $product_group): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $product_group->getProductDataOptions('location_product_option_group_uuid', app('client')),
        ]);
    }

    /**
     * VPN-specific: list products in the group filtered by the selected location, with bandwidth
     * and protocols metadata attached for the DataTable display.
     */
    public function controller_GetProducts(Request $request, $product_group): JsonResponse
    {
        $client = app('client');
        $currency = $client->currency;

        $location_key = $request->get('location');

        if (empty($location_key)) {
            return response()->json([
                'data' => ['original' => ['data' => []]],
            ]);
        }

        $location_product_option_group_uuids = ProductOption::query()
            ->whereRaw('LOWER(value) = ?', [strtolower($location_key)])
            ->pluck('product_option_group_uuid')
            ->toArray();

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
            $product_data = data_get($product_model, 'module.module.product_data') ?? [];

            // Available billing cycles come from the shared core helper. The UI shows these as
            // a period selector so the client can pick monthly / quarterly / annually / etc.
            $billingPeriods = $product_model->getAvailableBillingPeriods($client);
            if (empty($billingPeriods)) {
                // No prices configured in the client's currency — skip this product, it's unorderable.
                continue;
            }

            // Default to monthly when available (most common starting cycle) so the DataTable
            // row shows a stable "from" price until the user picks a period.
            $defaultPeriod = null;
            foreach ($billingPeriods as $bp) {
                if ($bp['period'] === 'monthly') {
                    $defaultPeriod = $bp;
                    break;
                }
            }
            if ($defaultPeriod === null) {
                $defaultPeriod = $billingPeriods[0];
            }

            $b_download = $product_data['b_download'] ?? 0;
            $b_upload = $product_data['b_upload'] ?? 0;

            // Protocols via the shared Product::getAttributesByGroup() helper.
            $protocols = [];
            $protocolsGroupUuid = $product_data['protocols_product_attribute_group_uuid'] ?? null;
            if ($protocolsGroupUuid) {
                foreach ($product_model->getAttributesByGroup($protocolsGroupUuid) as $attr) {
                    $protocols[] = $attr->name;
                }
            }

            $products[] = [
                'uuid' => $product_model->uuid,
                'name' => $product_model->name,
                'b_download' => $b_download,
                'b_upload' => $b_upload,
                'protocols' => $protocols,
                // Default row price = the "starting" cycle (monthly if available).
                'price' => $defaultPeriod['base'],
                'price_string' => $defaultPeriod['base_str'],
                'product_price_uuid' => $defaultPeriod['price_uuid'],
                'default_period' => $defaultPeriod['period'],
                'billing_periods' => $billingPeriods,
                'billing_mode' => $product_model->billing_mode ?? 'prepaid',
            ];
        }

        return response()->json([
            'data' => ['original' => ['data' => $products]],
        ]);
    }

    /**
     * Generic: select2-compatible product list (used by admin/backoffice flows).
     */
    public function controller_ProductsSelect(Request $request, $product_group): JsonResponse
    {
        $search = $request->input('q');

        $existingProductUuids = DB::table('product_x_product_group')
            ->where('product_group_uuid', $product_group->uuid)
            ->pluck('product_uuid')
            ->toArray();

        $products = Product::whereIn('uuid', $existingProductUuids)->get();

        if (! empty($search)) {
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
                'billing_mode' => $product->billing_mode ?? 'prepaid',
            ];
        }

        return response()->json([
            'data' => [
                'results' => array_values($results),
                'pagination' => ['more' => false],
            ],
        ]);
    }

    /**
     * Generic: select2-compatible billing cycle list in the current client's currency.
     * Delegates to Product::getAvailableBillingPeriods() — same helper used by the period
     * selector on the order form, so the two stay in sync by construction.
     */
    public function controller_ProductPricesSelect(Request $request, $product_group): JsonResponse
    {
        $client = app('client');
        $product = Product::find($request->input('product_uuid'));

        if (! $product) {
            return response()->json(['data' => ['results' => [], 'pagination' => ['more' => false]]]);
        }

        $results = [];
        foreach ($product->getAvailableBillingPeriods($client) as $period) {
            $results[] = [
                'id' => $period['price_uuid'],
                'text' => $period['base_str'].' / '.$period['label'],
            ];
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => ['more' => false],
            ],
        ], 200);
    }

    /**
     * Generic: option groups for a product + price combination, with location type detection.
     */
    public function controller_ProductOptionGroupsByProduct(Request $request): JsonResponse
    {
        $product = Product::with('productOptionGroups.productOptions.prices')
            ->find($request->input('product_uuid'));
        $price = Price::find($request->input('product_price_uuid'));

        if (empty($product) || empty($price)) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $product->getOptionGroupsForPrice($price),
        ]);
    }

    /**
     * Calculate summary with the VPN-specific quirks (location injection + group_label in output)
     * and tax handling based on billing_mode (invoice = taxed, prepaid = net).
     */
    public function controller_CalculateSummary(Request $request, $product_group): JsonResponse
    {
        $user = app('user');
        $client = app('client');
        $selected_location = $request->input('selectedLocation');

        $product = Product::where('uuid', $request->input('selected_product_uuid'))->firstOrFail();
        $price = $product->prices()->where('uuid', $request->input('selected_product_price_uuid'))->firstOrFail();

        // Module-specific: resolve the selected location option (identified by its `value` field
        // from the URL) and prepend its UUID to the option list. Every other option comes from the
        // form request as-is — we just add the location that the location picker knows about but
        // the Options form doesn't include as a visible field.
        $optionUuids = $request->all();
        $locationGroupUuid = data_get($product, 'module.module.product_data.location_product_option_group_uuid');
        if ($locationGroupUuid) {
            $locationGroup = $product->productOptionGroups()->where('uuid', $locationGroupUuid)->first();
            if ($locationGroup) {
                $locationOption = $locationGroup->productOptions()->where('value', $selected_location)->first();
                if ($locationOption) {
                    $optionUuids[] = $locationOption->uuid;
                }
            }
        }

        $product_group->setLocale($user->language);

        // Delegate all the math + formatting + tax logic to the shared core helper.
        $summary = $client->calculateOrderSummary(
            $product,
            $price,
            $optionUuids,
            $request->input('billing_mode', 'prepaid')
        );
        $summary['product_group'] = $product_group->name;

        return response()->json(['data' => $summary]);
    }

    /**
     * Create a new VPN service from the order form. Validates the location option server-side.
     */
    public function controller_CreateService(Request $request, $product_group): JsonResponse
    {
        $expected_fields = [
            'selectedLocation',
            'selected_product_uuid',
            'selected_product_price_uuid',
            'location_product_option_uuid',
        ];

        $client = app('client');

        $product = Product::query()->where('uuid', $request->get('selected_product_uuid'))->first();
        if (! $product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The selected product was not found')],
            ], 404);
        }

        $product_price = $product->prices()->where('uuid', $request->get('selected_product_price_uuid'))->first();
        if (! $product_price) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The selected product price was not found')],
            ], 404);
        }

        $product_data = data_get($product, 'module.module.product_data');
        $option_uuids = [];

        // Location option validation.
        $location_product_option_group_uuid = $product_data['location_product_option_group_uuid'] ?? null;
        if ($location_product_option_group_uuid) {
            if (! $product->hasProductOption(
                $location_product_option_group_uuid,
                $request->get('location_product_option_uuid'))
            ) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('error.The selected Location is not available')],
                ], 404);
            }
            $option_uuids[$location_product_option_group_uuid] = $request->get('location_product_option_uuid');
        }

        // Any additional selected product options.
        foreach ($request->all() as $key => $value) {
            if (! in_array($key, $expected_fields)) {
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
            'billing_mode' => $request->get('billing_mode'),
        ];

        $result = Service::createFromArray($data);

        if ($result['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'],
            ], $result['code']);
        }

        $service = Service::find($result['data']->uuid);

        // Prepaid services fire the module create() hook immediately; invoice-mode services stay
        // pending until the invoice is paid.
        if ($service->billing_mode !== 'invoice') {
            $service->create();
        }

        // Invoice mode → invoice payment page. Prepaid mode → the newly created service
        // manage page so the client immediately sees the deploy spinner + live polling.
        if ($service->billing_mode === 'invoice') {
            $redirect = route('client.web.panel.cloud.group', $product_group->uuid);
            $invoiceItem = \App\Models\InvoiceItem::query()
                ->join('invoices', 'invoice_items.invoice_uuid', '=', 'invoices.uuid')
                ->where('invoice_items.service_uuid', $service->uuid)
                ->where('invoices.billing_purpose', 'service_billing')
                ->whereIn('invoices.status', ['unpaid'])
                ->select('invoices.uuid')
                ->first();
            if ($invoiceItem) {
                $redirect = route('client.web.panel.client.invoice.payment', ['uuid' => $invoiceItem->uuid]);
            }
        } else {
            $redirect = route('client.web.panel.cloud.service', ['uuid' => $service->uuid]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'redirect' => $redirect,
        ]);
    }
}
