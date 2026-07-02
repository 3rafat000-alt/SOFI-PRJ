<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AML Rule Runs Table (C1 Blocker) — Immutable compliance audit log
     *
     * Purpose: Log every execution of an AML rule for compliance and debugging.
     * This table is APPEND-ONLY — never updated, never soft-deleted, never modified.
     *
     * Design notes:
     * - No auto-increment: rules run millions of times/day; UUID too expensive;
     *   use database auto-increment for local uniqueness (compliance: replayable by ID)
     * - input_snapshot JSON: exact state fed to rule (user_id, amount, daily_spent, etc.)
     * - output_snapshot JSON: rule result (flag=Y/N, reason, severity, suggested_action)
     * - execution_ms helps detect rule timeout/slowness issues (e.g., OFAC check taking >5s)
     * - Composite index (user_id, triggered_at) enables: "All rule runs for user X in date range"
     * - Composite index (rule_code, created_at) enables: "Which transactions triggered rule X?"
     * - Index (result, created_at) enables: "How many rules fired per day?" (KPI + audit)
     * - No FK constraint on user/transaction: allow orphans (compliance requires full history)
     */
    public function up(): void
    {
        Schema::create('aml_rule_runs', function (Blueprint $table) {
            $table->id();

            // Parties (not foreign keys — orphans allowed for compliance)
            $table->unsignedBigInteger('user_id'); // User who triggered rule (nullable if system rule)
            $table->unsignedBigInteger('transaction_id')->nullable(); // Optional: may be rule run on general user check

            // Rule Metadata
            $table->string('rule_code', 50); // e.g., 'velocity_daily', 'threshold_10k', 'pep_check'

            // Execution Result
            $table->enum('result', ['pass', 'flag', 'error'])->default('pass');

            // Snapshots (immutable evidence for compliance replay)
            $table->json('input_data'); // Snapshot of rule inputs: {user_id: 123, amount: 5000, daily_spent: 45000, ...}
            $table->json('output_data'); // Rule output: {flagged: true, reason: "daily_limit_exceeded", severity: "high", flag_id: 456}

            // Performance Monitoring
            $table->unsignedInteger('execution_ms')->default(0); // How long did rule take? Detect slowness/timeouts.

            // Audit
            $table->timestamps(); // created_at = when rule ran; updated_at = never used (APPEND-ONLY)

            // Indexes for compliance queries
            // "All rule runs for user X in date range" — supports compliance team forensics
            $table->index(['user_id', 'created_at'], 'idx_aml_rule_user_date');

            // "Which transactions triggered rule X?" — helps fine-tune thresholds
            $table->index(['rule_code', 'created_at'], 'idx_aml_rule_code_date');

            // "How many rules fired per day?" — KPI + rule effectiveness monitoring
            $table->index(['result', 'created_at'], 'idx_aml_rule_result_date');

            // Performance: transaction_id query (optional, for cross-referencing)
            $table->index('transaction_id', 'idx_aml_rule_txn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_rule_runs');
    }
};
