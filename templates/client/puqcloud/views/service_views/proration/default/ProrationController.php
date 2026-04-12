<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Default ProrationController for service_views.
 *
 * All business logic (validation, tax calculation, transactional invoice/balance application)
 * lives on the Service model — this controller is a thin HTTP adapter.
 *
 * IMPORTANT: Proration alignment is INVOICE-ONLY. Prepaid services are charged from the
 * client's balance at top-up time, so there's nothing to "align" for them. Both Calculate
 * and Execute enforce `requireInvoiceMode: true` so a crafted API request from a prepaid
 * service can never execute a proration. The `cloudServiceProration` / `cloudServiceProrationApi`
 * routes in CloudController also reject prepaid upstream — this is defense in depth.
 */
class ProrationController
{
    /**
     * Shared guard for both endpoints.
     * Returns a localised error string when proration is not allowed, or null to proceed.
     */
    private function ensureAllowed($service): ?string
    {
        return $service->validateMutable(requireInvoiceMode: true);
    }

    /**
     * Calculate proration preview (billing date alignment). Invoice-mode only.
     */
    public function controller_Calculate(Request $request, $service): JsonResponse
    {
        if ($err = $this->ensureAllowed($service)) {
            return response()->json(['errors' => [$err]], 422);
        }

        $targetDay = (int) $request->input('target_day', 1);
        if ($targetDay < 1 || $targetDay > 28) {
            return response()->json(['errors' => [__('error.Target day must be between 1 and 28')]], 400);
        }

        $proration = $service->calculateProrationAlignment($targetDay);
        if ($proration['status'] !== 'success') {
            return response()->json(['errors' => $proration['errors'] ?? ['Calculation error']], 500);
        }

        $currency = $service->price->currency;
        $proration['amount_str'] = formatCurrency($proration['amount'], $currency);

        $tax = $service->calculateTaxLines((float) $proration['amount']);
        $proration['taxes'] = $tax['lines'];
        $proration['tax_total'] = $tax['tax_total'];
        $proration['tax_total_str'] = $tax['tax_total_str'];
        $proration['gross_total'] = $tax['gross_total'];
        $proration['gross_total_str'] = $tax['gross_total_str'];

        return response()->json(['data' => $proration]);
    }

    /**
     * Execute proration alignment — invoice-mode only.
     * The entire DB-locked flow lives on Service::applyProrationAlignment() so every module
     * gets the same correctness guarantees without copy-pasting.
     */
    public function controller_Execute(Request $request, $service): JsonResponse
    {
        if ($err = $this->ensureAllowed($service)) {
            return response()->json(['errors' => [$err]], 422);
        }

        $targetDay = (int) $request->input('target_day', 1);
        $result = $service->applyProrationAlignment($targetDay);

        if (($result['status'] ?? 'error') !== 'success') {
            return response()->json(
                ['errors' => $result['errors'] ?? [__('error.Something went wrong')]],
                $result['code'] ?? 500
            );
        }

        unset($result['status']);

        return response()->json(['data' => $result]);
    }
}
