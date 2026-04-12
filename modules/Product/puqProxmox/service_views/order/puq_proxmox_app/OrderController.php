<?php

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * puqProxmox "app" OrderController.
 *
 * Thin HTTP adapter on top of shared helpers in Product / ProductGroup / Client models.
 * Proxmox-specific bits (location picker, product listing) stay here; everything else
 * (prices, option groups, tax, summary) delegates to the shared core infrastructure.
 */
class OrderController
{
    public function __construct() {}

    /**
     * List all available deployment locations across products in the group.
     * Delegates to ProductGroup::getProductDataOptions() so every module with a location
     * picker uses the exact same code path.
     */
    public function controller_GetLocations(Request $request, $product_group): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $product_group->getProductDataOptions('location_product_option_group_uuid', app('client')),
        ]);
    }

    /**
     * List products in the group filtered by a selected location.
     */
    public function controller_GetProducts(Request $request, $product_group): JsonResponse
    {
        $client = app('client');
        $currency = $client->currency;

        $location_key = $request->get('location');

        $location_product_option_group_uuids = ProductOption::query()
            ->where('value', $location_key)
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
            $price_model = $product_model->getPricesForClient($client, 'monthly')->first();

            $price = 0;
            $price_string = (string) $currency->prefix.' '.number_format(0, 2).' '.$currency->suffix;
            if ($price_model && $price_model->base) {
                $price = (float) $price_model->base;
                $price_string = $currency->prefix.' '.number_format($price_model->base, 2).' '.$currency->suffix;
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
                'billing_mode' => $product_model->billing_mode ?? 'prepaid',
            ];
        }

        return response()->json([
            'data' => ['original' => ['data' => $products]],
        ]);
    }

    /**
     * Option groups for a selected product/price, with Proxmox-specific type detection.
     * Delegates the heavy lifting to Product::getOptionGroupsForPrice() and just passes
     * the Proxmox-specific type map (cpu / ram / system_disk / backup_count).
     */
    public function controller_ProductOptionGroupsByProduct(Request $request): JsonResponse
    {
        $product = Product::with('productOptionGroups.productOptions.prices')
            ->find($request->input('product_uuid'));
        $price = Price::find($request->input('product_price_uuid'));

        if (empty($product) || empty($price)) {
            return response()->json(['data' => []]);
        }

        $productData = data_get($product, 'module.module.product_data') ?? [];

        // Proxmox-specific group type detection. These are UUIDs stored in product_data;
        // `getOptionGroupsForPrice` will tag each matching group with the type key.
        $extraTypes = [
            'cpu' => ['uuid' => $productData['cpu_cores_product_option_group_uuid'] ?? null, 'attributes' => []],
            'ram' => ['uuid' => $productData['memory_product_option_group_uuid'] ?? null, 'attributes' => []],
            'system_disk' => ['uuid' => $productData['rootfs_size_product_option_group_uuid'] ?? null, 'attributes' => []],
            'disk' => ['uuid' => $productData['mp_size_product_option_group_uuid'] ?? null, 'attributes' => []],
            'backup_count' => ['uuid' => $productData['backup_count_product_option_group_uuid'] ?? null, 'attributes' => []],
        ];

        return response()->json([
            'data' => $product->getOptionGroupsForPrice($price, $extraTypes),
        ]);
    }

    /**
     * Calculate order summary with location injection.
     * All math (options iteration, setup aggregation, tax, formatting) is delegated to
     * Client::calculateOrderSummary() so proxmox_app, proxmox_lxc, puqVPNCP and default all
     * produce identical summary structure by construction.
     */
    public function controller_CalculateSummary(Request $request, $product_group): JsonResponse
    {
        $user = app('user');
        $client = app('client');
        $selected_location = $request->input('selectedLocation');

        $product = Product::where('uuid', $request->input('selected_product_uuid'))->firstOrFail();
        $price = $product->prices()->where('uuid', $request->input('selected_product_price_uuid'))->firstOrFail();

        // Module-specific: append the location option (selected via the location picker)
        // to the form's option UUIDs so it gets included in the summary calculation.
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
     * Create a new Proxmox "app" service from the order form.
     */
    public function controller_CreateService(Request $request, $product_group): JsonResponse
    {
        $expected_fields = [
            'selected_product_uuid',
            'selected_product_price_uuid',
            'location_product_option_uuid',
            'cpu_cores_product_option_uuid',
            'memory_product_option_uuid',
            'mp_size_product_option_uuid',
            'backup_count_product_option_group_uuid',
        ];

        $client = app('client');

        $product = Product::query()->where('uuid', $request->get('selected_product_uuid'))->first();
        if (! $product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The selected product was not found')],
            ], 404);
        }

        $product_price = $product->prices()->where('uuid', $request->get('selected_product_price_uuid'))->first();
        if (! $product_price) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The selected product price was not found')],
            ], 404);
        }

        $product_data = data_get($product, 'module.module.product_data');

        $option_uuids = [];

        // Location (required)
        if (! $product->hasProductOption(
            $product_data['location_product_option_group_uuid'] ?? null,
            $request->get('location_product_option_uuid'))
        ) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.The selected Location is not available')],
            ], 404);
        }
        $option_uuids[$product_data['location_product_option_group_uuid']] = $request->get('location_product_option_uuid');

        // CPU (optional)
        if (! empty($product_data['cpu_cores_product_option_group_uuid'])) {
            if (! $product->hasProductOption(
                $product_data['cpu_cores_product_option_group_uuid'],
                $request->get('cpu_cores_product_option_uuid'))
            ) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.The selected CPU Cores is not available')],
                ], 404);
            }
            $option_uuids[$product_data['cpu_cores_product_option_group_uuid']] = $request->get('cpu_cores_product_option_uuid');
        }

        // RAM (optional)
        if (! empty($product_data['memory_product_option_group_uuid'])) {
            if (! $product->hasProductOption(
                $product_data['memory_product_option_group_uuid'],
                $request->get('memory_product_option_uuid'))
            ) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.The selected RAM is not available')],
                ], 404);
            }
            $option_uuids[$product_data['memory_product_option_group_uuid']] = $request->get('memory_product_option_uuid');
        }

        // Additional Disk (optional)
        if (! empty($product_data['mp_size_product_option_group_uuid'])) {
            if (! $product->hasProductOption(
                $product_data['mp_size_product_option_group_uuid'],
                $request->get('mp_size_product_option_uuid'))
            ) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.The selected Additional Disk Size is not available')],
                ], 404);
            }
            $option_uuids[$product_data['mp_size_product_option_group_uuid']] = $request->get('mp_size_product_option_uuid');
        }

        // Backup Count (optional)
        if (! empty($product_data['backup_count_product_option_group_uuid'])) {
            if (! $product->hasProductOption(
                $product_data['backup_count_product_option_group_uuid'],
                $request->get('backup_count_product_option_uuid'))
            ) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('Product.puqProxmox.The selected Backup Count is not available')],
                ], 404);
            }
            $option_uuids[$product_data['backup_count_product_option_group_uuid']] = $request->get('backup_count_product_option_uuid');
        }

        // Any additional options forwarded with the form.
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

        // Prepaid services fire the module create() hook immediately; invoice-mode services
        // stay pending until the invoice is paid.
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
