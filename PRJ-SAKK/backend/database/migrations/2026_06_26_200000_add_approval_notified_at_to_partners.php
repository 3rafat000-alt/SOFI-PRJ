<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency guard for the partner-approval email notification.
 * Stamps a timestamp the first time the approval email is dispatched so
 * subsequent admin saves (re-approve toggling) do NOT re-send.
 * Reversible: dropping the column restores the original schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->timestamp('approval_notified_at')->nullable()->after('kyc_approved_at');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->timestamp('approval_notified_at')->nullable()->after('kyc_approved_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('approval_notified_at')->nullable()->after('kyc_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('approval_notified_at');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('approval_notified_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('approval_notified_at');
        });
    }
};
