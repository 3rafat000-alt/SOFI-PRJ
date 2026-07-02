<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attribute company-side payroll ledger rows (PAYROLL_OUT) to a company.
 * transactions.user_id / wallet_id stay NOT NULL — company rows keep user_id =
 * the portal operator who ran payroll (so existing user_id-keyed history queries
 * are unaffected) and gain company_id for company-scoped reporting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('wallet_id')->constrained()->nullOnDelete();
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the index before the column so SQLite's table rebuild has no
            // dangling reference to company_id.
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
