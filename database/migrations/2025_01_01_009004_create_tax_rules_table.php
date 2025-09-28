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
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->integer('order')->nullable(1);

            $table->uuid('country_uuid')->nullable();
            $table->foreign('country_uuid')
                ->references('uuid')->on('countries')->onDelete('cascade');

            $table->uuid('region_uuid')->nullable();
            $table->foreign('region_uuid')
                ->references('uuid')->on('regions')->onDelete('cascade');

            $table->boolean('private_client');
            $table->boolean('company_without_tax_id');
            $table->boolean('company_with_tax_id');
            $table->boolean('individual_tax_rate');

            $table->decimal('tax_1', 5, 3)->nullable();
            $table->string('tax_1_name')->nullable();

            $table->decimal('tax_2', 5, 3)->nullable();
            $table->string('tax_2_name')->nullable();

            $table->decimal('tax_3', 5, 3)->nullable();
            $table->string('tax_3_name')->nullable();

            $table->uuid('home_company_uuid');
            $table->foreign('home_company_uuid')
                ->references('uuid')->on('home_companies')->onDelete('cascade');

            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('tax_rules');
    }
};
