<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BPS wilayah data can contain a kabupaten and a kota with the same bare
 * name (e.g. "Kediri"), so uniqueness must include the classification.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            $table->dropUnique('cities_province_name_unique');
            $table->unique(['province', 'classification', 'name'], 'cities_province_classification_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            $table->dropUnique('cities_province_classification_name_unique');
            $table->unique(['province', 'name'], 'cities_province_name_unique');
        });
    }
};
