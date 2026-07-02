<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('ref_code')->unique();
            $table->foreignId('property_type_id')
                ->constrained('property_types');
            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete();
            $table->foreignId('agency_id')
                ->nullable()
                ->constrained('agencies')
                ->nullOnDelete();
            $table->foreignId('governorate_id')
                ->constrained('governorates');
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete();
            $table->enum('purpose', ['sale', 'rent']);
            $table->enum('status', ['available', 'reserved', 'sold', 'rented', 'draft'])->default('available');
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('slug')->unique();
            $table->text('description_ar');
            $table->text('description_en');
            $table->decimal('price', 15, 2);
            $table->enum('currency', ['USD', 'SYP'])->default('USD');
            $table->enum('rent_period', ['month', 'year'])->nullable();
            $table->unsignedInteger('area_sqm')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('parking')->default(0);
            $table->smallInteger('floor')->nullable();
            $table->smallInteger('year_built')->nullable();
            $table->boolean('furnished')->default(false);
            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_hot_deal')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite read-path indexes
            $table->index(['purpose', 'status', 'is_featured']);
            $table->index(['governorate_id', 'area_id']);
            $table->index('property_type_id');
            $table->index('price');
            $table->index('published_at');
        });

        // Full-text index — MySQL/PostgreSQL only (SQLite doesn't support)
        if (!in_array(Schema::getConnection()->getDriverName(), ['sqlite'])) {
            Schema::table('properties', function (Blueprint $table) {
                $table->fullText(['title_ar', 'title_en', 'description_ar', 'description_en']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Drop full-text before any other index/FK to avoid constraint errors
            if (!in_array(Schema::getConnection()->getDriverName(), ['sqlite'])) {
                $table->dropFullText(['title_ar', 'title_en', 'description_ar', 'description_en']);
            }
            $table->dropIndex(['purpose', 'status', 'is_featured']);
            $table->dropIndex(['governorate_id', 'area_id']);
            $table->dropIndex(['property_type_id']);
            $table->dropIndex(['price']);
            $table->dropIndex(['published_at']);
            $table->dropForeign(['property_type_id']);
            $table->dropForeign(['agent_id']);
            $table->dropForeign(['agency_id']);
            $table->dropForeign(['governorate_id']);
            $table->dropForeign(['area_id']);
        });
        Schema::dropIfExists('properties');
    }
};
