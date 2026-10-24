<?php

use App\Models\CatalogContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editorial content per catalog item, kept in its own table so the catalog
     * identity (names, order, classification) stays pristine in catalog_items.
     * Additive only: nothing here touches existing rows or tables.
     */
    public function up(): void
    {
        Schema::create('catalog_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->unique()->constrained()->cascadeOnDelete();
            // draft | review | published (review = builder/AI output awaiting
            // human editorial confirmation).
            $table->string('status', 20)->default(CatalogContent::STATUS_REVIEW)->index();
            $table->json('fields')->comment('Structured editorial fields, see App\Services\Catalog\Content\FieldBlueprint');
            $table->timestamp('generated_at')->nullable()->comment('Last builder/AI draft generation');
            $table->timestamp('edited_at')->nullable()->comment('Last human editorial save');
            $table->timestamps();

            $table->index(['status', 'catalog_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_contents');
    }
};
