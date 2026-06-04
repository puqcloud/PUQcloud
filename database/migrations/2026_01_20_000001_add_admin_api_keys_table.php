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
        Schema::create('admin_api_keys', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->uuid('admin_uuid');
            $table->foreign('admin_uuid')
                ->references('uuid')
                ->on('admins')
                ->cascadeOnDelete();

            $table->string('name');
            $table->char('hash', 64)->unique(); // SHA-256
            $table->longText('description')->nullable();

            $table->json('ips')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('admin_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_api_keys');
    }
};
