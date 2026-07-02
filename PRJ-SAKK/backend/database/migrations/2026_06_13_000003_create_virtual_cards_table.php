<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            
            // Card Details
            $table->string('card_number', 19); // Encrypted
            $table->string('card_number_masked', 19); // **** **** **** 1234
            $table->string('cvv', 4); // Encrypted
            $table->string('expiry_month', 2);
            $table->string('expiry_year', 4);
            $table->string('cardholder_name');
            
            // Card Type & Brand
            $table->enum('card_type', ['virtual', 'physical'])->default('virtual');
            $table->enum('brand', ['visa', 'mastercard'])->default('visa');
            $table->string('bin', 6)->nullable(); // Bank Identification Number
            
            // Balance & Limits
            $table->decimal('balance', 18, 2)->default(0);
            $table->decimal('spending_limit', 18, 2)->default(5000);
            $table->decimal('daily_limit', 18, 2)->default(1000);
            $table->decimal('monthly_limit', 18, 2)->default(10000);
            $table->decimal('per_transaction_limit', 18, 2)->default(500);
            
            // Spending Tracking
            $table->decimal('daily_spent', 18, 2)->default(0);
            $table->decimal('monthly_spent', 18, 2)->default(0);
            $table->decimal('total_spent', 18, 2)->default(0);
            $table->date('daily_reset_at')->nullable();
            $table->date('monthly_reset_at')->nullable();
            
            // Status
            $table->enum('status', ['active', 'frozen', 'expired', 'cancelled', 'pending'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->string('frozen_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Settings
            $table->boolean('online_enabled')->default(true);
            $table->boolean('international_enabled')->default(true);
            $table->boolean('contactless_enabled')->default(true);
            $table->boolean('atm_enabled')->default(false);
            
            // Linked Services
            $table->boolean('apple_pay_enabled')->default(false);
            $table->boolean('google_pay_enabled')->default(false);
            $table->boolean('samsung_pay_enabled')->default(false);
            
            // Card Label
            $table->string('nickname')->nullable(); // User's custom name
            $table->string('color', 7)->default('#6366f1'); // Card color
            
            // Provider Integration
            $table->string('provider')->nullable(); // stripe, marqeta, etc
            $table->string('provider_card_id')->nullable();
            $table->json('provider_data')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['wallet_id', 'is_active']);
            $table->index('card_number_masked');
            $table->index('provider_card_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_cards');
    }
};
