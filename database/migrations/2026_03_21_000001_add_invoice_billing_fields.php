<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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
        // Products: billing_mode
        Schema::table('products', function (Blueprint $table) {
            $table->string('billing_mode', 10)->default('prepaid')->after('convert_price');
        });

        // Invoices: billing_purpose + reminder tracking
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('billing_purpose', 20)->default('add_funds')->after('status');
            $table->dateTime('reminder_sent_at')->nullable()->after('admin_notes');
            $table->dateTime('overdue_1_sent_at')->nullable()->after('reminder_sent_at');
            $table->dateTime('overdue_2_sent_at')->nullable()->after('overdue_1_sent_at');
            $table->dateTime('overdue_3_sent_at')->nullable()->after('overdue_2_sent_at');
            $table->index('billing_purpose');
        });

        // Invoice Items: service link + billing period
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->uuid('service_uuid')->nullable()->after('relation_model_uuid');
            $table->dateTime('period_start')->nullable()->after('service_uuid');
            $table->dateTime('period_stop')->nullable()->after('period_start');
            $table->index('service_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['service_uuid']);
            $table->dropColumn(['service_uuid', 'period_start', 'period_stop']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['billing_purpose']);
            $table->dropColumn(['billing_purpose', 'reminder_sent_at', 'overdue_1_sent_at', 'overdue_2_sent_at', 'overdue_3_sent_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['billing_mode']);
        });
    }
};
