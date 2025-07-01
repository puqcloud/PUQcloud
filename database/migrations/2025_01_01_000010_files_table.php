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
        Schema::create('files', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->string('name');
            $table->string('type');
            $table->string('size');
            $table->string('path');
            $table->string('directory')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('expires_at')->nullable();

            $table->string('model_type');
            $table->uuid('model_uuid');
            $table->string('model_field');
            $table->integer('order')->default(0);

            $table->timestamps();

            $table->index(['model_type', 'model_uuid']);
            $table->index('model_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
