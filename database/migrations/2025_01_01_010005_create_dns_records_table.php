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
        Schema::create('dns_records', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('name')->default('@');
            $table->string('type')->default('A');
            $table->integer('ttl')->default(3600);
            $table->longText('content');
            $table->text('description')->nullable();

            $table->uuid('dns_zone_uuid');
            $table->foreign('dns_zone_uuid')->references('uuid')->on('dns_zones')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
