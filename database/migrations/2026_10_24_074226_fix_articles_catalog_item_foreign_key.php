<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The articles table currently has a foreign key referencing
     * catalog_items_legacy (which doesn't exist) instead of catalog_items.
     * SQLite doesn't support dropping FKs in place, so we rebuild the table.
     */
    public function up(): void
    {
        // Enable foreign keys for this connection
        DB::statement('PRAGMA foreign_keys = ON');

        // Check if the bad FK exists
        $fkList = DB::select('PRAGMA foreign_key_list(articles)');
        $hasBadFk = false;
        foreach ($fkList as $fk) {
            if (($fk->table ?? $fk['table'] ?? '') === 'catalog_items_legacy') {
                $hasBadFk = true;
                break;
            }
        }

        // If the FK is already correct, nothing to do
        if (! $hasBadFk) {
            return;
        }

        // Rebuild articles table with correct FK to catalog_items
        Schema::create('articles_new', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->comment('Sanitized on write by HtmlSanitizerService');
            $table->unsignedInteger('reading_time')->nullable()->comment('Minutes, ceil(words / 200)');
            $table->unsignedBigInteger('word_count')->default(0);
            $table->string('status', 20)->default('draft')->index()->comment('draft|published');
            $table->string('featured_image')->nullable();
            $table->json('faqs')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->foreignId('catalog_item_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->text('image_brief')->nullable();

            $table->index(['status', 'published_at']);
        });

        // Copy data
        DB::statement('
            INSERT INTO articles_new (
                id, title, slug, excerpt, content, reading_time, word_count, status,
                featured_image, faqs, seo_title, meta_description, catalog_item_id,
                city_id, kecamatan_id, published_at, created_at, updated_at, image_brief
            )
            SELECT
                id, title, slug, excerpt, content, reading_time, word_count, status,
                featured_image, faqs, seo_title, meta_description, catalog_item_id,
                city_id, kecamatan_id, published_at, created_at, updated_at, image_brief
            FROM articles
        ');

        // Drop old table and rename new
        Schema::drop('articles');
        Schema::rename('articles_new', 'articles');
    }

    /**
     * Reverse the migrations.
     *
     * Rollback is intentionally not supported because the original state was
     * fundamentally broken: the FK pointed to catalog_items_legacy which never
     * existed. Recreating that broken state would just re-introduce the bug.
     */
    public function down(): void
    {
        // No-op: cannot rollback to a broken schema that references a non-existent table.
        // The original migration (2026_10_23_150448_create_articles_table) created
        // an FK to catalog_items_legacy due to a SQLite quirk where constrained()
        // resolved to a stale table name from a prior failed migration attempt.
    }
};
