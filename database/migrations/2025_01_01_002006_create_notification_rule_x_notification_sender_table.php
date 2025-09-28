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
        Schema::create('notification_rule_x_notification_sender', function (Blueprint $table) {
            $table->uuid('notification_rule_uuid');
            $table->foreign('notification_rule_uuid', 'n_rule_x_n_sender_n_rule_uuid_foreign')
                ->references('uuid')->on('notification_rules')
                ->onDelete('cascade');

            $table->uuid('notification_sender_uuid');
            $table->foreign('notification_sender_uuid', 'n_rule_x_n_sender_n_sender_uuid_foreign')
                ->references('uuid')->on('notification_senders')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_rule_x_notification_sender');
    }
};
