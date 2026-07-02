<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_amenity', function (Blueprint $table) {
            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();
            $table->foreignId('amenity_id')
                ->constrained('amenities')
                ->cascadeOnDelete();

            $table->primary(['property_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::table('property_amenity', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropForeign(['amenity_id']);
        });
        Schema::dropIfExists('property_amenity');
    }
};
