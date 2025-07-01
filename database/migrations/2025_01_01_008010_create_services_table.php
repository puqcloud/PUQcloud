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
        Schema::create('services', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->uuid('server_uuid');

            $table->uuid('price_uuid');
            $table->foreign('price_uuid')
                ->references('uuid')->on('prices')->onDelete('cascade');

            $table->uuid('client_uuid');
            $table->foreign('client_uuid')
                ->references('uuid')->on('clients')->onDelete('cascade');

            $table->uuid('product_uuid');
            $table->foreign('product_uuid')
                ->references('uuid')->on('products')->onDelete('cascade');

            $table->string('status')->default('pending');
            $table->boolean('idle')->default(false);
            $table->string('provision_status')->default('pending');

            $table->longText('provision_data')->nullable();

            $table->dateTime('order_date')->nullable();

            $table->dateTime('activated_date')->nullable();
            $table->text('create_error')->nullable();

            $table->dateTime('suspended_date')->nullable();
            $table->text('suspended_reason')->nullable();

            $table->dateTime('terminated_date')->nullable();
            $table->text('terminated_reason')->nullable();

            $table->dateTime('cancelled_date')->nullable();
            $table->text('cancelled_reason')->nullable();

            $table->dateTime('billing_timestamp')->nullable();

            $table->string('client_label')->nullable();
            $table->text('client_notes')->nullable();

            $table->string('admin_label')->nullable();
            $table->text('admin_notes')->nullable();

            $table->boolean('termination_request')->default(false);

            $table->timestamps();

            $table->index(['client_uuid', 'product_uuid']);
            $table->index(['status', 'billing_timestamp']);

            $table->index('admin_label');
            $table->index('client_label');
            $table->index('server_uuid');
            $table->index('provision_status');
            $table->index('status');
            $table->index('order_date');
            $table->index('activated_date');
            $table->index('terminated_date');
            $table->index('billing_timestamp');
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
};
