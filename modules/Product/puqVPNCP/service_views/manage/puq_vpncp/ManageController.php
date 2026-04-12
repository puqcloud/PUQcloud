<?php

use App\Traits\ServiceViewManageActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManageController
{
    // Generic service manage actions (SetLabel / TerminationRequest / CancelTerminationRequest)
    // come from the shared trait. SwitchBillingMode is overridden below to add the stricter
    // "provision_status === completed" guard required by the VPN lifecycle.
    use ServiceViewManageActions {
        controller_SwitchBillingMode as traitSwitchBillingMode;
    }

    public function __construct() {}

    /**
     * Override the trait's SwitchBillingMode to additionally require a fully provisioned service.
     * Switching billing mode while the VPN client is still being created mid-deploy would race
     * the background job, so we block it until provision_status === 'completed'.
     */
    public function controller_SwitchBillingMode(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable(requireProvisioned: true)) {
            return response()->json(['errors' => [$err]], 422);
        }

        return $this->traitSwitchBillingMode($request, $service);
    }

    /**
     * VPN-specific override of the trait's controller_GetStatus.
     *
     * On top of the core `getManageViewState()` state machine we add one extra check:
     * even when service.status = 'active' AND provision_status = 'completed', the
     * VPN client itself may not have been written to `provision_data.client_name` yet
     * (the background job completes in two phases — status flip first, provision_data
     * population second). Until that field is populated we want the client area to
     * keep showing the deploy spinner, so we downgrade the phase back to 'deploying'.
     *
     * Response shape matches the trait version so the blade JS reads `data.phase`
     * consistently across both default and VPN templates.
     */
    public function controller_GetStatus(Request $request, $service): JsonResponse
    {
        $state = $service->getManageViewState();

        // VPN-specific downgrade: `provision_data.client_name` missing → still deploying.
        if ($state['phase'] === 'active' && empty($service->provision_data['client_name'] ?? null)) {
            $state['phase'] = 'deploying';
            $state['should_poll'] = true;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'phase' => $state['phase'],
                'service_status' => $service->status,
                'provision_status' => $service->provision_status,
                'should_poll' => $state['should_poll'],
                'unpaid_invoice_url' => $state['unpaid_invoice_url'] ?? null,
            ],
        ]);
    }
}
