<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redesign support: add a dual-currency limits column to kyc_levels.
 *
 * The new 2-level system stores per-currency limits as JSON:
 *   { "USD": {"daily":.., "monthly":.., "single":..},
 *     "SYP": {"daily":.., "monthly":.., "single":..} }
 *
 * The legacy decimal columns (daily_limit, monthly_limit, ...) are kept for
 * backward compatibility and mirror the USD values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('kyc_levels', 'limits')) {
                $table->json('limits')->nullable()->after('requirements');
            }
            if (!Schema::hasColumn('kyc_levels', 'key')) {
                $table->string('key')->nullable()->after('level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kyc_levels', function (Blueprint $table) {
            if (Schema::hasColumn('kyc_levels', 'limits')) {
                $table->dropColumn('limits');
            }
            if (Schema::hasColumn('kyc_levels', 'key')) {
                $table->dropColumn('key');
            }
        });
    }
};
