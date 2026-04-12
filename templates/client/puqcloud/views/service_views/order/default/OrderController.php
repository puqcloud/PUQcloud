<?php

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Default OrderController for service_views.
 *
 * Generic select2/price/option/summary endpoints used by the default order flow. Modules that
 * need bespoke product listings (e.g. location-filtered grids with bandwidth metadata) can add
 * their own `controller_GetLocations` / `controller_GetProducts` methods without touching this
 * base — the generic helpers below live on the Product model (`getPricesForClient`,
 * `getOptionGroupsForPrice`, `getAttributesByGroup`) so custom overrides stay thin.
 */
class OrderController
{
    public function __construct() {}

    /**
     * Universal product listing for the default order template — returns every orderable
     * product in the group with its billing cycles in a DataTables-ready shape. No location
     * filter (the default template is module-agnostic). Modules with location-based order
     * flows implement their own controller_GetProducts.
     *
     * Response:
     *   { data: { original: { data: [ {uuid, name, short_description, icon, billing_mode,
     *             billing_periods, default_period, price, price_string, product_price_uuid}, ... ] } } }
     */
    public function controller_GetProducts(Request $request, $product_group): JsonResponse
    {
        return response()->json([
            'data' => [
                'original' => [
                    'data' => $product_group->getOrderableProducts(app('client')),
                ],
            ],
        ]);
    }

    /**
     * Select2-compatible product list for the current product group.
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
     * Select2-compatible billing cycle list for a chosen product, in the current client's currency.
     * Delegates to Product::getAvailableBillingPeriods() so the period set + sort order stay
     * consistent with every other module's order form.
     */
    public function controller_ProductPricesSelect(Request $request, $product_group): JsonResponse
    {
        $client = app('client');

        $product = Product::find($request->input('product_uuid'));

        if (! $product) {
            return response()->json([
                'data' => ['results' => [], 'pagination' => ['more' => false]],
            ]);
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
     * Option groups for a product + price combination.
     */
    public function controller_ProductOptionGroupsByProduct(Request $request): JsonResponse
    {
        $product_uuid = $request->input('product_uuid');
        $product_price_uuid = $request->input('product_price_uuid');

        $product = Product::with('productOptionGroups.productOptions.prices')->find($product_uuid);
        $price = Price::find($product_price_uuid);

        if (empty($product) || empty($price)) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $product->getOptionGroupsForPrice($price),
        ]);
    }

    /**
     * Calculate order summary: product base + options + setup fee + total (+ tax lines for invoice mode).
     *
     * Response fields:
     *   - product_name, product_group, period
     *   - price            — product base price (formatted)
     *   - options          — array of {label, price} (option line items)
     *   - setup_fee        — total setup fee (formatted)
     *   - billing_mode     — echoed back for JS convenience
     *   - subtotal         — numeric net total (base + options), without taxes
     *   - subtotal_str     — formatted subtotal
     *   - taxes            — array of tax lines (empty for prepaid and when no tax rules match)
     *   - tax_total        — numeric tax amount
     *   - tax_total_str    — formatted tax amount
     *   - total            — numeric final total (= subtotal + tax for invoice, = subtotal for prepaid)
     *   - total_str        — formatted final total (this is what the big summary number displays)
     */
    public function controller_CalculateSummary(Request $request, $product_group)
    {
        $user = app('user');
        $client = app('client');

        $product = Product::where('uuid', $request->input('product_uuid'))->firstOrFail();
        $price = Price::where('uuid', $request->input('product_price_uuid'))->firstOrFail();

        $product_group->setLocale($user->language);

        // Core helper does the heavy lifting (options iteration, setup aggregation, tax math,
        // formatting). Caller just appends the group name which is outside the helper's scope.
        $summary = $client->calculateOrderSummary(
            $product,
            $price,
            $request->input('option_uuids', []),
            $request->input('billing_mode', 'prepaid')
        );
        $summary['product_group'] = $product_group->name;

        return response()->json(['data' => $summary]);
    }

    /**
     * Create a new service from the order form submission.
     */
    public function controller_CreateService(Request $request, $product_group): JsonResponse
    {
        $expected_fields = ['product_uuid', 'product_price_uuid'];

        $client = app('client');

        $product = Product::query()->where('uuid', $request->get('product_uuid'))->first();
        if (! $product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The selected product was not found')],
            ], 404);
        }

        $product_price = $product->prices()->where('uuid', $request->get('product_price_uuid'))->first();
        if (! $product_price) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The selected product price was not found')],
            ], 404);
        }

        $option_uuids = [];
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

        // Prepaid services fire the module create() hook immediately so the client lands on
        // a "deploying..." page that polls for status. Invoice-mode services stay pending
        // until the invoice is paid — the invoice page is where they land instead.
        if ($service && $service->billing_mode !== 'invoice') {
            $service->create();
        }

        $redirect = route('client.web.panel.cloud.group', $product_group->uuid);
        if ($service && $service->billing_mode === 'invoice') {
            // Invoice mode → jump straight to the freshly generated invoice payment page.
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
        } elseif ($service) {
            // Prepaid mode → jump to the newly created service manage page so the client
            // immediately sees the deploying spinner + live polling for status updates.
            $redirect = route('client.web.panel.cloud.service', ['uuid' => $service->uuid]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'redirect' => $redirect,
        ]);
    }
}
