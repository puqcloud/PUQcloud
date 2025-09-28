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

namespace Modules\Payment\puqMonobank\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonobankClient
{
    private string $token;
    private string $baseUrl;
    private bool $sandboxMode;
    private string $cmsName = 'PUQcloud';
    private string $cmsVersion = '1.0.0';

    public function __construct(array $config)
    {
        $this->sandboxMode = $config['sandbox_mode'] ?? false;
        
        // Use sandbox or production token based on mode
        $this->token = $this->sandboxMode 
            ? ($config['sandbox_token'] ?? '') 
            : ($config['production_token'] ?? '');
            
        // Monobank API base URL
        $this->baseUrl = 'https://api.monobank.ua';
        
        // Set CMS information if provided
        if (!empty($config['cms_name'])) {
            $this->cmsName = $config['cms_name'];
        }
        if (!empty($config['cms_version'])) {
            $this->cmsVersion = $config['cms_version'];
        }
    }

    /**
     * Create new invoice for payment
     *
     * @param array $data Payment data
     * @return array Response with invoiceId and pageUrl or error
     */
    public function createInvoice(array $data): array
    {
        $endpoint = '/api/merchant/invoice/create';
        
        $payload = [
            'amount' => intval($data['amount']), // Amount in kopecks (ensure int)
            'ccy' => intval($data['currency'] ?? 980), // Default to UAH (ensure int)
            'redirectUrl' => $data['redirect_url'],
            'webHookUrl' => $data['webhook_url'],
            'validity' => intval($data['validity'] ?? 3600), // 1 hour default (ensure int)
            'paymentType' => $data['payment_type'] ?? 'debit', // debit or hold
        ];



        // Add merchant payment info if provided
        if (!empty($data['merchant_info'])) {
            $payload['merchantPaymInfo'] = [
                'reference' => $data['merchant_info']['reference'] ?? '',
                'destination' => $data['merchant_info']['destination'] ?? '',
                'comment' => $data['merchant_info']['comment'] ?? '',
            ];
        }

        // Add display type for iframe if specified
        if (!empty($data['display_type'])) {
            $payload['displayType'] = $data['display_type'];
        }

        try {
            $response = $this->makeApiRequest('POST', $endpoint, $payload);
            
            if ($response['status'] === 'success') {
                return [
                    'status' => 'success',
                    'invoice_id' => $response['data']['invoiceId'],
                    'payment_url' => $response['data']['pageUrl'],
                ];
            }
            
            return [
                'status' => 'error',
                'error_code' => $response['error_code'] ?? 'UNKNOWN_ERROR',
                'error_message' => $response['error_message'] ?? 'Unknown error occurred',
            ];
            
        } catch (\Exception $e) {
            $this->logError('createInvoice', $data, $e->getMessage());
            
            return [
                'status' => 'error',
                'error_code' => 'REQUEST_FAILED',
                'error_message' => 'Failed to create invoice: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get invoice status
     *
     * @param string $invoiceId Invoice ID
     * @return array Invoice status and details
     */
    public function getInvoiceStatus(string $invoiceId): array
    {
        $endpoint = '/api/merchant/invoice/status';
        
        try {
            $response = $this->makeApiRequest('GET', $endpoint, ['invoiceId' => $invoiceId]);
            
            if ($response['status'] === 'success') {
                $data = $response['data'];
                
                return [
                    'status' => 'success',
                    'invoice_id' => $data['invoiceId'],
                    'payment_status' => $data['status'],
                    'amount' => $data['amount'],
                    'final_amount' => $data['finalAmount'] ?? $data['amount'],
                    'currency' => $data['ccy'],
                    'reference' => $data['reference'] ?? '',
                    'destination' => $data['destination'] ?? '',
                    'created_date' => $data['createdDate'] ?? null,
                    'modified_date' => $data['modifiedDate'] ?? null,
                    'failure_reason' => $data['failureReason'] ?? null,
                    'error_code' => $data['errCode'] ?? null,
                    'payment_info' => $data['paymentInfo'] ?? null,
                ];
            }
            
            return [
                'status' => 'error',
                'error_code' => $response['error_code'] ?? 'UNKNOWN_ERROR',
                'error_message' => $response['error_message'] ?? 'Failed to get invoice status',
            ];
            
        } catch (\Exception $e) {
            $this->logError('getInvoiceStatus', ['invoiceId' => $invoiceId], $e->getMessage());
            
            return [
                'status' => 'error',
                'error_code' => 'REQUEST_FAILED',
                'error_message' => 'Failed to get invoice status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel payment
     *
     * @param string $invoiceId Invoice ID
     * @param array $data Optional cancellation data (amount for partial refund, etc.)
     * @return array Cancellation result
     */
    public function cancelPayment(string $invoiceId, array $data = []): array
    {
        $endpoint = '/api/merchant/invoice/cancel';
        
        $payload = [
            'invoiceId' => $invoiceId,
        ];
        
        // Add optional parameters
        if (!empty($data['ext_ref'])) {
            $payload['extRef'] = $data['ext_ref'];
        }
        
        if (!empty($data['amount'])) {
            $payload['amount'] = $data['amount'];
        }
        
        if (!empty($data['items'])) {
            $payload['items'] = $data['items'];
        }

        try {
            $response = $this->makeApiRequest('POST', $endpoint, $payload);
            
            if ($response['status'] === 'success') {
                $data = $response['data'];
                
                return [
                    'status' => 'success',
                    'cancellation_status' => $data['status'],
                    'created_date' => $data['createdDate'],
                    'modified_date' => $data['modifiedDate'],
                ];
            }
            
            return [
                'status' => 'error',
                'error_code' => $response['error_code'] ?? 'UNKNOWN_ERROR',
                'error_message' => $response['error_message'] ?? 'Failed to cancel payment',
            ];
            
        } catch (\Exception $e) {
            $this->logError('cancelPayment', ['invoiceId' => $invoiceId, 'data' => $data], $e->getMessage());
            
            return [
                'status' => 'error',
                'error_code' => 'REQUEST_FAILED',
                'error_message' => 'Failed to cancel payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Remove (invalidate) invoice
     *
     * @param string $invoiceId Invoice ID
     * @return array Result of invalidation
     */
    public function removeInvoice(string $invoiceId): array
    {
        $endpoint = '/api/merchant/invoice/remove';
        
        $payload = [
            'invoiceId' => $invoiceId,
        ];

        try {
            $response = $this->makeApiRequest('POST', $endpoint, $payload);
            
            if ($response['status'] === 'success') {
                return [
                    'status' => 'success',
                    'message' => 'Invoice invalidated successfully',
                ];
            }
            
            return [
                'status' => 'error',
                'error_code' => $response['error_code'] ?? 'UNKNOWN_ERROR',
                'error_message' => $response['error_message'] ?? 'Failed to remove invoice',
            ];
            
        } catch (\Exception $e) {
            $this->logError('removeInvoice', ['invoiceId' => $invoiceId], $e->getMessage());
            
            return [
                'status' => 'error',
                'error_code' => 'REQUEST_FAILED',
                'error_message' => 'Failed to remove invoice: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get merchant details
     *
     * @return array Merchant information
     */
    public function getMerchantDetails(): array
    {
        $endpoint = '/api/merchant/details';

        try {
            $response = $this->makeApiRequest('GET', $endpoint);
            
            if ($response['status'] === 'success') {
                $data = $response['data'];
                
                return [
                    'status' => 'success',
                    'merchant_id' => $data['merchantId'],
                    'merchant_name' => $data['merchantName'],
                    'edrpou' => $data['edrpou'],
                ];
            }
            
            return [
                'status' => 'error',
                'error_code' => $response['error_code'] ?? 'UNKNOWN_ERROR',
                'error_message' => $response['error_message'] ?? 'Failed to get merchant details',
            ];
            
        } catch (\Exception $e) {
            $this->logError('getMerchantDetails', [], $e->getMessage());
            
            return [
                'status' => 'error',
                'error_code' => 'REQUEST_FAILED',
                'error_message' => 'Failed to get merchant details: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test API connection
     *
     * @return array Connection test result
     */
    public function testConnection(): array
    {
        $result = $this->getMerchantDetails();
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'message' => 'Connection successful',
                'merchant_name' => $result['merchant_name'],
                'api_mode' => $this->sandboxMode ? 'sandbox' : 'production',
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Connection failed',
            'error' => $result['error_message'] ?? 'Unknown error',
            'api_mode' => $this->sandboxMode ? 'sandbox' : 'production',
        ];
    }

    /**
     * Verify webhook signature (if Monobank provides signature verification)
     *
     * @param array $data Webhook data
     * @param string $signature Webhook signature
     * @return bool Whether signature is valid
     */
    public function verifyWebhook(array $data, string $signature): bool
    {
        // Note: Based on the documentation provided, there's no mention of signature verification
        // This method is prepared for future use if Monobank implements webhook signatures
        
        // For now, we'll implement basic validation
        if (empty($data) || empty($signature)) {
            return false;
        }
        
        // Additional validation can be added here when Monobank provides
        // signature verification mechanism
        
        return true;
    }

    /**
     * Make API request to Monobank
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Processed response
     */
    private function makeApiRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'X-Token' => $this->token,
            'X-Cms' => $this->cmsName,
            'X-Cms-Version' => $this->cmsVersion,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        try {
            $httpClient = Http::withHeaders($headers)->timeout(30);
            
            if ($method === 'GET') {
                $response = $httpClient->get($url, $data);
            } else {
                $response = $httpClient->send($method, $url, ['json' => $data]);
            }

            $statusCode = $response->status();
            $responseData = $response->json();



            // Handle successful response
            if ($statusCode === 200) {
                return [
                    'status' => 'success',
                    'data' => $responseData,
                ];
            }

            // Handle error responses
            $errorCode = $responseData['errCode'] ?? 'UNKNOWN_ERROR';
            $errorMessage = $responseData['errText'] ?? 'Unknown error occurred';

            $this->logError('makeApiRequest', [
                'method' => $method,
                'endpoint' => $endpoint,
                'request_data' => $data,
                'status_code' => $statusCode,
            ], "API Error: {$errorCode} - {$errorMessage}");

            return [
                'status' => 'error',
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'http_status' => $statusCode,
            ];

        } catch (\Exception $e) {
            $this->logError('makeApiRequest', [
                'method' => $method,
                'endpoint' => $endpoint,
                'request_data' => $data,
            ], 'Request exception: ' . $e->getMessage());

            return [
                'status' => 'error',
                'error_code' => 'REQUEST_EXCEPTION',
                'error_message' => 'Request failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Log error with module context
     *
     * @param string $action Action being performed
     * @param array $request Request data
     * @param string $error Error message
     */
    private function logError(string $action, array $request, string $error): void
    {
        logModule(
            'Payment',
            'puqMonobank',
            $action,
            'error',
            $request,
            $error
        );
    }
} 