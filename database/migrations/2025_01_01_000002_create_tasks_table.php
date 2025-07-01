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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('job_name')->nullable();
            $table->uuid('job_id')->nullable();
            $table->string('queue')->nullable();
            $table->longText('input_data')->nullable();
            $table->longText('output_data')->nullable();
            $table->text('tags')->nullable();
            $table->string('status')->nullable();
            $table->integer('attempts')->nullable();
            $table->integer('maxTries')->nullable();
            $table->timestamp('added_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('filed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
