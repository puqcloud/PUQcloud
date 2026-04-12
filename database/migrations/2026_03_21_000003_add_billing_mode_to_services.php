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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('billing_mode', 10)->default('prepaid')->after('status');
        });

        // Fill billing_mode from product for existing services
        DB::statement('
            UPDATE services
            INNER JOIN products ON services.product_uuid = products.uuid
            SET services.billing_mode = CASE
                WHEN products.billing_mode = \'both\' THEN \'invoice\'
                ELSE products.billing_mode
            END
        ');

        $table = 'services';
        $existingIndexes = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->unique();
        if (! $existingIndexes->contains('services_billing_mode_index')) {
            Schema::table($table, function (Blueprint $t) {
                $t->index('billing_mode');
            });
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['billing_mode']);
            $table->dropColumn('billing_mode');
        });
    }
};
