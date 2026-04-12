<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManageController
{
    public function __construct()
    {
    }


    public function controller_GetStatus(Request $request, $service): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $service->status,
        ]);
    }

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

    public function controller_TerminationRequest(Request $request, $service): JsonResponse
    {

        if ($service->termination_request) {
            return response()->json([
                'errors' => [__('error.The service is already awaiting terminate')],
            ], 409);
        }

        if (!in_array($service->status, ['active', 'pending', 'suspended'])) {
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

    public function controller_CancelTerminationRequest(Request $request, $service): JsonResponse
    {

        if (!in_array($service->status, ['active', 'pending', 'suspended'])) {
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

    public function controller_SwitchBillingMode(Request $request, $service): JsonResponse
    {
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
