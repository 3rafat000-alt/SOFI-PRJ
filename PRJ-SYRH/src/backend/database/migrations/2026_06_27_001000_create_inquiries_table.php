<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();
            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('message');
            $table->enum('type', ['visit', 'callback', 'offer'])->default('callback');
            $table->timestamp('preferred_at')->nullable();
            $table->decimal('offer_amount', 15, 2)->nullable();
            $table->enum('status', ['new', 'contacted', 'closed'])->default('new');
            $table->timestamps();

            $table->index('status');
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropForeign(['agent_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['property_id']);
        });
        Schema::dropIfExists('inquiries');
    }
};
