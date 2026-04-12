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

namespace Modules\Payment\puqPrzelewy24\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\puqPrzelewy24\Services\Przelewy24Client;
use OpenApi\Attributes as OA;

class puqPrzelewy24 extends Controller
{
    #[OA\Post(
        path: 'admin.api.Payment.puqPrzelewy24.test_connection.post',
        description: 'Tests the connection to Przelewy24 API using provided credentials.',
        summary: 'Test Przelewy24 connection',
        security: [['apiKeyAuth' => []]],
        tags: ['puqPrzelewy24'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection test result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function testConnection(Request $request, $uuid): JsonResponse
    {
        if ($request->get('sandbox') == 'yes') {
            $sandbox = true;
        } else {
            $sandbox = false;
        }

        $stripe = new Przelewy24Client([
            'sandbox' => $sandbox,
            'merchant_id' => $request->get('merchant_id'),
            'pos_id' => $request->get('pos_id'),
            'crc' => $request->get('crc'),
            'api_key' => $request->get('api_key'),
            'sandbox_merchant_id' => $request->get('sandbox_merchant_id'),
            'sandbox_pos_id' => $request->get('sandbox_pos_id'),
            'sandbox_crc' => $request->get('sandbox_crc'),
            'sandbox_api_key' => $request->get('sandbox_api_key'),
        ]);

        $result = $stripe->testConnection();

        if ($result['status'] == 'success') {
            return response()->json([
                'status' => 'success',
                'message' => __('Payment.puqPrzelewy24.Access Available'),
                'data' => $result['data'] ?? '',
            ], $result['code'] ?? 200);
        }

        return response()->json([
            'status' => 'error',
            'errors' => $result['errors'],
        ], $result['code'] ?? 500);

    }
}
