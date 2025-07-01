<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace Modules\Payment\puqStripe\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\puqStripe\Services\StripeClient;

class puqStripe extends Controller
{
    public function testConnection(Request $request, $uuid): JsonResponse
    {
        if ($request->get('sandbox') == 'yes') {
            $sandbox = true;
        } else {
            $sandbox = false;
        }

        $stripe = new StripeClient([
            'sandbox' => $sandbox,
            'sandbox_publishable_key' => $request->get('sandbox_publishable_key'),
            'publishable_key' => $request->get('publishable_key'),
            'sandbox_secret_key' => $request->get('sandbox_secret_key'),
            'secret_key' => $request->get('secret_key'),
            'sandbox_webhook_secret' => $request->get('sandbox_webhook_secret'),
            'webhook_secret' => $request->get('webhook_secret'),
        ]);

        $result = $stripe->testConnection();

        if ($result['status'] == 'success') {
            return response()->json([
                'status' => 'success',
                'message' => __('Payment.puqStripe.Access Available'),
                'data' => $result['data'] ?? '',
            ], $result['code'] ?? 200);
        }

        return response()->json([
            'status' => 'error',
            'errors' => $result['errors'],
        ], $result['code'] ?? 500);

    }
}
