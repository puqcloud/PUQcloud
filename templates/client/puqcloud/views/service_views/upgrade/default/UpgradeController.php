<?php

use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Default UpgradeController for service_views.
 *
 * All filtering + proration + balance logic lives on the Service model so modules only need
 * to add metadata-specific enrichment (e.g. bandwidth, protocols) in their own overrides.
 */
class UpgradeController
{
    /**
     * Current plan info for the sidebar card.
     */
    public function controller_GetCurrent(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable()) {
            return response()->json(['errors' => [$err]], 400);
        }

        $currentProduct = $service->product;
        $currentPrice = $service->price;

        return response()->json([
            'data' => [
                'product_uuid' => $currentProduct->uuid,
                'product_name' => $currentProduct->name ?: $currentProduct->key,
                'price_uuid' => $currentPrice->uuid,
                'period' => $currentPrice->period,
                'base' => (float) $currentPrice->base,
                'base_str' => formatCurrency($currentPrice->base, $currentPrice->currency),
                'billing_mode' => $service->billing_mode,
                'billing_timestamp' => $service->billing_timestamp,
                'allow_proration_alignment' => (bool) $currentProduct->allow_proration_alignment,
            ],
        ]);
    }

    /**
     * Available upgrade/downgrade products as a DataTables-ready list.
     *
     * Uses Service::getAvailableUpgradeProducts() which handles module filtering, price matching,
     * direction detection, and allow_upgrade/allow_downgrade permission gating centrally.
     */
    public function controller_GetProducts(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable()) {
            return response()->json(['errors' => [$err]], 400);
        }

        $rows = $service->getAvailableUpgradeProducts()->map(function ($row) {
            return [
                'uuid' => $row['uuid'],
                'name' => $row['name'],
                'price' => $row['price_base'],
                'price_string' => $row['price_string'],
                'product_price_uuid' => $row['product_price_uuid'],
                'direction' => $row['direction'],
                // Localised period label (e.g. "Monthly") for the price column suffix.
                // `$row['price']` is the Eloquent Price model from getAvailableUpgradeProducts.
                'period' => isset($row['price']) ? __('main.'.$row['price']->period) : null,
            ];
        })->values();

        return response()->json([
            'data' => [
                'original' => [
                    'data' => $rows,
                ],
            ],
        ]);
    }

    /**
     * Calculate proration for a selected upgrade/downgrade target.
     */
    public function controller_CalculateProration(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable()) {
            return response()->json(['errors' => [$err]], 400);
        }

        $newProduct = Product::find($request->input('product_uuid'));
        $newPrice = Price::find($request->input('price_uuid'));

        if (! $newProduct || ! $newPrice) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $newOptionUuids = $request->input('option_uuids', []);
        $proration = $service->calculateUpgradeProration($newProduct, $newPrice, $newOptionUuids);

        if ($proration['status'] !== 'success') {
            return response()->json(['errors' => $proration['errors'] ?? ['Calculation error']], 500);
        }

        $currency = $service->price->currency;
        $proration['total_to_pay_str'] = formatCurrency($proration['total_to_pay'], $currency);
        $proration['old_price_str'] = formatCurrency($proration['old_price_amount'], $currency);
        $proration['new_price_str'] = formatCurrency($proration['new_price_amount'], $currency);
        $proration['credit_str'] = formatCurrency($proration['credit'], $currency);

        // Tax is only rendered for invoice-mode services. For prepaid, balances are already net
        // (tax was applied at top-up time) so charging it again on proration would double-count.
        if ($service->billing_mode === 'invoice') {
            $tax = $service->calculateTaxLines((float) $proration['total_to_pay']);
            $proration['taxes'] = $tax['lines'];
            $proration['tax_total'] = $tax['tax_total'];
            $proration['tax_total_str'] = $tax['tax_total_str'];
            $proration['gross_total'] = $tax['gross_total'];
            $proration['gross_total_str'] = $tax['gross_total_str'];
        } else {
            $proration['taxes'] = [];
            $proration['tax_total'] = 0;
            $proration['tax_total_str'] = formatCurrency(0, $currency);
            $proration['gross_total'] = (float) $proration['total_to_pay'];
            $proration['gross_total_str'] = formatCurrency($proration['total_to_pay'], $currency);
        }

        $proration['currency'] = [
            'prefix' => $currency->prefix ?? '',
            'suffix' => $currency->suffix ?? '',
        ];

        return response()->json(['data' => $proration]);
    }

    /**
     * Execute upgrade/downgrade change.
     */
    public function controller_ExecuteChange(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable()) {
            return response()->json(['errors' => [$err]], 400);
        }

        $newProduct = Product::find($request->input('product_uuid'));
        $newPrice = Price::find($request->input('price_uuid'));

        if (! $newProduct || ! $newPrice) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $newOptionUuids = $request->input('option_uuids', []);

        if ($service->billing_mode === 'invoice') {
            $invoice = $service->generateChangeInvoice($newProduct, $newPrice, $newOptionUuids);
            if (! $invoice) {
                return response()->json(['errors' => [__('error.Failed to generate invoice')]], 500);
            }

            return response()->json([
                'data' => [
                    'action' => 'invoice_created',
                    'invoice_uuid' => $invoice->uuid,
                    'redirect' => route('client.web.panel.client.invoice.payment', ['uuid' => $invoice->uuid]),
                    'message' => __('message.Invoice generated successfully'),
                ],
            ]);
        }

        // Prepaid: pre-check balance with a clean error before the authoritative check inside
        // executeImmediateChange (which runs under a DB lock).
        $proration = $service->calculateUpgradeProration($newProduct, $newPrice, $newOptionUuids);
        if ($proration['status'] !== 'success') {
            return response()->json([
                'errors' => $proration['errors'] ?? [__('error.Something went wrong')],
            ], 422);
        }

        $totalToPay = (float) ($proration['total_to_pay'] ?? 0);
        if ($totalToPay > 0 && ! $service->canAffordChange($totalToPay)) {
            return response()->json(['errors' => [__('error.Insufficient funds')]], 422);
        }

        $result = $service->executeImmediateChange($newProduct, $newPrice, $newOptionUuids);
        if ($result['status'] !== 'success') {
            return response()->json(['errors' => $result['errors'] ?? [__('error.Something went wrong')]], 500);
        }

        return response()->json([
            'data' => [
                'action' => 'changed',
                'message' => __('message.Service updated successfully'),
            ],
        ]);
    }

    /**
     * Get product option groups for a product (used by options step of upgrade flow).
     */
    public function controller_ProductOptionGroups(Request $request, $service): JsonResponse
    {
        $product = Product::find($request->input('product_uuid'));
        $price = Price::find($request->input('price_uuid'));

        if (! $product || ! $price) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $groups = $product->getOptionGroupsForPrice($price);

        return response()->json(['data' => $groups]);
    }
}
