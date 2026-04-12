<?php

use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * puqVPNCP UpgradeController.
 *
 * Same flow as the default UpgradeController, plus:
 *   - requires provision_status === 'completed' for all endpoints
 *   - enriches each product row with VPN-specific metadata (b_download, b_upload, protocols)
 *
 * Generic logic (module filtering, price matching, direction detection, tax math, balance
 * pre-check) lives on the Service model — this controller only adds the VPN-specific bits.
 */
class UpgradeController
{
    /**
     * Extract VPN-specific bandwidth + protocols from a product's module data and attributes.
     * This is the only bit of logic that genuinely belongs in the module (not the core Service model).
     */
    private function extractProductMeta(Product $product): array
    {
        $pd = [];
        if ($product->module) {
            $product->module->moduleExecute('getProductData', $product->configuration);
            $pd = $product->module->module->product_data ?? [];
        }

        $protocols = [];
        $protocolsGroupUuid = $pd['protocols_product_attribute_group_uuid'] ?? null;
        if ($protocolsGroupUuid) {
            foreach ($product->getAttributesByGroup($protocolsGroupUuid) as $attr) {
                $protocols[] = $attr->name;
            }
        }

        return [
            'b_download' => (int) ($pd['b_download'] ?? 0),
            'b_upload' => (int) ($pd['b_upload'] ?? 0),
            'protocols' => $protocols,
        ];
    }

    /**
     * Current plan info for the sidebar card.
     */
    public function controller_GetCurrent(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable(requireProvisioned: true)) {
            return response()->json(['errors' => [$err]], 422);
        }

        $currentProduct = $service->product;
        $currentPrice = $service->price;
        $meta = $this->extractProductMeta($currentProduct);

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
                'b_download' => $meta['b_download'],
                'b_upload' => $meta['b_upload'],
                'protocols' => $meta['protocols'],
            ],
        ]);
    }

    /**
     * Available upgrade/downgrade products as a DataTables-ready list with VPN metadata.
     */
    public function controller_GetProducts(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable(requireProvisioned: true)) {
            return response()->json(['errors' => [$err]], 422);
        }

        $rows = $service->getAvailableUpgradeProducts()->map(function ($row) {
            $meta = $this->extractProductMeta($row['product']);

            return [
                'uuid' => $row['uuid'],
                'name' => $row['name'],
                'b_download' => $meta['b_download'],
                'b_upload' => $meta['b_upload'],
                'protocols' => $meta['protocols'],
                'price' => $row['price_base'],
                'price_string' => $row['price_string'],
                'product_price_uuid' => $row['product_price_uuid'],
                'direction' => $row['direction'],
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
     * Calculate proration preview for a selected target product/price.
     */
    public function controller_CalculateProration(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable(requireProvisioned: true)) {
            return response()->json(['errors' => [$err]], 422);
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

        // Tax is only shown for invoice-mode services — prepaid balances are already net so
        // applying tax again on proration would double-charge the client.
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
     * Execute upgrade/downgrade.
     */
    public function controller_ExecuteChange(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable(requireProvisioned: true)) {
            return response()->json(['errors' => [$err]], 422);
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

        // Prepaid: pre-check balance using the centralised helper before the authoritative check
        // inside executeImmediateChange (which runs under a DB lock).
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
}
