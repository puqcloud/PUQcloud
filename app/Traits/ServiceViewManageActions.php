<?php

/*
 * PUQcloud - Free Cloud Billing System
 *
 * Shared trait for service_views/manage controllers.
 *
 * Gives every module's ManageController four generic endpoints (SetLabel, TerminationRequest,
 * CancelTerminationRequest, SwitchBillingMode) for free. Modules just `use` this trait and add
 * their own module-specific endpoints on top.
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 */

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ServiceViewManageActions
{
    /**
     * Return the current lifecycle phase of the service so the manage view JS can decide
     * whether to keep polling or reload when the state flips to `active`.
     *
     * The JS on the pending/deploying sub-view polls this endpoint every few seconds.
     * When the returned phase becomes `active`, the JS triggers `location.reload()` and
     * the full "active" content of the manage page renders.
     *
     * Modules with extra "not yet ready" conditions (e.g. puqVPNCP waits for
     * `provision_data.client_name` to be populated even after `status=active`) should
     * override this method in their own ManageController and add the check.
     */
    public function controller_GetStatus(Request $request, $service): JsonResponse
    {
        $state = $service->getManageViewState();

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

    /**
     * Update the client-visible label for a service.
     */
    public function controller_SetLabel(Request $request, $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_label' => 'required',
        ], [
            'client_label.required' => __('error.The Label is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $service->setClientLabel($request->get('client_label'));

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'redirect' => route('client.web.panel.cloud.service', $service->uuid),
        ]);
    }

    /**
     * Flag the service as pending termination.
     */
    public function controller_TerminationRequest(Request $request, $service): JsonResponse
    {
        if ($service->termination_request) {
            return response()->json([
                'errors' => [__('error.The service is already awaiting terminate')],
            ], 409);
        }

        if (! in_array($service->status, ['active', 'pending', 'suspended'])) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $service->termination_request = true;
        $service->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'redirect' => route('client.web.panel.cloud.service', $service->uuid),
        ]);
    }

    /**
     * Cancel a pending termination request.
     */
    public function controller_CancelTerminationRequest(Request $request, $service): JsonResponse
    {
        if (! in_array($service->status, ['active', 'pending', 'suspended'])) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $service->termination_request = false;
        $service->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'redirect' => route('client.web.panel.cloud.service', $service->uuid),
        ]);
    }

    /**
     * Switch a service between prepaid and invoice billing modes.
     *
     * The trait applies the MINIMAL guard (`active + !termination_request`) which matches the
     * historical default behaviour. Modules that need to additionally block this mid-provisioning
     * (e.g. puqVPNCP) should override this method in their own ManageController and call
     * `$service->validateMutable(requireProvisioned: true)` before the rest of the logic.
     */
    public function controller_SwitchBillingMode(Request $request, $service): JsonResponse
    {
        if ($err = $service->validateMutable()) {
            return response()->json(['errors' => [$err]], 422);
        }

        $newMode = $request->input('billing_mode');
        if (! $newMode || ! in_array($newMode, ['prepaid', 'invoice'])) {
            return response()->json(['errors' => [__('error.Invalid billing mode')]], 422);
        }

        try {
            $result = $service->switchBillingMode($newMode);
        } catch (\Throwable $e) {
            return response()->json(['errors' => [__('error.Something went wrong')]], 500);
        }

        if ($result['status'] === 'error') {
            return response()->json(['errors' => $result['errors']], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'redirect' => route('client.web.panel.cloud.service', $service->uuid),
        ]);
    }
}
