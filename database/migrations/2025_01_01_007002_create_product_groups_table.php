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
        Schema::create('product_groups', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('key')->unique();
            $table->boolean('hidden')->default(false);
            $table->integer('order')->default(0);
            $table->longText('notes')->nullable();
            $table->string('icon')->nullable();
            $table->string('list_template')->nullable()->default('default');
            $table->string('order_template')->nullable()->default('default');
            $table->string('manage_template')->nullable()->default('default');
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
        Schema::dropIfExists('product_groups');
    }
};
