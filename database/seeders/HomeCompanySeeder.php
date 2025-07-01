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

namespace Database\Seeders;

use App\Models\Group;
use App\Models\HomeCompany;
use App\Models\Module;
use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class HomeCompanySeeder extends Seeder
{
    public function run()
    {
        $path = base_path('database/InvoiceTemplates/');
        $proforma_template = file_get_contents($path.'/proforma/default.blade.php');
        $invoice_template = file_get_contents($path.'/invoice/default.blade.php');
        $credit_note_template = file_get_contents($path.'/credit_note/default.blade.php');

        $homeCompany = HomeCompany::firstOrCreate(
            ['name' => 'Default'],
            [
                'company_name' => 'Your Company Name',
                'address_1' => '123 Business Street',
                'address_2' => 'Suite 101',

                // Default tax IDs (empty)
                'tax_local_id' => '',
                'tax_local_id_name' => '',
                'tax_eu_vat_id' => '',
                'tax_eu_vat_id_name' => 'VAT ID',
                'registration_number' => '',
                'registration_number_name' => 'Registration Number',

                // US-specific (empty by default)
                'us_ein' => '',
                'us_state_tax_id' => '',
                'us_entity_type' => '',

                // Canada-specific (empty by default)
                'ca_business_number' => '',
                'ca_gst_hst_number' => '',
                'ca_pst_qst_number' => '',
                'ca_entity_type' => '',

                // Tax rates - default to 0
                'tax_1' => 0,
                'tax_1_name' => '',
                'tax_2' => 0,
                'tax_2_name' => '',
                'tax_3' => 0,
                'tax_3_name' => '',

                // Invoicing defaults
                'proforma_invoice_number_format' => 'PRO-{NUMBER}',
                'proforma_invoice_number_next' => 1,
                'proforma_invoice_number_reset' => 'yearly',

                'invoice_number_format' => 'INV-{NUMBER}',
                'invoice_number_next' => 1,
                'invoice_number_reset' => 'yearly',

                'credit_note_number_format' => 'CN-{NUMBER}',
                'credit_note_number_next' => 1,
                'credit_note_number_reset' => 'yearly',

                'balance_credit_purchase_item_name' => 'Account Credit',
                'balance_credit_purchase_item_description' => 'Purchase of account credit',

                'refund_item_name' => 'Refund',
                'refund_item_description' => 'Refund of previous payment',

                'pay_to_text' => "Please make payment to:\n".
                    "Your Company Name\n".
                    "Bank: Your Bank\n".
                    "Account: Your Account Number\n".
                    'Reference: {invoice_number}',

                'invoice_footer_text' => 'Thank you for your business!',
                'invoice_template' => $invoice_template,
                'proforma_template' => $proforma_template,
                'credit_note_template' => $credit_note_template,
                'signature' => '<strong>PUQcloud</strong><br>
<em>Billing & Cloud Automation, Open-Source and Free</em><br>
—<br>
This message is intended only for you.',
            ]
        );

        HomeCompany::query()->update(['default' => false]);
        $homeCompany->default = true;

        $homeCompany->invoice_template = $invoice_template;
        $homeCompany->proforma_template = $proforma_template;
        $homeCompany->credit_note_template = $credit_note_template;

        $homeCompany->save();

        $homeCompany->refresh();
        $module = Module::query()->where('name', 'puqBankTransfer')->first();
        if ($module) {

            $payment_gateway = $homeCompany->paymentGateways()->where('key', 'default Bank Transfer')->first();
            if (! $payment_gateway) {
                $payment_gateway = new PaymentGateway;
            }
            $payment_gateway->key = 'default Bank Transfer';
            $payment_gateway->module_uuid = $module->uuid;
            $payment_gateway->home_company_uuid = $homeCompany->uuid;
            $payment_gateway->configuration = [];
            $payment_gateway->save();
        }
        PaymentGateway::reorder($homeCompany->uuid);

        $group = Group::query()->where('name', 'Client Notifications')->first();
        if ($group) {
            $homeCompany->group_uuid = $group->uuid;
        }
        $homeCompany->save();
    }
}
