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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicates before adding unique index (keep the earliest transaction)
        DB::statement('
            DELETE t1 FROM transactions t1
            INNER JOIN transactions t2
            WHERE t1.uuid > t2.uuid
              AND t1.payment_gateway_uuid = t2.payment_gateway_uuid
              AND t1.transaction_id = t2.transaction_id
              AND t1.payment_gateway_uuid IS NOT NULL
              AND t1.transaction_id IS NOT NULL
        ');

        $existingIndexes = collect(DB::select('SHOW INDEX FROM transactions'))->pluck('Key_name')->unique();

        Schema::table('transactions', function (Blueprint $table) use ($existingIndexes) {
            if ($existingIndexes->contains('transactions_transaction_id_index')) {
                $table->dropIndex(['transaction_id']);
            }
            if (! $existingIndexes->contains('transactions_pg_txn_unique')) {
                $table->unique(['payment_gateway_uuid', 'transaction_id'], 'transactions_pg_txn_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_pg_txn_unique');
            $table->index('transaction_id');
        });
    }
};
