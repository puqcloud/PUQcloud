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

return new class extends Migration {
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('currency_code', 'idx_currency_code');
            $table->fullText('description', 'idx_description');
            $table->index(['amount_net', 'amount_gross'], 'idx_amount_net_gross');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index('currency_uuid', 'idx_currency_uuid');
            $table->index('status', 'idx_clients_status');
            $table->index(['firstname', 'lastname'], 'idx_clients_name');
            $table->fullText(['notes', 'admin_notes'], 'idx_clients_notes');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_currency_code');
            $table->dropFullText('idx_description');
            $table->dropIndex('idx_amount_net_gross');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('idx_currency_uuid');
            $table->dropIndex('idx_clients_status');
            $table->dropIndex('idx_clients_name');
            $table->dropFullText('idx_clients_notes');
        });
    }
};
