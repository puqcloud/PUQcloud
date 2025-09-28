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

use App\Modules\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentGateway;

class puqMonobank extends Payment
{
    public string $payment_gateway_uuid = '';
    public array $module_data = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get module configuration data
     *
     * @param array $data Configuration data
     * @return array Module configuration
     */
    public function getModuleData(array $data = []): array
    {
        $this->module_data = [
            // Production settings
            'production_token' => $data['production_token'] ?? '',
            
            // Sandbox settings
            'sandbox_mode' => $data['sandbox_mode'] ?? false,
            'sandbox_token' => $data['sandbox_token'] ?? '',
            
            // Webhook security
            'webhook_signature_verification' => $data['webhook_signature_verification'] ?? true,
            
            // Payment settings
            'payment_timeout' => $data['payment_timeout'] ?? 3600, // 1 hour default
            'payment_type' => $data['payment_type'] ?? 'debit', // debit or hold
            'auto_redirect' => $data['auto_redirect'] ?? true,
            'iframe_mode' => $data['iframe_mode'] ?? false,
            
            // Advanced settings
            'cms_name' => $data['cms_name'] ?? 'PUQcloud',
            'cms_version' => $data['cms_version'] ?? '1.0.0',
            
            // Supported features
            'supported_currencies' => ['UAH'],
            'max_amount' => 999999999, // in kopecks (9,999,999.99 UAH)
            'min_amount' => 100, // 1 UAH in kopecks
        ];

        return $this->module_data;
    }

    /**
     * Get settings page HTML for admin panel
     *
     * @param array $data Additional data for the view
     * @return string Settings page HTML
     */
    public function getSettingsPage(array $data = []): string
    {
        
        $this->logInfo('getSettingsPage', 'getSettingsPage', $data);
        
        // Use decrypted config so tokens can be prefilled in the form
        $module_data = $this->getDecryptedConfig();

        $this->logInfo('getSettingsPage', 'getSettingsPage', $module_data);
        
        // Check if tokens are configured (for security - don't show actual values)
        $module_data['production_token_exists'] = !empty($module_data['production_token']);
        $module_data['sandbox_token_exists'] = !empty($module_data['sandbox_token']);
        
        
        $data['module_data'] = $module_data;
        
        // Generate webhook URL
        $data['webhook_url'] = route('static.module.post', [
            'type' => 'Payment',
            'name' => 'puqMonobank',
            'method' => 'apiWebhookPost',
            'uuid' => $this->payment_gateway_uuid
        ]);

        return $this->view('configuration', $data);
    }

    /**
     * Save and validate module configuration
     *
     * @param array $data Configuration data to save
     * @return array Result of save operation
     */
    public function saveModuleData(array $data = []): array
    {
        // Load existing tokens from DB (source of truth) to avoid losing them on empty input
        $savedModuleData = [];
        try {
            // Resolve gateway UUID from multiple sources (best-effort)
            $resolvedUuid = $this->payment_gateway_uuid
                ?: ($data['payment_gateway_uuid'] ?? $data['uuid'] ?? null)
                ?: (function () {
                    try { return request()->route('uuid'); } catch (\Throwable $e) { return null; }
                })()
                ?: (function () {
                    try { return request()->input('uuid'); } catch (\Throwable $e) { return null; }
                })();

            if (!empty($resolvedUuid)) {
                $pg = PaymentGateway::where('uuid', $resolvedUuid)->first();
                if ($pg) {
                    $savedModuleData = is_array($pg->module_data) ? $pg->module_data : [];
                }
            }
        } catch (\Throwable $e) {
            // Best-effort; if DB not available, fallback to current module_data
        }
        // Relax base rules to allow keeping existing tokens when input is empty
        $validator = Validator::make($data, [
            'production_token' => 'nullable|string|min:10',
            'sandbox_token' => 'nullable|string|min:10',
            'sandbox_mode' => 'nullable|string',
            'webhook_signature_verification' => 'nullable|string',
            'payment_timeout' => 'integer|min:300|max:86400', // 5 min to 24 hours
            'payment_type' => 'in:debit,hold',
            'auto_redirect' => 'nullable|string',
            'iframe_mode' => 'nullable|string',
            'cms_name' => 'string|max:50',
            'cms_version' => 'string|max:20',
        ], [
            'production_token.min' => __('Payment.puqMonobank.Production token must be at least 10 characters'),
            'sandbox_token.min' => __('Payment.puqMonobank.Sandbox token must be at least 10 characters'),
            'payment_timeout.min' => __('Payment.puqMonobank.Payment timeout must be at least 5 minutes'),
            'payment_timeout.max' => __('Payment.puqMonobank.Payment timeout cannot exceed 24 hours'),
            'payment_type.in' => __('Payment.puqMonobank.Payment type must be either debit or hold'),
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ];
        }

        // No hard presence validation here: empty token means "keep current token"

        // Handle token updates - encrypt only if new token provided
        if (!empty($data['production_token'])) {
            $data['production_token'] = Crypt::encryptString($data['production_token']);
        } else {
            // Keep existing encrypted token from DB (fallback to in-memory module_data)
            $data['production_token'] = $savedModuleData['production_token']
                ?? ($this->module_data['production_token'] ?? '');
        }

        if (!empty($data['sandbox_token'])) {
            $data['sandbox_token'] = Crypt::encryptString($data['sandbox_token']);
        } else {
            // Keep existing encrypted token from DB (fallback to in-memory module_data)
            $data['sandbox_token'] = $savedModuleData['sandbox_token']
                ?? ($this->module_data['sandbox_token'] ?? '');
        }

        // Convert checkbox values: when checked = 'yes', when unchecked = '' (from hidden field)
        $data['sandbox_mode'] = isset($data['sandbox_mode']) && $data['sandbox_mode'] === 'yes';
        $data['auto_redirect'] = isset($data['auto_redirect']) && $data['auto_redirect'] === 'yes';
        $data['iframe_mode'] = isset($data['iframe_mode']) && $data['iframe_mode'] === 'yes';
        $data['webhook_signature_verification'] = isset($data['webhook_signature_verification']) && $data['webhook_signature_verification'] === 'yes';



        // Set default values that are needed for payment processing
        $data['supported_currencies'] = ['UAH'];
        $data['min_amount'] = intval($data['min_amount'] ?? 100); // 1 UAH in kopecks
        $data['max_amount'] = intval($data['max_amount'] ?? 1000000000); // 10,000,000 UAH in kopecks
        $data['cms_name'] = $data['cms_name'] ?? 'PUQcloud';
        $data['cms_version'] = $data['cms_version'] ?? '1.0.0';
        $data['payment_timeout'] = intval($data['payment_timeout'] ?? 3600); // 1 hour default
        $data['payment_type'] = $data['payment_type'] ?? 'debit';

        return [
            'status' => 'success',
            'data' => $data,
            'code' => 200,
        ];
    }

