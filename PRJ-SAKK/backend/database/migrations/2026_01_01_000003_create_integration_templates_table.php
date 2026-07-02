<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('name');
            $table->string('name_ar');
            $table->string('type'); // email, sms, push, in_app
            $table->string('event'); // deposit_success, withdrawal_success, card_issued, etc.
            $table->string('subject')->nullable();
            $table->string('subject_ar')->nullable();
            $table->text('body');
            $table->text('body_ar');
            $table->text('variables')->nullable(); // JSON array of available variables
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_templates');
    }
};
