<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Currency & Balance
            $table->string('currency', 10); // USD, SYP
            $table->decimal('balance', 18, 8)->default(0);
            $table->decimal('available_balance', 18, 8)->default(0); // balance - pending
            $table->decimal('pending_balance', 18, 8)->default(0); // held for pending transactions
            
            // Limits
            $table->decimal('daily_limit', 18, 2)->default(10000);
            $table->decimal('monthly_limit', 18, 2)->default(100000);
            $table->decimal('daily_spent', 18, 2)->default(0);
            $table->decimal('monthly_spent', 18, 2)->default(0);
            $table->date('daily_reset_at')->nullable();
            $table->date('monthly_reset_at')->nullable();
            
            // Statistics
            $table->decimal('total_deposits', 18, 8)->default(0);
            $table->decimal('total_withdrawals', 18, 8)->default(0);
            $table->decimal('total_sent', 18, 8)->default(0);
            $table->decimal('total_received', 18, 8)->default(0);
            $table->unsignedInteger('transaction_count')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_frozen')->default(false);
            $table->string('frozen_reason')->nullable();
            
            // Crypto specific
            $table->string('network')->nullable(); // TRC20, ERC20
            $table->string('deposit_address')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['user_id', 'currency']);
            $table->index(['currency', 'is_active']);
            $table->index('deposit_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