    /**
     * Generate payment form HTML for client area
     *
     * @param array $data Data containing invoice information
     * @return string Payment form HTML
     */
    public function getClientAreaHtml(array $data = []): string
    {
        $invoice = $data['invoice'];
        $amount = $invoice->getDueAmountAttribute();
        $currency = $invoice->client->currency->code;

        // Validate currency support
        if (!isset($this->module_data['supported_currencies']) || !in_array($currency, $this->module_data['supported_currencies'])) {

            return $this->view('unsupported_currency', [
                'currency' => $currency,
                'supported_currencies' => $this->module_data['supported_currencies']
            ]);
        }

        // Validate amount limits
        $amountInKopecks = $amount * 100;
        if ($amountInKopecks < $this->module_data['min_amount'] || $amountInKopecks > $this->module_data['max_amount']) {

            return $this->view('amount_error', [
                'amount' => $amount,
                'currency' => $currency,
                'min_amount' => $this->module_data['min_amount'] / 100,
                'max_amount' => $this->module_data['max_amount'] / 100,
            ]);
        }

        try {
            // Decrypt tokens for API client
            $config = $this->getDecryptedConfig();
            $monobank = new \Modules\Payment\puqMonobank\Services\MonobankClient($config);

            // Prepare payment data
            $paymentData = [
                'amount' => $amountInKopecks,
                'currency' => $this->getCurrencyCode($currency),
                'redirect_url' => route('client.web.panel.module.web', [
                    'type' => 'Payment',
                    'name' => 'puqMonobank',
                    'method' => 'returnUrl',
                    'uuid' => $this->payment_gateway_uuid
                ]) . '?ref=' . $invoice->uuid,
                'webhook_url' => route('static.module.post', [
                    'type' => 'Payment',
                    'name' => 'puqMonobank',
                    'method' => 'apiWebhookPost',
                    'uuid' => $this->payment_gateway_uuid
                ]),
                'validity' => $this->module_data['payment_timeout'],
                'payment_type' => $this->module_data['payment_type'],
                'merchant_info' => [
                    'reference' => $invoice->uuid,
                    'destination' => __('Payment.puqMonobank.Payment for invoice') . ' #' . $invoice->number,
                    'comment' => __('Payment.puqMonobank.Payment for services'),
                ]
            ];

            // Add iframe display type if enabled
            if ($this->module_data['iframe_mode']) {
                $paymentData['display_type'] = 'iframe';
            }



            $response = $monobank->createInvoice($paymentData);

            if ($response['status'] === 'success') {

                // Map our reference (invoice UUID) to Monobank invoiceId for 1 hour to enable API checks on returnUrl
                $this->saveRefToInvoiceMapping($invoice->uuid, $response['invoice_id']);

                return $this->view('client_area', [
                    'invoice' => $invoice,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_url' => $response['payment_url'],
                    'monobank_invoice_id' => $response['invoice_id'],
                    'iframe_mode' => $this->module_data['iframe_mode'],
                    'auto_redirect' => $this->module_data['auto_redirect'],
                ]);
            } else {

                return $this->view('payment_error', [
                    'error_message' => $response['error_message'] ?? __('Payment.puqMonobank.Failed to create payment'),
                    'error_code' => $response['error_code'] ?? 'UNKNOWN_ERROR'
                ]);
            }

        } catch (\Exception $e) {

            return $this->view('payment_error', [
                'error_message' => 'EXCEPTION: ' . $e->getMessage(),
                'error_code' => 'SYSTEM_ERROR',
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
    }

    /**
     * Handle webhook notifications from Monobank
     *
     * @return JsonResponse Webhook response
     */
    public function controllerClientStatic_apiWebhookPost(): JsonResponse
    {
        try {
            $input = request()->getContent();
            $webhookData = json_decode($input, true);

            if (empty($webhookData)) {
                $this->logError('webhook', 'Empty webhook data', $input);
                return response()->json(['error' => 'Invalid webhook data'], 400);
            }

            // Verify webhook signature if enabled
            if ($this->module_data['webhook_signature_verification'] ?? true) {
                $xSignHeader = request()->header('X-Sign');
                
                if (empty($xSignHeader)) {
                    $this->logError('webhook', 'X-Sign header missing from webhook request');
                    return response()->json(['error' => 'Missing signature header'], 400);
                }

                if (!$this->verifyWebhookSignature($input, $xSignHeader)) {
                    $this->logError('webhook', 'Webhook signature verification failed', [
                        'x_sign_header' => $xSignHeader,
                        'webhook_data' => $webhookData
                    ]);
                    return response()->json(['error' => 'Invalid signature'], 400);
                }
            }

            $this->logInfo('webhook', 'Received webhook', $webhookData);

            // Extract invoice information
            $monobankInvoiceId = $webhookData['invoiceId'] ?? '';
            $status = $webhookData['status'] ?? '';
            $amount = $webhookData['amount'] ?? 0;
            $finalAmount = $webhookData['finalAmount'] ?? $amount;
            $reference = $webhookData['reference'] ?? '';

            // Do not persist webhook payload; we'll always verify via Monobank API

            if (empty($monobankInvoiceId) || empty($status) || empty($reference)) {
                $this->logError('webhook', 'Missing required webhook fields', $webhookData);
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Process successful payment
            if ($status === 'success') {
                $paymentData = [
                    'invoice_uuid' => $reference, // We use invoice UUID as reference
                    'transaction_id' => $monobankInvoiceId,
                    'amount' => $finalAmount / 100, // Convert from kopecks to main currency units
                    'fee' => 0, // Monobank doesn't provide fee information in webhook
                    'method' => $this->module_name,
                    'description' => 'Monobank payment via ' . $this->module_name,
                ];

                // Add payment details if available
                if (!empty($webhookData['paymentInfo'])) {
                    $paymentInfo = $webhookData['paymentInfo'];
                    $paymentData['description'] .= sprintf(
                        ' - Card: %s, RRN: %s',
                        $paymentInfo['maskedPan'] ?? 'Unknown',
                        $paymentInfo['rrn'] ?? 'Unknown'
                    );
                }

                $result = $this->handleInvoicePayment($paymentData);

                if ($result['status'] === 'success') {
                    // Clear any cached failure state for this invoice UUID
                    $this->clearPaymentFailureStatus($reference);
                    $this->logInfo('webhook', 'Payment processed successfully', [
                        'invoice_uuid' => $reference,
                        'monobank_invoice_id' => $monobankInvoiceId,
                        'amount' => $finalAmount / 100
                    ]);

                    return response()->json(['status' => 'success']);
                } else {
                    $this->logError('webhook', 'Failed to process payment', [
                        'payment_data' => $paymentData,
                        'result' => $result
                    ]);

                    return response()->json(['error' => 'Payment processing failed'], 500);
                }
            }

            // Log other statuses for monitoring (no caching/persistence by request)
            if (in_array($status, ['failure', 'expired', 'reversed'])) {
                $failureData = [
                    'invoice_uuid' => $reference,
                    'monobank_invoice_id' => $monobankInvoiceId,
                    'status' => $status,
                    'failure_reason' => $webhookData['failureReason'] ?? null,
                    'error_code' => $webhookData['errCode'] ?? null,
                    'timestamp' => now()
                ];
                $this->logInfo('webhook', "Payment {$status}", $failureData);
            }

            return response()->json(['status' => 'received']);

        } catch (\Exception $e) {
            $this->logError('webhook', 'Exception processing webhook', $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle return URL from Monobank payment
     *
     * @param array $data Request data
     * @return array Return page data
     */
    public function controllerClientWeb_returnUrl(array $data = []): array
    {
        $title = __('Payment.puqMonobank.Payment Status');
        $request = $data['request'];
        
        // Log all URL parameters for debugging
        $this->logInfo('return_url_debug', 'All URL parameters', [
            'all_params' => $request->all(),
            'query_string' => $request->getQueryString(),
            'full_url' => $request->fullUrl()
        ]);
        
        // Monobank может передавать разные параметры, проверим все возможные варианты
        $invoiceId = $request->get('invoiceId') ?? $request->get('invoice_id') ?? $request->get('id') ?? '';
        if (is_array($invoiceId)) {
            $invoiceId = $invoiceId[0] ?? '';
        }
        
        $status = $request->get('status') ?? $request->get('state') ?? '';
        if (is_array($status)) {
            $status = $status[0] ?? '';
        }
        
        $errCode = $request->get('errCode') ?? $request->get('error_code') ?? $request->get('err') ?? '';
        if (is_array($errCode)) {
            $errCode = $errCode[0] ?? '';
        }
        
        // Получим ссылку на наш счет
        $invoiceUuid = $request->get('ref') ?? '';
        if (is_array($invoiceUuid)) {
            $invoiceUuid = $invoiceUuid[0] ?? '';
        }
        
        $this->logInfo('return_url_debug', 'Extracted parameters', [
            'invoiceId' => $invoiceId,
            'status' => $status,
            'errCode' => $errCode,
            'invoiceUuid' => $invoiceUuid
        ]);

        return [
            'blade' => 'return_url', 
            'variables' => [
                'invoiceId' => $invoiceId,
                'status' => $status,
                'errCode' => $errCode,
                'invoiceUuid' => $invoiceUuid,
                'payment_gateway_uuid' => $this->payment_gateway_uuid,
                'title' => $title,
            ]
        ];
    }

    /**
     * API endpoint to check payment status after return
     *
     * @param array $data Request data
     * @return JsonResponse Payment status check result
     */
    public function controllerClientApi_apiReturnUrlPost(array $data = []): JsonResponse
    {
        try {
            $request = $data['request'];
            
            // Ensure we get string values, not arrays
            $invoiceId = $request->get('invoiceId');
            if (is_array($invoiceId)) {
                $invoiceId = $invoiceId[0] ?? '';
            }
            
            $status = $request->get('status');
            if (is_array($status)) {
                $status = $status[0] ?? '';
            }
            
            $errCode = $request->get('errCode');
            if (is_array($errCode)) {
                $errCode = $errCode[0] ?? '';
            }
            
            $invoiceUuid = $request->get('invoiceUuid');
            if (is_array($invoiceUuid)) {
                $invoiceUuid = $invoiceUuid[0] ?? '';
            }

            // Если есть UUID нашего счета, но нет Monobank параметров - проверим через API
            if (!$invoiceId && $invoiceUuid) {
                // Validate UUID format to avoid false-positive rendering on arbitrary ref
                if (!Str::isUuid($invoiceUuid)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('Payment.puqMonobank.Invalid reference identifier'),
                    ], 400);
                }
                // If explicit status or errCode is present, double-check via API to avoid stale failure state
                if ($status || $errCode) {
                    // If status is 'success' - return success immediately
                    if ($status === 'success') {
                        return response()->json([
                            'status' => 'success',
                            'data' => [
                                'url' => route('client.web.panel.client.invoice.details', ['uuid' => $invoiceUuid]),
                                'message' => __('Payment.puqMonobank.Payment completed successfully')
                            ]
                        ]);
                    }

                    // For any non-success status or errCode - verify real payment state
                    $paymentStatus = $this->checkInvoicePaymentStatus($invoiceUuid);
                    if (!empty($paymentStatus['found'])) {
                        if ($paymentStatus['status'] === 'success') {
                            return response()->json([
                                'status' => 'success',
                                'data' => [
                                    'url' => route('client.web.panel.client.invoice.details', ['uuid' => $invoiceUuid]),
                                    'message' => __('Payment.puqMonobank.Payment completed successfully')
                                ]
                            ]);
                        }

                        // Payment not successful - return a user friendly message
                        $resolvedErrCode = $paymentStatus['errCode'] ?? $errCode;
                        $errorMessage = $resolvedErrCode ? $this->getMonobankErrorText((int) $resolvedErrCode) : ($paymentStatus['message'] ?? __('Payment.puqMonobank.Payment status unknown or failed'));
                        return response()->json([
                            'status' => 'error',
                            'message' => $errorMessage,
                            'errCode' => $resolvedErrCode,
                            'details' => $paymentStatus
                        ], 422);
                    }

                    // Nothing found - consider payment pending
                    return response()->json([
                        'status' => 'pending',
                        'message' => __('Payment.puqMonobank.Payment status is being verified'),
                        'data' => [
                            'url' => route('client.web.panel.client.invoice.details', ['uuid' => $invoiceUuid]),
                            'invoice_uuid' => $invoiceUuid
                        ]
                    ]);
                }

                // No Monobank params - try via webhooks/API
                $paymentStatus = $this->checkInvoicePaymentStatus($invoiceUuid);
                if (!empty($paymentStatus['found'])) {
                    if ($paymentStatus['status'] === 'success') {
                        return response()->json([
                            'status' => 'success',
                            'data' => [
                                'url' => route('client.web.panel.client.invoice.details', ['uuid' => $invoiceUuid]),
                                'message' => __('Payment.puqMonobank.Payment completed successfully')
                            ]
                        ]);
                    }

                    $resolvedErrCode = $paymentStatus['errCode'] ?? null;
                    $errorMessage = $resolvedErrCode ? $this->getMonobankErrorText((int) $resolvedErrCode) : ($paymentStatus['message'] ?? __('Payment.puqMonobank.Payment status unknown or failed'));
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'errCode' => $resolvedErrCode,
                        'details' => $paymentStatus
                    ], 422);
                }

                // Status not found - possibly processing
                return response()->json([
                    'status' => 'pending',
                    'message' => __('Payment.puqMonobank.Payment status is being verified'),
                    'data' => [
                        'url' => route('client.web.panel.client.invoice.details', ['uuid' => $invoiceUuid]),
                        'invoice_uuid' => $invoiceUuid
                    ]
                ]);
            }
            
            if (!$invoiceId) {
                return response()->json([
                    'status' => 'error', 
                    'message' => __('Payment.puqMonobank.Missing invoice ID')
                ], 400);
            }

            // If we have status from URL, use it
            if ($status) {
                if ($status === 'success') {
                    // Check if payment was already processed via webhook
                    $result = $this->checkPaymentStatus($invoiceId);
                    if ($result['status'] === 'success') {
                        return response()->json([
                            'status' => 'success',
                            'data' => [
                                'url' => route('client.web.panel.client.invoice.details', ['uuid' => $result['invoice_uuid']]),
                                'message' => __('Payment.puqMonobank.Payment completed successfully')
                            ]
                        ]);
                    } else {
                        // Payment not found or not processed yet
                        return response()->json([
                            'status' => 'error',
                            'message' => __('Payment.puqMonobank.Payment not found or not processed yet')
                        ], 404);
                    }
                } else {
                    // Payment failed - get error message
                    $errorMessage = $this->getMonobankErrorText($errCode);
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'errCode' => $errCode,
                        'details' => [
                            'invoice_id' => $invoiceId,
                            'status' => $status
                        ]
                    ], 422);
                }
            }

            // If no status in URL, check via API
            $result = $this->checkPaymentStatus($invoiceId);
            
            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'url' => route('client.web.panel.client.invoice.details', ['uuid' => $result['invoice_uuid']]),
                        'message' => __('Payment.puqMonobank.Payment completed successfully')
                    ]
                ]);
            } else {
                // Check if it's an error status
                if (isset($result['errCode'])) {
                    $errorMessage = $this->getMonobankErrorText($result['errCode']);
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'errCode' => $result['errCode'],
                        'details' => $result
                    ], 422);
                }

                return response()->json([
                    'status' => 'error',
                    'message' => __('Payment.puqMonobank.Payment status unknown or failed'),
                    'details' => $result
                ], 422);
            }

        } catch (\Exception $e) {
            $this->logError('return_url', 'Exception checking payment status', $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => __('Payment.puqMonobank.System error occurred')
            ], 500);
        }
    }

    /**
     * Define admin permissions for this module
     *
     * @return array List of permissions
     */
    public function adminPermissions(): array
    {
        return [
            [
                'name' => 'Test Connection',
                'key' => 'test-connection',
                'description' => 'Test API connection to Monobank',
            ],
        ];
    }

    /**
     * Define admin API routes
     *
     * @return array Admin API routes
     */
    public function adminApiRoutes(): array
    {
        return [
            [
                'method' => 'post',
                'uri' => 'test_connection/{uuid}',
                'permission' => 'test-connection',
                'name' => 'test_connection.post',
                'controller' => 'puqMonobank@testConnection',
            ],
            [
                'method' => 'post',
                'uri' => 'fetch_public_key/{uuid}',
                'permission' => 'test-connection',
                'name' => 'fetch_public_key.post',
                'controller' => 'puqMonobank@fetchPublicKey',
            ],
        ];
    }

    /**
     * Define client API routes
     *
     * @return array Client API routes
     */
    public function clientApiRoutes(): array
    {
        return [];
    }

    /**
     * Fetch public key via static endpoint
     *
     * @param array $data Request data
     * @return \Illuminate\Http\JsonResponse
     */
    public function controllerClientStatic_fetch_public_key(array $data = []): \Illuminate\Http\JsonResponse
    {
        try {
            $request = $data['request'] ?? request();
            
            // Get form data
            $inputData = $request->all();
            $sandboxMode = $inputData['sandbox_mode'] ?? false;
            $productionToken = $inputData['production_token'] ?? '';
            $sandboxToken = $inputData['sandbox_token'] ?? '';
            
            // Use existing tokens if fields are empty
            $config = $this->getDecryptedConfig();
            if (empty($productionToken)) {
                $productionToken = $config['production_token'] ?? '';
            }
            if (empty($sandboxToken)) {
                $sandboxToken = $config['sandbox_token'] ?? '';
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
            
            // Fetch public key from API
            $result = $this->fetchPublicKeyFromAPI($requiredToken);
            
            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'public_key' => $result['public_key'],
                    'message' => __('Payment.puqMonobank.Public key fetched successfully'),
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'] ?? __('Payment.puqMonobank.Failed to fetch public key'),
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR',
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Payment.puqMonobank.Fetch failed') . ': ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ], 500);
        }
    }

    /**
     * Test connection via static endpoint
     *
     * @param array $data Request data
     * @return \Illuminate\Http\JsonResponse
     */
    public function controllerClientStatic_test_connection(array $data = []): \Illuminate\Http\JsonResponse
    {
        try {
            $request = $data['request'] ?? request();
            
            // Get form data
            $inputData = $request->all();
            $sandboxMode = $inputData['sandbox_mode'] ?? false;
            $productionToken = $inputData['production_token'] ?? '';
            $sandboxToken = $inputData['sandbox_token'] ?? '';
            
            // Use existing tokens if fields are empty
            $config = $this->getDecryptedConfig();
            if (empty($productionToken)) {
                $productionToken = $config['production_token'] ?? '';
            }
            if (empty($sandboxToken)) {
                $sandboxToken = $config['sandbox_token'] ?? '';
            }
            
            // Validate required token
            $requiredToken = $sandboxMode ? $sandboxToken : $productionToken;
            if (empty($requiredToken)) {
                $tokenType = $sandboxMode ? 'sandbox' : 'production';
                return response()->json([
                    'status' => 'error',
                    'message' => __('Payment.puqMonobank.:token_type token is required for testing', ['token_type' => ucfirst($tokenType)]),
                ], 400);
            }
            
            // Create test configuration
            $testConfig = [
                'sandbox_mode' => $sandboxMode,
                'production_token' => $productionToken,
                'sandbox_token' => $sandboxToken,
                'cms_name' => 'PUQcloud',
                'cms_version' => '1.0.0',
            ];
            
            // Test connection
            $monobank = new \Modules\Payment\puqMonobank\Services\MonobankClient($testConfig);
            $result = $monobank->testConnection();
            
            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => __('Payment.puqMonobank.Connection successful'),
                    'merchant_name' => $result['merchant_name'] ?? '',
                    'merchant_id' => $result['merchant_id'] ?? '',
                    'api_mode' => $result['api_mode'] ?? '',
                    'endpoint_used' => $sandboxMode ? 'Sandbox (Test)' : 'Production',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error'] ?? __('Payment.puqMonobank.Connection failed'),
                    'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR',
                    'api_mode' => $result['api_mode'] ?? '',
                    'endpoint_used' => $sandboxMode ? 'Sandbox (Test)' : 'Production',
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Payment.puqMonobank.Test failed') . ': ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ], 500);
        }
    }

    /**
     * Get decrypted configuration for API client
     *
     * @return array Decrypted configuration
     */
    private function getDecryptedConfig(): array
    {
        $config = $this->module_data;

        // Decrypt tokens
        if (!empty($config['production_token'])) {
            try {
                $config['production_token'] = Crypt::decryptString($config['production_token']);
            } catch (\Exception $e) {
                $this->logError('getDecryptedConfig', 'Failed to decrypt production token', $e->getMessage());
                $config['production_token'] = '';
            }
        }

        if (!empty($config['sandbox_token'])) {
            try {
                $config['sandbox_token'] = Crypt::decryptString($config['sandbox_token']);
            } catch (\Exception $e) {
                $this->logError('getDecryptedConfig', 'Failed to decrypt sandbox token', $e->getMessage());
                $config['sandbox_token'] = '';
            }
        }

        return $config;
    }

    /**
     * Verify webhook signature from Monobank
     *
     * @param string $message Raw webhook message (JSON string)
     * @param string $xSignBase64 X-Sign header value from webhook request
     * @return bool True if signature is valid, false otherwise
     */
    private function verifyWebhookSignature(string $message, string $xSignBase64): bool
    {
        try {
            // Get API token for fetching public key
            $config = $this->getDecryptedConfig();
            $apiToken = $config['sandbox_mode'] ? $config['sandbox_token'] : $config['production_token'];
            
            if (empty($apiToken)) {
                $this->logError('webhook_verification', 'API token not configured for fetching public key');
                return false;
            }

            // Fetch public key from Monobank API (like WHMCS does)
            $pubKeyUrl = 'https://api.monobank.ua/api/merchant/pubkey';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-Token: ' . $apiToken
            ));

            $pubKeyResponse = curl_exec($ch);
            $pubKeyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $pubKeyCurlError = curl_error($ch);
            curl_close($ch);

            if ($pubKeyHttpCode !== 200) {
                $this->logError('webhook_verification', 'Failed to retrieve public key from API', [
                    'http_code' => $pubKeyHttpCode,
                    'curl_error' => $pubKeyCurlError,
                    'response' => $pubKeyResponse
                ]);
                return false;
            }

            $pubKeyData = json_decode($pubKeyResponse, true);
            if (!isset($pubKeyData['key'])) {
                $this->logError('webhook_verification', 'Invalid response format from API', [
                    'response' => $pubKeyResponse
                ]);
                return false;
            }

            $publicKeyBase64 = $pubKeyData['key'];

            // Decode signature and public key
            $signature = base64_decode($xSignBase64);
            if ($signature === false) {
                $this->logError('webhook_verification', 'Failed to decode X-Sign header (invalid base64)');
                return false;
            }

            $publicKey = openssl_get_publickey(base64_decode($publicKeyBase64));
            if ($publicKey === false) {
                $this->logError('webhook_verification', 'Failed to load public key (invalid key format)');
                return false;
            }

            // Verify signature using SHA256 algorithm
            $result = openssl_verify($message, $signature, $publicKey, OPENSSL_ALGO_SHA256);

            // Free the public key resource
            openssl_free_key($publicKey);

            if ($result === 1) {
                $this->logInfo('webhook_verification', 'Webhook signature verified successfully');
                return true;
            } else {
                $this->logError('webhook_verification', 'Webhook signature verification failed', [
                    'result' => $result,
                    'message_length' => strlen($message),
                    'signature_length' => strlen($xSignBase64),
                    'openssl_errors' => openssl_error_string()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            $this->logError('webhook_verification', 'Exception during signature verification', $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch public key from Monobank API
     *
     * @param string $apiToken API token for authentication
     * @return array Result with status and public key
     */
    private function fetchPublicKeyFromAPI(string $apiToken): array
    {
        try {
            $pubKeyUrl = 'https://api.monobank.ua/api/merchant/pubkey';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-Token: ' . $apiToken
            ));

            $pubKeyResponse = curl_exec($ch);
            $pubKeyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $pubKeyCurlError = curl_error($ch);
            curl_close($ch);

            if ($pubKeyHttpCode !== 200) {
                $this->logError('fetch_public_key', 'Failed to retrieve public key from API', [
                    'http_code' => $pubKeyHttpCode,
                    'curl_error' => $pubKeyCurlError,
                    'response' => $pubKeyResponse
                ]);
                
                return [
                    'status' => 'error',
                    'message' => 'Failed to retrieve public key: HTTP ' . $pubKeyHttpCode,
                    'error_code' => 'API_ERROR'
                ];
            }

            $pubKeyData = json_decode($pubKeyResponse, true);
            if (!isset($pubKeyData['key'])) {
                $this->logError('fetch_public_key', 'Invalid response format from API', [
                    'response' => $pubKeyResponse
                ]);
                
                return [
                    'status' => 'error',
                    'message' => 'Invalid response format from API',
                    'error_code' => 'INVALID_RESPONSE'
                ];
            }

            $this->logInfo('fetch_public_key', 'Successfully retrieved public key from API');
            
            return [
                'status' => 'success',
                'public_key' => $pubKeyData['key']
            ];

        } catch (\Exception $e) {
            $this->logError('fetch_public_key', 'Exception while fetching public key', $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION'
            ];
        }
    }

    /**
     * Get ISO 4217 currency code
     *
     * @param string $currency Currency code (e.g., 'UAH')
     * @return int ISO 4217 numeric code
     */
    private function getCurrencyCode(string $currency): int
    {
        $currencyMap = [
            'UAH' => 980,
            'USD' => 840,
            'EUR' => 978,
        ];

        return $currencyMap[$currency] ?? 980; // Default to UAH
    }

    /**
     * Check payment status via Monobank API
     *
     * @param string $invoiceId Monobank invoice ID
     * @return array Payment status result
     */
    private function checkPaymentStatus(string $invoiceId): array
    {
        try {
            $config = $this->getDecryptedConfig();
            $apiToken = $config['sandbox_mode'] ? $config['sandbox_token'] : $config['production_token'];
            
            if (empty($apiToken)) {
                return [
                    'status' => 'error',
                    'message' => 'API token not configured'
                ];
            }

            // Get payment status from Monobank API
            $url = 'https://api.monobank.ua/api/merchant/invoice/status?invoiceId=' . urlencode($invoiceId);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-Token: ' . $apiToken
            ));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode !== 200) {
                $this->logError('check_payment_status', 'Failed to get payment status', [
                    'http_code' => $httpCode,
                    'curl_error' => $curlError,
                    'response' => $response
                ]);
                
                return [
                    'status' => 'error',
                    'message' => 'Failed to get payment status: HTTP ' . $httpCode
                ];
            }

            $paymentData = json_decode($response, true);
            if (!$paymentData) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid response from Monobank API'
                ];
            }

            $status = $paymentData['status'] ?? '';
            $reference = $paymentData['reference'] ?? '';

            if ($status === 'success') {
                // Check if payment was already processed
                $existingPayment = $this->checkExistingPayment($invoiceId);
                if ($existingPayment) {
                    // Clear any cached failure state for this invoice UUID
                    if (!empty($reference)) {
                        $this->clearPaymentFailureStatus($reference);
                    }
                    return [
                        'status' => 'success',
                        'invoice_uuid' => $reference,
                        'message' => 'Payment already processed'
                    ];
                }

                // Process payment
                $paymentData = [
                    'invoice_uuid' => $reference,
                    'transaction_id' => $invoiceId,
                    'amount' => ($paymentData['amount'] ?? 0) / 100,
                    'fee' => 0,
                    'method' => $this->module_name,
                    'description' => 'Monobank payment via ' . $this->module_name,
                ];

                $result = $this->handleInvoicePayment($paymentData);
                
                if ($result['status'] === 'success') {
                    // Clear any cached failure state for this invoice UUID
                    if (!empty($reference)) {
                        $this->clearPaymentFailureStatus($reference);
                    }
                    return [
                        'status' => 'success',
                        'invoice_uuid' => $reference,
                        'message' => 'Payment processed successfully'
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to process payment',
                        'details' => $result
                    ];
                }
            } else {
                // Payment failed or other status
                return [
                    'status' => 'error',
                    'errCode' => $paymentData['errCode'] ?? null,
                    'message' => $paymentData['errText'] ?? 'Payment failed',
                    'details' => $paymentData
                ];
            }

        } catch (\Exception $e) {
            $this->logError('check_payment_status', 'Exception checking payment status', $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if payment was already processed
     *
     * @param string $transactionId Monobank invoice ID
     * @return bool|array False if not found, payment data if found
     */
    private function checkExistingPayment(string $transactionId)
    {
        // This would check your database for existing payment
        // Implementation depends on your payment tracking system
        return false; // For now, assume not processed
    }

    /**
     * Save webhook data to cache for later retrieval
     *
     * @param string $invoiceUuid Invoice UUID
     * @param array $webhookData Webhook data from Monobank
     */
    private function saveWebhookData(string $invoiceUuid, array $webhookData): void
    {
        try {
            // Save to cache for 1 hour
            $cacheKey = "monobank_webhook_{$invoiceUuid}";
            cache()->put($cacheKey, $webhookData, 3600);
        } catch (\Exception $e) {
            // Best-effort: if cache fails, just log (no file fallback)
            $this->logError('save_webhook_data', 'Failed to save to cache', $e->getMessage());
        }
    }

    /**
     * Save payment failure status to cache for later retrieval
     *
     * @param string $invoiceUuid Invoice UUID
     * @param array $failureData Failure data from webhook
     */
    private function savePaymentFailureStatus(string $invoiceUuid, array $failureData): void
    {
        try {
            // Save to cache for 1 hour (enough for return URL processing)
            $cacheKey = "monobank_failure_{$invoiceUuid}";
            cache()->put($cacheKey, $failureData, 3600);
        } catch (\Exception $e) {
            // Best-effort: if cache fails, just log (no file fallback)
            $this->logError('save_failure_status', 'Failed to save to cache', $e->getMessage());
        }
    }

    /**
     * Check invoice payment status from database, webhook data, or Monobank API
     *
     * @param string $invoiceUuid Invoice UUID
     * @return array Payment status information
     */
    private function checkInvoicePaymentStatus(string $invoiceUuid): array
    {
        try {
            // 1. Проверим, был ли уже обработан платеж для этого счета
            $existingPayment = $this->checkExistingPayment($invoiceUuid);
            if ($existingPayment) {
                return [
                    'found' => true,
                    'status' => 'success',
                    'message' => 'Payment already processed',
                    'payment_data' => $existingPayment
                ];
            }

            // 2. Resolve Monobank Invoice ID from cache mapping saved at invoice creation
            $monobankInvoiceId = $this->findMonobankInvoiceId($invoiceUuid);
            if ($monobankInvoiceId) {
                $apiStatus = $this->checkMonobankInvoiceStatus($monobankInvoiceId);
                if (!empty($apiStatus['found'])) {
                    return $apiStatus;
                }
            }

            // 4. Если ничего не найдено
            return [
                'found' => false,
                'status' => 'unknown',
                'message' => 'Payment status not found'
            ];
            
        } catch (\Exception $e) {
            return [
                'found' => false,
                'status' => 'error',
                'message' => 'Error checking payment status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Find Monobank Invoice ID from recent webhook logs
     *
     * @param string $invoiceUuid Invoice UUID
     * @return string|null Monobank Invoice ID or null if not found
     */
    private function findMonobankInvoiceId(string $invoiceUuid): ?string
    {
        try {
            // First try explicit mapping saved at invoice creation
            $mapKey = "monobank_ref_to_invoice_{$invoiceUuid}";
            $mappedInvoiceId = cache()->get($mapKey);
            if (!empty($mappedInvoiceId) && is_string($mappedInvoiceId)) {
                return $mappedInvoiceId;
            }

            // Try cache of recent webhooks (legacy support)
            $cacheKey = "monobank_webhook_{$invoiceUuid}";
            $webhookData = cache()->get($cacheKey);
            if ($webhookData && isset($webhookData['invoiceId'])) {
                return $webhookData['invoiceId'];
            }

            return null;
        } catch (\Exception $e) {
            $this->logError('find_monobank_invoice_id', 'Failed to find Monobank Invoice ID', $e->getMessage());
            return null;
        }
    }

    /**
     * Check Monobank invoice status via API
     *
     * @param string $monobankInvoiceId Monobank Invoice ID
     * @return array Status information
     */
    private function checkMonobankInvoiceStatus(string $monobankInvoiceId): array
    {
        try {
            $config = $this->getDecryptedConfig();
            $apiToken = $config['sandbox_mode'] ? $config['sandbox_token'] : $config['production_token'];
            
            if (empty($apiToken)) {
                return [
                    'found' => false,
                    'status' => 'error',
                    'message' => 'API token not configured'
                ];
            }

            // Use existing checkPaymentStatus and normalize response to include 'found'
            $result = $this->checkPaymentStatus($monobankInvoiceId);

            if (($result['status'] ?? '') === 'success') {
                return [
                    'found' => true,
                    'status' => 'success',
                    'invoice_uuid' => $result['invoice_uuid'] ?? null,
                    'message' => $result['message'] ?? null,
                ];
            }

            if (($result['status'] ?? '') === 'error' && isset($result['errCode'])) {
                return [
                    'found' => true,
                    'status' => 'failure',
                    'message' => $result['message'] ?? 'Payment failed',
                    'errCode' => $result['errCode'],
                    'details' => $result['details'] ?? null,
                ];
            }

            return [
                'found' => false,
                'status' => 'unknown',
                'message' => $result['message'] ?? 'Payment status not found',
            ];
            
        } catch (\Exception $e) {
            return [
                'found' => false,
                'status' => 'error',
                'message' => 'Error checking Monobank API: ' . $e->getMessage()
            ];
        }
    }

    // Removed resolveStatusByReference: status is resolved via cached mapping ref→invoiceId for up to 1 hour

    /**
     * Get payment failure status from cache or file
     *
     * @param string $invoiceUuid Invoice UUID
     * @return array|null Failure data or null if not found
     */
    private function getPaymentFailureStatus(string $invoiceUuid): ?array
    {
        try {
            // Check cache first
            $cacheKey = "monobank_failure_{$invoiceUuid}";
            $cached = cache()->get($cacheKey);
            if ($cached) {
                return $cached;
            }

            return null;
        } catch (\Exception $e) {
            $this->logError('get_failure_status', 'Failed to get failure status', $e->getMessage());
            return null;
        }
    }

    /**
     * Clear cached failure status for an invoice UUID
     *
     * @param string $invoiceUuid Invoice UUID
     */
    private function clearPaymentFailureStatus(string $invoiceUuid): void
    {
        try {
            // Remove cache key
            $cacheKey = "monobank_failure_{$invoiceUuid}";
            cache()->forget($cacheKey);
        } catch (\Exception $e) {
            // Best-effort cleanup; log and continue
            $this->logError('clear_failure_status', 'Failed to clear failure status', $e->getMessage());
        }
    }

    /**
     * Save mapping from our reference (invoice UUID) to Monobank invoiceId for 1 hour.
     * This enables deterministic API checks on returnUrl using only ref.
     */
    private function saveRefToInvoiceMapping(string $invoiceUuid, string $monobankInvoiceId): void
    {
        try {
            $mapKey = "monobank_ref_to_invoice_{$invoiceUuid}";
            cache()->put($mapKey, $monobankInvoiceId, 3600);
        } catch (\Exception $e) {
            $this->logError('save_ref_mapping', [
                'invoice_uuid' => $invoiceUuid,
                'monobank_invoice_id' => $monobankInvoiceId,
            ], $e->getMessage());
        }
    }

    /**
     * Get Monobank error text by error code
     *
     * @param int|null $errCode Error code from Monobank
     * @return string Error message
     */
    private function getMonobankErrorText(?int $errCode): string
    {
        $errors = [
            6 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            40 => __('Payment.puqMonobank.Card lost. Expenses limited'),
            41 => __('Payment.puqMonobank.Card lost. Expenses limited'),
            50 => __('Payment.puqMonobank.Card expenses limited'),
            51 => __('Payment.puqMonobank.Card expiration date expired'),
            52 => __('Payment.puqMonobank.Incorrect card number'),
            54 => __('Payment.puqMonobank.Technical failure occurred'),
            55 => __('Payment.puqMonobank.Merchant point configuration error'),
            56 => __('Payment.puqMonobank.Card type does not support such payments'),
            57 => __('Payment.puqMonobank.Transaction not supported'),
            58 => __('Payment.puqMonobank.Card expenses limited for purchases'),
            59 => __('Payment.puqMonobank.Insufficient funds on card'),
            60 => __('Payment.puqMonobank.Card expense operation limit exceeded'),
            61 => __('Payment.puqMonobank.Card internet limit exceeded'),
            62 => __('Payment.puqMonobank.PIN code limit exceeded'),
            63 => __('Payment.puqMonobank.Card internet limit exceeded'),
            67 => __('Payment.puqMonobank.Merchant point configuration error'),
            68 => __('Payment.puqMonobank.Operation rejected by payment system'),
            71 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            72 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            73 => __('Payment.puqMonobank.Routing error'),
            74 => __('Payment.puqMonobank.Merchant point configuration error'),
            75 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            80 => __('Payment.puqMonobank.Incorrect CVV code'),
            81 => __('Payment.puqMonobank.Incorrect CVV2 code'),
            82 => __('Payment.puqMonobank.Transaction not allowed with such conditions'),
            83 => __('Payment.puqMonobank.Card payment attempt limits exceeded'),
            84 => __('Payment.puqMonobank.Incorrect 3D Secure verification value'),
            98 => __('Payment.puqMonobank.Merchant point configuration error'),
            1000 => __('Payment.puqMonobank.Internal system error'),
            1005 => __('Payment.puqMonobank.Internal system error'),
            1010 => __('Payment.puqMonobank.Internal system error'),
            1014 => __('Payment.puqMonobank.Full card details required for payment'),
            1034 => __('Payment.puqMonobank.3-D Secure verification failed'),
            1035 => __('Payment.puqMonobank.3-D Secure verification failed'),
            1036 => __('Payment.puqMonobank.Internal system error'),
            1044 => __('Payment.puqMonobank.Merchant point configuration error'),
            1045 => __('Payment.puqMonobank.3-D Secure verification failed'),
            1053 => __('Payment.puqMonobank.Merchant point configuration error'),
            1054 => __('Payment.puqMonobank.3-D Secure verification failed'),
            1056 => __('Payment.puqMonobank.Transfer only possible to Ukrainian bank card'),
            1064 => __('Payment.puqMonobank.Payment only possible with Mastercard or Visa'),
            1066 => __('Payment.puqMonobank.Merchant point configuration error'),
            1077 => __('Payment.puqMonobank.Payment amount less than minimum allowed'),
            1080 => __('Payment.puqMonobank.Incorrect card expiration date'),
            1090 => __('Payment.puqMonobank.Customer information not found'),
            1115 => __('Payment.puqMonobank.Merchant point configuration error'),
            1121 => __('Payment.puqMonobank.Merchant point configuration error'),
            1145 => __('Payment.puqMonobank.Minimum transfer amount'),
            1165 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            1187 => __('Payment.puqMonobank.Recipient name required'),
            1193 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            1194 => __('Payment.puqMonobank.This top-up method only works with other bank cards'),
            1200 => __('Payment.puqMonobank.CVV code required'),
            1405 => __('Payment.puqMonobank.Payment system limited transfers'),
            1406 => __('Payment.puqMonobank.Card blocked by risk management'),
            1407 => __('Payment.puqMonobank.Operation blocked by risk management'),
            1408 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            1411 => __('Payment.puqMonobank.This type of operations with hryvnia cards temporarily limited'),
            1413 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            1419 => __('Payment.puqMonobank.Incorrect card expiration date'),
            1420 => __('Payment.puqMonobank.Internal system error'),
            1421 => __('Payment.puqMonobank.3-D Secure verification failed'),
            1422 => __('Payment.puqMonobank.3-D Secure stage error'),
            1425 => __('Payment.puqMonobank.3-D Secure stage error'),
            1428 => __('Payment.puqMonobank.Operation blocked by issuing bank'),
            1429 => __('Payment.puqMonobank.3-D Secure verification failed'),
            1433 => __('Payment.puqMonobank.Check recipient name and surname'),
            1436 => __('Payment.puqMonobank.Russian cards not supported'),
            1439 => __('Payment.puqMonobank.Operation not allowed for eVidnovlennya program'),
            1458 => __('Payment.puqMonobank.Operation rejected at 3DS step'),
            8001 => __('Payment.puqMonobank.Payment link expired'),
            8002 => __('Payment.puqMonobank.Client cancelled payment'),
            8003 => __('Payment.puqMonobank.Technical failure occurred'),
            8004 => __('Payment.puqMonobank.3-D Secure processing problems'),
            8005 => __('Payment.puqMonobank.Payment acceptance limits exceeded'),
            8006 => __('Payment.puqMonobank.Payment acceptance limits exceeded'),
        ];

        if ($errCode && isset($errors[$errCode])) {
            return $errors[$errCode];
        }

        return __('Payment.puqMonobank.Unknown error. Please try again or contact support.');
    }
} 