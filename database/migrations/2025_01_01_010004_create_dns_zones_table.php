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
        Schema::create('dns_zones', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('soa_admin_email')->nullable();
            $table->integer('soa_ttl')->default(3600);
            $table->integer('soa_refresh')->default(86400);
            $table->integer('soa_retry')->default(7200);
            $table->integer('soa_expire')->default(3600000);
            $table->integer('soa_minimum')->default(172800);
            $table->uuid('dns_server_group_uuid');
            $table->foreign('dns_server_group_uuid')
                ->references('uuid')
                ->on('dns_server_groups')
                ->onDelete('restrict');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_zones');
    }
};
