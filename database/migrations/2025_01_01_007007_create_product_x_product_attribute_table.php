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
        Schema::create('product_x_product_attribute', function (Blueprint $table) {

            $table->uuid('product_uuid');
            $table->foreign('product_uuid')->references('uuid')->on('products')->onDelete('cascade');

            $table->uuid('product_attribute_uuid');
            $table->foreign('product_attribute_uuid')->references('uuid')->on('product_attributes')->onDelete('cascade');

            $table->primary(['product_uuid', 'product_attribute_uuid']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_x_product_attribute');
    }
};
