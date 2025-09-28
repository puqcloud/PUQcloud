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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->uuid('admin_uuid')->nullable();
            $table->foreign('admin_uuid')->references('uuid')->on('admins')->onDelete('cascade');

            $table->uuid('client_uuid')->nullable();
            $table->foreign('client_uuid')->references('uuid')->on('clients')->onDelete('cascade');

            $table->uuid('user_uuid')->nullable();
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('cascade');

            $table->string('level')->nullable();
            $table->string('action')->nullable();
            $table->longText('description')->nullable();
            $table->string('model_type')->nullable();
            $table->uuid('model_uuid')->nullable();
            $table->longText('model_old_data')->nullable();
            $table->longText('model_new_data')->nullable();
            $table->string('ip_address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
