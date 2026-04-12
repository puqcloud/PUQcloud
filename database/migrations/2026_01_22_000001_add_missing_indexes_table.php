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

return new class extends Migration {
    public function up(): void
    {
        // Remove duplicates before adding unique index (keep the one with the latest start_use)
        DB::statement('
            DELETE a1 FROM admin_ips a1
            INNER JOIN admin_ips a2
            WHERE a1.uuid > a2.uuid
              AND a1.admin_uuid = a2.admin_uuid
              AND a1.ip_address = a2.ip_address
        ');

        $existingIndexes = collect(DB::select('SHOW INDEX FROM admin_ips'))->pluck('Key_name')->unique();

        Schema::table('admin_ips', function (Blueprint $table) use ($existingIndexes) {
            if (! $existingIndexes->contains('admin_ips_admin_uuid_index')) {
                $table->index('admin_uuid');
            }
            if (! $existingIndexes->contains('admin_ips_ip_address_index')) {
                $table->index('ip_address');
            }
            if (! $existingIndexes->contains('admin_ips_start_use_stop_use_index')) {
                $table->index(['start_use', 'stop_use']);
            }
            if (! $existingIndexes->contains('admin_ips_admin_ip_unique')) {
                $table->unique(['admin_uuid', 'ip_address'], 'admin_ips_admin_ip_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_ips', function (Blueprint $table) {
            $table->dropIndex(['admin_uuid']);
            $table->dropIndex(['ip_address']);
            $table->dropIndex(['start_use', 'stop_use']);
            $table->dropUnique('admin_ips_admin_ip_unique');
        });

    }
};
