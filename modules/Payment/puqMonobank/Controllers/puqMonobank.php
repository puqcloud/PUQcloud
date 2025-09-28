<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Dmytro Kravchenko <dmytro@kravchenko.im>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace Modules\Payment\puqMonobank\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class puqMonobank extends Controller
{
    /**
     * Test connection to Monobank API
     *
     * @param Request $request
     * @param string $uuid Payment gateway UUID
     * @return JsonResponse Test result
     */
    public function testConnection(Request $request, string $uuid): JsonResponse
    {
        try {
            // Get payment gateway from database
            $paymentGateway = PaymentGateway::where('uuid', $uuid)->first();
            if (!$paymentGateway) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Payment.puqMonobank.Payment gateway not found'),
                ], 404);
            }

            $savedModuleData = $paymentGateway->module_data ?? [];
            
            // Get test configuration from request or saved data
            $testConfig = [
                'sandbox_mode' => $request->boolean('sandbox_mode'),
                'production_token' => $request->input('production_token') ?: ($savedModuleData['production_token'] ?? ''),
                'sandbox_token' => $request->input('sandbox_token') ?: ($savedModuleData['sandbox_token'] ?? ''),
                'cms_name' => 'PUQcloud',
                'cms_version' => '1.0.0',
            ];

            // Decrypt tokens if they are encrypted (from saved data)
            if (!empty($testConfig['production_token']) && !$request->input('production_token')) {
                try {
                    $testConfig['production_token'] = Crypt::decryptString($testConfig['production_token']);
                } catch (\Exception $e) {
                    // Token is not encrypted, use as is
                }
            }
            
            if (!empty($testConfig['sandbox_token']) && !$request->input('sandbox_token')) {
                try {
                    $testConfig['sandbox_token'] = Crypt::decryptString($testConfig['sandbox_token']);
                } catch (\Exception $e) {
                    // Token is not encrypted, use as is
                }
            }

            // Validate required token
            $requiredToken = $testConfig['sandbox_mode'] ? 'sandbox_token' : 'production_token';
            if (empty($testConfig[$requiredToken])) {
                $tokenType = $testConfig['sandbox_mode'] ? 'sandbox' : 'production';
                return response()->json([
                    'status' => 'error',
                    'message' => __('Payment.puqMonobank.:token_type token is required for testing', ['token_type' => ucfirst($tokenType)]),
                ], 400);
            }

            // Create API client and test connection
            $monobank = new \Modules\Payment\puqMonobank\Services\MonobankClient($testConfig);
            $result = $monobank->testConnection();

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => __('Payment.puqMonobank.Connection successful'),
                    'merchant_name' => $result['merchant_name'] ?? '',
                    'merchant_id' => $result['merchant_id'] ?? '',
                    'api_mode' => $result['api_mode'] ?? '',
                    'endpoint_used' => $testConfig['sandbox_mode'] ? 'Sandbox (Test)' : 'Production',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error'] ?? __('Payment.puqMonobank.Connection failed'),
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR',
                    'api_mode' => $result['api_mode'] ?? '',
                    'endpoint_used' => $testConfig['sandbox_mode'] ? 'Sandbox (Test)' : 'Production',
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Payment.puqMonobank.Test failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch public key from Monobank API
     *
     * @param Request $request
     * @param string $uuid Payment gateway UUID
     * @return JsonResponse Fetch result
     */
    public function fetchPublicKey(Request $request, string $uuid): JsonResponse
    {
        try {
            // Get payment gateway from database
            $paymentGateway = PaymentGateway::where('uuid', $uuid)->first();
            if (!$paymentGateway) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Payment.puqMonobank.Payment gateway not found'),
                ], 404);
            }

            $savedModuleData = $paymentGateway->module_data ?? [];
            
            // Get configuration from request or saved data
            $sandboxMode = $request->boolean('sandbox_mode');
            $productionToken = $request->input('production_token') ?: ($savedModuleData['production_token'] ?? '');
            $sandboxToken = $request->input('sandbox_token') ?: ($savedModuleData['sandbox_token'] ?? '');

            // Decrypt tokens if they are encrypted (from saved data)
            if (!empty($productionToken) && !$request->input('production_token')) {
                try {
                    $productionToken = Crypt::decryptString($productionToken);
                } catch (\Exception $e) {
                    // Token is not encrypted, use as is
                }
            }
            
            if (!empty($sandboxToken) && !$request->input('sandbox_token')) {
                try {
                    $sandboxToken = Crypt::decryptString($sandboxToken);
                } catch (\Exception $e) {
                    // Token is not encrypted, use as is
                }
            }

            // Validate required token
            $requiredToken = $sandboxMode ? $sandboxToken : $productionToken;
            if (empty($requiredToken)) {
                $tokenType = $sandboxMode ? 'sandbox' : 'production';
                return response()->json([
                    'status' => 'error',
                    'message' => __('Payment.puqMonobank.:token_type token is required for fetching public key', ['token_type' => ucfirst($tokenType)]),
                ], 400);
            }

            // Fetch public key from Monobank API
            $pubKeyUrl = 'https://api.monobank.ua/api/merchant/pubkey';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-Token: ' . $requiredToken
            ));

            $pubKeyResponse = curl_exec($ch);
            $pubKeyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $pubKeyCurlError = curl_error($ch);
            curl_close($ch);

            if ($pubKeyHttpCode !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to retrieve public key: HTTP ' . $pubKeyHttpCode,
                    'error_code' => 'API_ERROR',
                    'details' => [
                        'curl_error' => $pubKeyCurlError,
                        'response' => $pubKeyResponse
                    ]
                ], 400);
            }

            $pubKeyData = json_decode($pubKeyResponse, true);
            if (!isset($pubKeyData['key'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid response format from API',
                    'error_code' => 'INVALID_RESPONSE',
                    'details' => [
                        'response' => $pubKeyResponse
                    ]
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'public_key' => $pubKeyData['key'],
                'message' => __('Payment.puqMonobank.Public key fetched successfully'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Payment.puqMonobank.Fetch failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }
} 