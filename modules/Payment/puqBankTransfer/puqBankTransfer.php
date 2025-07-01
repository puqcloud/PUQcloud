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
use App\Modules\Payment;

class puqBankTransfer extends Payment
{
    public string $payment_gateway_uuid = '';

    public array $module_data = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        $this->module_data = [
            'bank_transfer_instructions' => $data['bank_transfer_instructions'] ?? '',
        ];

        return $this->module_data;
    }

    public function getSettingsPage(array $data = []): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;

        return $this->view('configuration', $data);
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
        $invoice = $data['invoice'];
        $currency = $invoice->client->currency;
        $due_amount = $currency->prefix.' '.$invoice->getDueAmountAttribute().$currency->suffix;

        $this->module_data['bank_transfer_instructions'] = str_replace('{AMOUNT}', $due_amount, $this->module_data['bank_transfer_instructions']);
        $this->module_data['bank_transfer_instructions'] = str_replace('{INVOICE_NUMBER}', $invoice->number, $this->module_data['bank_transfer_instructions']);
        $this->module_data['bank_transfer_instructions'] = str_replace('{CURRENCY}', $currency->code, $this->module_data['bank_transfer_instructions']);

        return $this->view('client_area', $this->module_data);
    }
}
