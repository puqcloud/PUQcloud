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
        Schema::create('service_x_product_option', function (Blueprint $table) {

            $table->uuid('product_option_uuid');
            $table->foreign('product_option_uuid')
                ->references('uuid')->on('product_options')->onDelete('cascade');

            $table->uuid('service_uuid');
            $table->foreign('service_uuid')
                ->references('uuid')->on('services')->onDelete('cascade');

            $table->primary(['product_option_uuid', 'service_uuid']);
            $table->index('product_option_uuid');
            $table->index('service_uuid');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_x_product_option');
    }
};
