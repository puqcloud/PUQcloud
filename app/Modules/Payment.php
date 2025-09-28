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

namespace App\Modules;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class Payment extends Module
{
    public string $payment_gateway_uuid = '';

    public function __construct()
    {
        $this->module_type = 'Payment';
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        $this->module_data = $data;

        return $this->module_data;
    }

    public function setPaymentGatewayUuid(string $payment_gateway_uuid): void
    {
        $this->payment_gateway_uuid = $payment_gateway_uuid;
    }

    public function getSettingsPage(array $data = []): string
    {
        return __('message.No data to display');
    }

    public function saveModuleData(array $data = []): array
    {
        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    public function getClientAreaHtml(array $data = []): string
    {
        return __('message.No data to display');
    }

    public function view(string $template, array $data = []): string
    {
        $templatePath = 'modules.Payment.'.$this->module_name.'.views.'.$template;

        if (view()->exists($templatePath)) {
            try {
                return view($templatePath, $data)->render();
            } catch (\Throwable $e) {
                return 'Error rendering template: '.$e->getMessage();
            }
        }

        return "Template '{$template}' not found.";
    }

    protected function handleInvoicePayment(array $paymentData): array
    {
        $invoice_uuid = $paymentData['invoice_uuid'] ?? null;
        $transactionId = $paymentData['transaction_id'] ?? null;
        $amount = $paymentData['amount'] ?? 0;
        $fee = $paymentData['fee'] ?? 0;
        $email = $paymentData['email'] ?? null;
        $method = $paymentData['method'] ?? $this->module_name;
        $description = $paymentData['description'] ?? '';
        $description .= $email.', '.$method;

        try {
            return DB::transaction(function () use ($invoice_uuid, $amount, $fee, $transactionId, $description) {
                $invoice = Invoice::where('uuid', $invoice_uuid)->lockForUpdate()->first();

                if (! $invoice) {
                    return [
                        'status' => 'error',
                        'errors' => [__('error.Invoice not found')],
                    ];
                }

                if ($invoice->status === 'invoiced') {
                    return [
                        'status' => 'success',
                        'message' => __('message.Payment Already Processed'),
                        'data' => [
                            'url' => route('client.web.panel.client.invoice.details', ['uuid' => $invoice->reference_invoice_uuid]),
                        ],
                    ];
                }

                $add_payment = $invoice->addPaymentByPaymentGateway(
                    $amount,
                    $fee,
                    $transactionId,
                    $description,
                    $this->payment_gateway_uuid
                );

                if ($add_payment['status'] === 'error') {
                    return [
                        'status' => 'error',
                        'errors' => $add_payment['errors'],
                    ];
                }

                return [
                    'status' => 'success',
                    'message' => __('message.Payment Successful'),
                    'data' => [
                        'url' => route('client.web.panel.client.invoice.details', ['uuid' => $invoice->reference_invoice_uuid]),
                    ],
                ];
            });
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'errors' => ['Unexpected error: '.$e->getMessage()],
            ];
        }
    }
}
