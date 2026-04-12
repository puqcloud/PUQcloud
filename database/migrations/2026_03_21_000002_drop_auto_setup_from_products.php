<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'auto_setup')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('auto_setup');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'auto_setup')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('auto_setup', 20)->default('payment_received')->after('billing_mode');
            });
        }
    }
};
