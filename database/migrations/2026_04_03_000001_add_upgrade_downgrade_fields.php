<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds upgrade/downgrade support fields across products, product_groups and invoice_items.
 *
 * Every column add/drop is guarded by Schema::hasColumn() so the migration is idempotent —
 * it is safe to rerun on environments where part of the schema was already applied (e.g.,
 * when this file was edited between deployments).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'allow_upgrade')) {
                    $table->boolean('allow_upgrade')->default(false)->after('billing_mode');
                }
                if (! Schema::hasColumn('products', 'allow_downgrade')) {
                    $table->boolean('allow_downgrade')->default(false)->after('allow_upgrade');
                }
                if (! Schema::hasColumn('products', 'allow_proration_alignment')) {
                    $table->boolean('allow_proration_alignment')->default(false)->after('allow_downgrade');
                }
            });
        }

        if (Schema::hasTable('product_groups')) {
            Schema::table('product_groups', function (Blueprint $table) {
                if (! Schema::hasColumn('product_groups', 'upgrade_template')) {
                    $table->string('upgrade_template')->nullable()->default('default')->after('manage_template');
                }
                if (! Schema::hasColumn('product_groups', 'proration_template')) {
                    $table->string('proration_template')->nullable()->default('default')->after('upgrade_template');
                }
            });
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('invoice_items', 'metadata')) {
                    $table->json('metadata')->nullable()->after('period_stop');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $drop = [];
                foreach (['allow_upgrade', 'allow_downgrade', 'allow_proration_alignment'] as $col) {
                    if (Schema::hasColumn('products', $col)) {
                        $drop[] = $col;
                    }
                }
                if (! empty($drop)) {
                    $table->dropColumn($drop);
                }
            });
        }

        if (Schema::hasTable('product_groups')) {
            Schema::table('product_groups', function (Blueprint $table) {
                $drop = [];
                foreach (['upgrade_template', 'proration_template'] as $col) {
                    if (Schema::hasColumn('product_groups', $col)) {
                        $drop[] = $col;
                    }
                }
                if (! empty($drop)) {
                    $table->dropColumn($drop);
                }
            });
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('invoice_items', 'metadata')) {
                    $table->dropColumn('metadata');
                }
            });
        }
    }
};
