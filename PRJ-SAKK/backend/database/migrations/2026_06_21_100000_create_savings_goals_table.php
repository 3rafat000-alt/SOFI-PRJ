<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('target_amount', 18, 2)->nullable();
            $table->decimal('saved_amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active'); // active, completed, closed
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });

        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('savings_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // deposit, withdraw
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('completed');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['savings_goal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
        Schema::dropIfExists('savings_goals');
    }
};
