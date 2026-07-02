<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 32)->unique(); // TXN-XXXXXX
            
            // Parties
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained('virtual_cards')->nullOnDelete();
            
            // P2P
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            
            // Transaction Details
            $table->enum('type', [
                'deposit',      // Add money to wallet
                'withdrawal',   // Remove money from wallet
                'transfer_out', // P2P sent
                'transfer_in',  // P2P received
                'card_load',    // Load card from wallet
                'card_unload',  // Unload card to wallet
                'card_payment', // Card purchase
                'card_refund',  // Card refund
                'fee',          // Service fee
                'reward',       // Cashback, referral bonus
                'adjustment',   // Admin adjustment
            ]);
            
            $table->enum('category', [
                'wallet',
                'card',
                'p2p',
                'crypto',
                'fee',
                'reward',
                'adjustment',
            ]);
            
            // Amounts
            $table->string('currency', 10);
            $table->decimal('amount', 18, 8); // Positive for credit, negative for debit
            $table->decimal('fee', 18, 8)->default(0);
            $table->decimal('net_amount', 18, 8); // amount - fee
            $table->decimal('balance_before', 18, 8);
            $table->decimal('balance_after', 18, 8);
            
            // Exchange Rate (for multi-currency)
            $table->string('original_currency', 10)->nullable();
            $table->decimal('original_amount', 18, 8)->nullable();
            $table->decimal('exchange_rate', 18, 8)->nullable();
            
            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'reversed',
                'refunded',
            ])->default('pending');
            
            // Metadata
            $table->string('title'); // Human readable
            $table->text('description')->nullable();
            $table->string('merchant_name')->nullable();
            $table->string('merchant_category')->nullable();
            $table->json('metadata')->nullable();
            
            // External Reference
            $table->string('external_reference')->nullable(); // Provider's reference
            $table->string('provider')->nullable();
            $table->json('provider_response')->nullable();
            
            // Crypto specific
            $table->string('tx_hash')->nullable();
            $table->string('network')->nullable();
            $table->unsignedInteger('confirmations')->nullable();
            
            // Error handling
            $table->string('failure_reason')->nullable();
            $table->json('failure_details')->nullable();
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['wallet_id', 'status']);
            $table->index(['card_id', 'created_at']);
            $table->index(['reference']);
            $table->index(['status', 'created_at']);
            $table->index('tx_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
