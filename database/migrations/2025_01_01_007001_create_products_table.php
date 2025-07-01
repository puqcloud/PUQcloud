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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('key')->unique();

            $table->uuid('module_uuid')->nullable();

            $table->uuid('welcome_email_uuid')->nullable();
            $table->uuid('suspension_email_uuid')->nullable();
            $table->uuid('unsuspension_email_uuid')->nullable();
            $table->uuid('termination_email_uuid')->nullable();

            $table->boolean('hourly_billing')->default(true);
            $table->boolean('allow_idle')->default(false);
            $table->boolean('convert_price')->default(true);

            $table->boolean('hidden')->default(false);
            $table->boolean('retired')->default(false);

            $table->boolean('stock_control')->default(false);
            $table->bigInteger('quantity')->nullable();

            $table->bigInteger('termination_delay_hours')->default(48);
            $table->bigInteger('cancellation_delay_hours')->default(48);

            $table->longText('configuration')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();
            // $table->longText('name')->nullable();
            // $table->longText('short_description')->nullable();
            // $table->longText('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
