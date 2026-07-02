<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform revenue ledger (treasury).
 *
 * Records income the platform earns so it stops "disappearing": the exchange
 * spread profit in particular was previously only embedded in transaction
 * metadata and never counted in admin revenue. Each row is one revenue event,
 * tied to the transaction that produced it. Wallet balances are unaffected — the
 * spread is already retained in platform float (paid out less than mid-value);
 * this table is the accounting recognition of that income.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_revenues', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40);                 // exchange_spread | withdraw_fee | card_fee | deposit_fee | ...
            $table->string('currency', 10);               // USD | SYP
            $table->decimal('amount', 18, 8);             // always positive income
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // who generated it
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source', 'created_at']);
            $table->index(['currency', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_revenues');
    }
};
