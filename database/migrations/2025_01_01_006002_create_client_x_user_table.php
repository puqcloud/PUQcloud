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
        Schema::create('client_x_user', function (Blueprint $table) {

            $table->uuid('client_uuid');
            $table->foreign('client_uuid')->references('uuid')->on('clients')->onDelete('cascade');

            $table->uuid('user_uuid');
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('cascade');

            $table->boolean('owner')->default(false);

            $table->longText('permissions')->nullable();
            $table->longText('notifications')->nullable();

            $table->timestamps();

            $table->primary(['user_uuid', 'client_uuid']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_x_user');
    }
};
