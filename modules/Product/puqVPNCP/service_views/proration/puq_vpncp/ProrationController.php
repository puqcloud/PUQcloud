<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * puqVPNCP ProrationController.
 *
 * Same shape as the default ProrationController, but additionally requires:
 *   - provision_status === 'completed' (can't run while VPN is still being set up)
 *   - billing_mode === 'invoice' (proration has no meaning for prepaid in VPNCP UX)
 *
 * All heavy lifting (proration math, tax, transactional invoice cleanup, balance debit, billing
 * timestamp shift) lives on the Service model — this is a thin HTTP adapter.
 */
class ProrationController
{
    /**
     * Shared guard for both endpoints: requires provisioned + invoice mode in addition to the
     * generic mutable-state check.
     *
     * @return string|null Error message, or null to proceed.
     */
    private function ensureAllowed($service): ?string
    {
        return $service->validateMutable(requireProvisioned: true, requireInvoiceMode: true);
    }

    /**
     * Calculate proration preview with tax breakdown.
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
     * Execute proration alignment.
     * Delegates the full DB-locked flow to Service::applyProrationAlignment().
     */
    public function controller_Execute(Request $request, $service): JsonResponse
    {
        if ($err = $this->ensureAllowed($service)) {
            return response()->json(['errors' => [$err]], 422);
        }

        $targetDay = (int) $request->input('target_day', 1);
        $result = $service->applyProrationAlignment($targetDay, requireProvisioned: true);

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
