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
        Schema::create('client_balances', function (Blueprint $table) {
            $table->uuid('client_uuid')->primary();
            $table->decimal('balance', 15, 4)->default(0.0000);
            $table->timestamps();

            $table->foreign('client_uuid')
                ->references('uuid')->on('clients')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_balances');
    }
};
