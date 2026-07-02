<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('agency_id')
                ->nullable()
                ->constrained('agencies')
                ->nullOnDelete();
            $table->string('display_name');
            $table->string('phone');
            $table->string('whatsapp')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('license_no')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->text('bio_ar')->nullable();
            $table->text('bio_en')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('agency_id');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['agency_id']);
            $table->dropIndex(['agency_id']);
        });
        Schema::dropIfExists('agents');
    }
};
