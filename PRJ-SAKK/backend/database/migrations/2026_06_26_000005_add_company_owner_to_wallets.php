<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Let a Company own a wallet (its prefunded payroll balance) without breaking
 * the user-only invariant. Strategy: add nullable company_id, make user_id
 * nullable, and keep "exactly one owner" as an app-level guard (Wallet::creating).
 *
 * The existing unique(user_id, currency) keeps protecting USER wallets (company
 * rows have user_id = NULL, which that index treats as distinct). For company
 * rows we add a PARTIAL unique index where the engine supports it (sqlite/pgsql);
 * on MySQL the invariant is held by the firstOrCreate-under-lock path in
 * Company::companyWallet(). NOT a financial-field change — safe to keep simple.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->index(['company_id', 'currency']);
        });

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX wallets_company_currency_unique ON wallets (company_id, currency) WHERE company_id IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            DB::statement('DROP INDEX IF EXISTS wallets_company_currency_unique');
        }

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'currency']);
            $table->dropConstrainedForeignId('company_id');
            // Note: user_id is left nullable on rollback — pre-existing rows all
            // have a user_id, so this is harmless and avoids a fragile re-tighten.
        });
    }
};
