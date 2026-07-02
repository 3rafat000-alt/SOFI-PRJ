<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users');
            $table->foreignId('referred_id')->constrained('users');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->decimal('referrer_reward', 18, 8);
            $table->decimal('referred_reward', 18, 8);
            $table->string('currency', 10)->default('USD');
            $table->enum('trigger', ['registration', 'first_deposit', 'kyc_verified', 'first_transaction']);
            $table->enum('status', ['pending', 'credited', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};
