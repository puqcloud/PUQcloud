<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['client_uuid', 'transaction_date'], 'idx_transactions_client_date');
            $table->index(['client_uuid', 'type'], 'idx_transactions_client_type');
            $table->index(['payment_gateway_uuid', 'transaction_date'], 'idx_transactions_gateway_date');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_client_date');
            $table->dropIndex('idx_transactions_client_type');
            $table->dropIndex('idx_transactions_gateway_date');
        });
    }
};
