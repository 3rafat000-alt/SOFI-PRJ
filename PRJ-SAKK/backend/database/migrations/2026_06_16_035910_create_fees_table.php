<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Management System for SAKK Wallet
 * 
 * Supports:
 * - Deposit fees (USDT, ShamCash)
 * - Withdrawal fees (USDT, ShamCash)
 * - Card funding fees (Virtual Card)
 * - Exchange fees (USD ↔ SYP)
 * - Per currency configuration
 * - Fixed + Percentage fee structure
 * - Min/Max fee limits
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            
            // Fee identification
            $table->string('code', 50)->unique(); // deposit_usdt, withdraw_syp, card_fund, exchange
            $table->string('name'); // Display name (Arabic)
            $table->string('name_en')->nullable(); // Display name (English)
            $table->text('description')->nullable();
            
            // Fee type
            $table->enum('type', ['deposit', 'withdrawal', 'card_fund', 'exchange', 'transfer']);
            $table->string('currency', 10)->default('USD'); // USD, SYP, USDT
            $table->string('payment_method')->nullable(); // ccpayment, shamcash, stripe, internal
            
            // Fee structure
            $table->decimal('fixed_amount', 20, 6)->default(0); // Fixed fee amount
            $table->decimal('percentage', 8, 4)->default(0); // Percentage (e.g., 2.5 = 2.5%)
            $table->decimal('min_fee', 20, 6)->default(0); // Minimum fee
            $table->decimal('max_fee', 20, 6)->nullable(); // Maximum fee (null = no limit)
            
            // Limits
            $table->decimal('min_amount', 20, 6)->default(0); // Minimum transaction amount
            $table->decimal('max_amount', 20, 6)->nullable(); // Maximum transaction amount
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional configuration
            
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'currency', 'is_active']);
            $table->index(['code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
