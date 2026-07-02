<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')
                ->constrained('governorates')
                ->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('properties_count')->default(0);
            $table->timestamps();

            $table->index('governorate_id');
            $table->unique(['governorate_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['governorate_id']);
            $table->dropUnique(['governorate_id', 'slug']);
            $table->dropIndex(['governorate_id']);
        });
        Schema::dropIfExists('areas');
    }
};
