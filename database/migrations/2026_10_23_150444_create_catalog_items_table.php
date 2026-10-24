<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // 'pelatihan' | 'jasa' — set from docs/source/catalog-manifest.json,
            // including Jasa Lingkungan Karbon which stays 'pelatihan' until a
            // human confirms otherwise.
            $table->string('category', 20)->index();
            // Source ordering within its category (pelatihan 1..227, jasa 1..45).
            $table->unsignedInteger('source_order')->comment('Position in docs/source/catalog-source-full.txt within its category');
            $table->boolean('review_required')->default(false);
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->unique(['category', 'source_order']);
            $table->unique(['category', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
