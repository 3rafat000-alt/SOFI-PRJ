<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_ar')->nullable();
            $table->string('alt_en')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::table('property_images', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropIndex(['property_id']);
        });
        Schema::dropIfExists('property_images');
    }
};
