<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relax the `type` and `category` CHECK constraints on the transactions table.
 *
 * The columns were originally defined as enums, which compile to restrictive
 * CHECK constraints on SQLite. Adding new transaction types (e.g. `exchange`)
 * then fails at the DB layer. We convert them to plain strings — validation is
 * enforced at the application layer via the TransactionType / TransactionCategory
 * enums instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type', 32)->change();
            $table->string('category', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type', [
                'deposit', 'withdrawal', 'transfer_out', 'transfer_in',
                'card_load', 'card_unload', 'card_payment', 'card_refund',
                'fee', 'reward', 'adjustment',
            ])->change();

            $table->enum('category', [
                'wallet', 'card', 'p2p', 'crypto', 'fee', 'reward', 'adjustment',
            ])->change();
        });
    }
};
