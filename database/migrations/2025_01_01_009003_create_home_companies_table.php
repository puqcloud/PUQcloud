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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('home_companies', function (Blueprint $table) {
            $table->uuid()->primary();

            // === Basic company details ===
            $table->string('name'); // Display name
            $table->string('company_name')->nullable(); // Legal name
            $table->boolean('default')->default(false);

            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('region_uuid')->nullable();
            $table->string('country_uuid')->nullable();
            $table->string('postcode')->nullable();

            // === Universal tax ID fields ===
            $table->string('tax_local_id')->nullable();        // Local tax ID, e.g., NIP (PL), CIF (ES)
            $table->string('tax_local_id_name')->nullable();   // Label, e.g., "NIP", "CIF"

            $table->string('tax_eu_vat_id')->nullable();       // EU VAT ID, e.g., PL1234567890
            $table->string('tax_eu_vat_id_name')->nullable();  // Label, e.g., "EU VAT Number"

            $table->string('registration_number')->nullable();       // National registry number
            $table->string('registration_number_name')->nullable();  // Label, e.g., "REGON" (PL)

            // === US-specific fields ===
            $table->string('us_ein')->nullable();             // Employer Identification Number (EIN)
            $table->string('us_state_tax_id')->nullable();    // State sales tax number
            $table->string('us_entity_type')->nullable();     // LLC, Corporation, Sole Proprietor, etc.

            // === Canada-specific fields ===
            $table->string('ca_business_number')->nullable();     // Business Number (BN)
            $table->string('ca_gst_hst_number')->nullable();      // GST/HST number (BN + RT0001)
            $table->string('ca_pst_qst_number')->nullable();      // PST (BC), QST (QC), etc.
            $table->string('ca_entity_type')->nullable();         // Corporation, Sole Proprietor, etc.

            // === Up to 3 tax rates with labels and regions ===
            $table->decimal('tax_1', 5, 2)->nullable();             // e.g., 23.00
            $table->string('tax_1_name')->nullable();               // e.g., "VAT", "GST"

            $table->decimal('tax_2', 5, 2)->nullable();
            $table->string('tax_2_name')->nullable();

            $table->decimal('tax_3', 5, 2)->nullable();
            $table->string('tax_3_name')->nullable();

            $table->string('proforma_invoice_number_format')->default('{NUMBER}');
            $table->unsignedInteger('proforma_invoice_number_next')->default(1);
            $table->enum('proforma_invoice_number_reset', ['never', 'monthly', 'yearly'])->default('never');

            $table->string('invoice_number_format')->default('{NUMBER}');
            $table->unsignedInteger('invoice_number_next')->default(1);
            $table->enum('invoice_number_reset', ['never', 'monthly', 'yearly'])->default('never');

            $table->string('credit_note_number_format')->nullable()->default('{NUMBER}');
            $table->unsignedInteger('credit_note_number_next')->default(1);
            $table->enum('credit_note_number_reset', ['never', 'monthly', 'yearly'])->default('never');

            // === Default item names for invoice/credit note positions ===
            $table->string('balance_credit_purchase_item_name')->default('Purchase of Cloud Services');   // e.g., "Purchase of Cloud Services"
            $table->string('balance_credit_purchase_item_description')->nullable();

            $table->string('refund_item_name')->default('Refund of Unused Cloud Services');     // e.g., "Refund of Unused Cloud Services"
            $table->string('refund_item_description')->nullable();

            $table->text('pay_to_text')->nullable();
            $table->text('invoice_footer_text')->nullable();

            $table->string('pdf_paper')->nullable()->default('a4');
            $table->string('pdf_font')->nullable()->default('DejaVu Sans');

            $table->longText('proforma_template')->nullable();
            $table->longText('invoice_template')->nullable();
            $table->longText('credit_note_template')->nullable();

            $table->longText('signature')->nullable();
            $table->uuid('group_uuid')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('home_companies');
    }
};
