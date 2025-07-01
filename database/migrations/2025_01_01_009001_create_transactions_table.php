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
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->uuid('client_uuid');
            $table->foreign('client_uuid')
                ->references('uuid')->on('clients')->onDelete('cascade');

            $table->uuid('admin_uuid')->nullable();
            $table->string('type');

            $table->decimal('amount_gross', 15, 4);
            $table->decimal('amount_net', 15, 4);
            $table->decimal('balance_before', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->string('currency_code');

            $table->decimal('fees', 15, 4)->default(0.0000);

            $table->text('description')->nullable();

            $table->string('relation_model')->nullable();
            $table->uuid('relation_model_uuid')->nullable();

            $table->uuid('payment_gateway_uuid')->nullable();
            $table->string('transaction_id')->nullable();
            $table->dateTime('transaction_date');

            $table->dateTime('period_start')->nullable();
            $table->dateTime('period_stop')->nullable();

            $table->timestamps();

            $table->index('client_uuid');
            $table->index('transaction_date');
            $table->index('type');
            $table->index(['relation_model', 'relation_model_uuid']);
            $table->index('admin_uuid');
            $table->index(['period_start', 'period_stop']);
            $table->index('transaction_id');
            $table->index('payment_gateway_uuid');

        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
