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
        Schema::create('prices', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->uuid('currency_uuid');

            $table->enum('period', [
                'one-time',
                'hourly',
                'daily',
                'weekly',
                'bi-weekly',
                'monthly',
                'quarterly',
                'semi-annually',
                'annually',
                'biennially',
                'triennially',
            ]);

            $table->decimal('setup', 15, 2)->nullable();
            $table->decimal('base', 15, 2)->nullable();
            $table->decimal('idle', 15, 2)->nullable();
            $table->decimal('switch_down', 15, 2)->nullable();
            $table->decimal('switch_up', 15, 2)->nullable();
            $table->decimal('uninstall', 15, 2)->nullable();

            $table->index('currency_uuid');
            $table->index(['period', 'currency_uuid']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
