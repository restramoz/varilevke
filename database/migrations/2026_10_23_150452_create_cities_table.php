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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('province')->index();
            $table->string('classification', 20)->nullable()->comment('Kota|Kabupaten');
            $table->string('bps_code', 10)->nullable()->unique();
            $table->unsignedInteger('kecamatan_count')->nullable()->comment('Count of kecamatan rows, set by the region seeder');
            $table->timestamps();
            $table->unique(['province', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
