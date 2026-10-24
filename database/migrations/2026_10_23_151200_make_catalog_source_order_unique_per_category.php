<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite cannot drop the old (source_order)-only unique constraint in place,
 * so the constraint shape is fixed via guarded index operations. The guards
 * also absorb state left behind by an earlier migration run that was killed
 * mid-flight — such leftovers are empty artifacts; the catalog importer
 * re-verifies every row against the manifest on its next run.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. A crashed earlier run may have renamed the table away and never
        //    dropped the copy. Remove that empty artifact first.
        if (Schema::hasTable('catalog_items_legacy')) {
            Schema::drop('catalog_items_legacy');
        }

        // 2. The old run-attempt unique index must not survive on any DB that
        //    still carries it.
        if (Schema::hasIndex('catalog_items', 'catalog_items_source_order_unique')) {
            Schema::table('catalog_items', function (Blueprint $table): void {
                $table->dropUnique('catalog_items_source_order_unique');
            });
        }

        // 3. Guarantee the final index set (some may be missing after a crash).
        if (! Schema::hasIndex('catalog_items', 'catalog_items_category_source_order_unique')) {
            Schema::table('catalog_items', function (Blueprint $table): void {
                $table->unique(['category', 'source_order'], 'catalog_items_category_source_order_unique');
            });
        }

        if (! Schema::hasIndex('catalog_items', 'catalog_items_slug_unique')) {
            Schema::table('catalog_items', function (Blueprint $table): void {
                $table->unique('slug', 'catalog_items_slug_unique');
            });
        }

        if (! Schema::hasIndex('catalog_items', 'catalog_items_category_name_unique')) {
            Schema::table('catalog_items', function (Blueprint $table): void {
                $table->unique(['category', 'name'], 'catalog_items_category_name_unique');
            });
        }

        if (! Schema::hasIndex('catalog_items', 'catalog_items_category_index')) {
            Schema::table('catalog_items', function (Blueprint $table): void {
                $table->index('category', 'catalog_items_category_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table): void {
            $table->dropUnique('catalog_items_category_source_order_unique');
            $table->dropUnique('catalog_items_category_name_unique');
            $table->dropIndex('catalog_items_category_index');
        });
    }
};
