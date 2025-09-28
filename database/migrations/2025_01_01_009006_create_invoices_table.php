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
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->uuid('client_uuid');
            $table->uuid('home_company_uuid');

            $table->enum('type', ['invoice', 'proforma', 'credit_note']);
            $table->string('number')->nullable();

            $table->uuid('invoice_uuid')->nullable(); // invoice->invoice_proforma, credit_note->invoice

            $table->enum('status', ['draft', 'unpaid', 'paid', 'canceled', 'refunded', 'deleted', 'invoiced'])->default('draft');
            $table->string('currency_code', 3);

            $table->decimal('tax_1', 5, 3)->default(0)->nullable();
            $table->decimal('tax_2', 5, 3)->default(0)->nullable();
            $table->decimal('tax_3', 5, 3)->default(0)->nullable();

            $table->string('tax_1_name')->nullable();
            $table->string('tax_2_name')->nullable();
            $table->string('tax_3_name')->nullable();

            $table->decimal('tax_1_amount', 20, 2)->default(0)->nullable();
            $table->decimal('tax_2_amount', 20, 2)->default(0)->nullable();
            $table->decimal('tax_3_amount', 20, 2)->default(0)->nullable();

            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('tax', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);

            $table->dateTime('issue_date');
            $table->dateTime('due_date');
            $table->dateTime('paid_date')->nullable();
            $table->dateTime('refunded_date')->nullable();
            $table->dateTime('canceled_date')->nullable();

            $table->text('admin_notes')->nullable();

            // Client Snapshot
            $table->string('client_firstname')->nullable();
            $table->string('client_lastname')->nullable();
            $table->string('client_company_name')->nullable();
            $table->string('client_country')->nullable();
            $table->string('client_postcode')->nullable();
            $table->string('client_address_1')->nullable();
            $table->string('client_address_2')->nullable();
            $table->string('client_city')->nullable();
            $table->string('client_region')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_tax_id')->nullable();

            // Home Company Snapshot
            $table->string('home_company_company_name');
            $table->string('home_company_address_1')->nullable();
            $table->string('home_company_address_2')->nullable();
            $table->string('home_company_city')->nullable();
            $table->string('home_company_postcode')->nullable();
            $table->string('home_company_country')->nullable();
            $table->string('home_company_region')->nullable();

            $table->string('home_company_tax_local_id')->nullable();
            $table->string('home_company_tax_local_id_name')->nullable();
            $table->string('home_company_tax_eu_vat_id')->nullable();
            $table->string('home_company_tax_eu_vat_id_name')->nullable();
            $table->string('home_company_registration_number')->nullable();
            $table->string('home_company_registration_number_name')->nullable();
            $table->string('home_company_us_ein')->nullable();
            $table->string('home_company_us_state_tax_id')->nullable();
            $table->string('home_company_us_entity_type')->nullable();
            $table->string('home_company_ca_gst_hst_number')->nullable();
            $table->string('home_company_ca_pst_qst_number')->nullable();
            $table->string('home_company_ca_entity_type')->nullable();
            $table->string('home_company_pay_to_text')->nullable();
            $table->string('home_company_invoice_footer_text')->nullable();

            $table->timestamps();

            $table->index('client_uuid');
            $table->index('home_company_uuid');

            $table->index('status');
            $table->index('type');

            $table->index('issue_date');
            $table->index('due_date');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
