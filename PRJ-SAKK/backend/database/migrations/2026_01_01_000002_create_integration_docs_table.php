<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('title');
            $table->string('title_ar');
            $table->string('slug')->unique();
            $table->text('content')->nullable(); // markdown content
            $table->text('content_ar')->nullable();
            $table->string('section')->default('general'); // general, setup, api, troubleshooting, examples
            $table->integer('order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_docs');
    }
};
