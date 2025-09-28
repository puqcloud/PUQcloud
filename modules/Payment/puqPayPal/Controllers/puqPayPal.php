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

namespace Modules\Payment\puqPayPal\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\puqPayPal\Services\PayPalClient;

class puqPayPal extends Controller
{
    public function testConnection(Request $request, $uuid): JsonResponse
    {
        if ($request->get('sandbox') == 'yes') {
            $sandbox = true;
        } else {
            $sandbox = false;
        }

        $paypal = new PayPalClient([
            'sandbox' => $sandbox,
            'client_id' => $request->get('client_id'),
            'secret' => $request->get('secret'),
            'sandbox_client_id' => $request->get('sandbox_client_id'),
            'sandbox_secret' => $request->get('sandbox_secret'),
        ]);

        $result = $paypal->request('GET', '/v1/reporting/balances');

        if ($result['status'] == 'success') {
            return response()->json([
                'status' => 'success',
                'message' => __('Payment.puqPayPal.Access Available'),
                'data' => $result['data'] ?? '',
            ], $result['code'] ?? 200);
        }

        return response()->json([
            'status' => 'error',
            'errors' => $result['errors'],
        ], $result['code'] ?? 500);

    }
}
