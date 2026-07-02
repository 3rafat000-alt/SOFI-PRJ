<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Fraud Detection Fields to Transactions Table (C5)
     *
     * Purpose: Enable fraud detection, chargeback tracking, and refund root-cause analysis.
     *
     * Design notes:
     * - flagged_for_fraud BOOLEAN: quick KPI "fraud loss rate" = SUM(amount WHERE flagged_for_fraud=true) / SUM(amount)
     * - fraud_reason VARCHAR(255): root-cause tracking (e.g., 'aml_rule_velocity', 'chargeback_initiated', 'manual_admin')
     * - chargeback_status ENUM: tracks chargeback lifecycle (none → initiated → won/lost)
     * - Composite index (flagged_for_fraud, created_at) enables fraud report queries:
     *   "Show me all flagged transactions from last 30d, sorted by date"
     * - Index on (chargeback_status, created_at) enables chargeback KPI:
     *   "How many chargebacks initiated/won/lost per day?"
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Fraud Detection Flags
            $table->boolean('flagged_for_fraud')
                  ->default(false)
                  ->after('status'); // Place right after status for logical grouping

            // Root Cause Tracking
            $table->string('fraud_reason', 255)
                  ->nullable()
                  ->after('flagged_for_fraud');
            // e.g., 'aml_rule_velocity', 'aml_rule_threshold_10k', 'chargeback_initiated',
            //       'manual_admin_review', 'duplicate_detection'

            // Chargeback Lifecycle
            $table->enum('chargeback_status', ['none', 'initiated', 'won', 'lost'])
                  ->default('none')
                  ->after('fraud_reason');

            // Indexes
            // KPI: "Fraud transactions in last 30d"
            $table->index(
                ['flagged_for_fraud', 'created_at'],
                'idx_txn_fraud_flagged'
            );

            // KPI: "Chargeback rate per day"
            $table->index(
                ['chargeback_status', 'created_at'],
                'idx_txn_chargeback_status'
            );

            // Root-cause analysis: "How many transactions flagged for reason X?"
            $table->index(
                ['fraud_reason', 'created_at'],
                'idx_txn_fraud_reason'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_txn_fraud_flagged');
            $table->dropIndex('idx_txn_chargeback_status');
            $table->dropIndex('idx_txn_fraud_reason');

            $table->dropColumn('flagged_for_fraud');
            $table->dropColumn('fraud_reason');
            $table->dropColumn('chargeback_status');
        });
    }
};
