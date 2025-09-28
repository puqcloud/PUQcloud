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
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->uuid('group_uuid');
            $table->foreign('group_uuid')->references('uuid')->on('groups')->onDelete('cascade');

            $table->string('category');
            $table->string('notification');

            $table->uuid('notification_layout_uuid');
            $table->foreign('notification_layout_uuid')->references('uuid')->on('notification_layouts')->onDelete('cascade');

            $table->uuid('notification_template_uuid');
            $table->foreign('notification_template_uuid')->references('uuid')->on('notification_templates')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
