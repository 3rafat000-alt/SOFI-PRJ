<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AML Flags Table (C1 Blocker) — Compliance audit trail
     *
     * Purpose: Log every transaction flagged by an AML rule for manual review.
     * This is the compliance heart of the system — admins queue off this table.
     *
     * Design notes:
     * - Unique constraint on (transaction_id, rule_name) prevents duplicate flags
     *   on the same transaction from the same rule (idempotency).
     * - Composite index (status, severity, created_at) enables admin queue:
     *   "Show me all pending + high/critical flags in last 24h"
     * - Composite index (user_id, created_at) enables fraud detection:
     *   "Which users have most flags in last 30d?" (abuse detection).
     * - Composite index (rule_name, created_at) enables rule audit:
     *   "Which rule fires most? Is threshold tuning needed?"
     * - details JSON stores rule-specific inputs/outputs for compliance audit
     * - No soft-deletes: rejected flags must remain visible for compliance trails
     */
    public function up(): void
    {
        Schema::create('aml_flags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Foreign Keys
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Rule Metadata
            $table->string('rule_name', 100); // e.g., 'velocity_daily', 'threshold_10k', 'pep_check'
            $table->enum('severity', ['info', 'warning', 'high', 'critical'])->default('warning');

            // Rule Context (immutable snapshot of rule inputs + outputs)
            $table->json('rule_context')->nullable(); // e.g., {daily_count: 12, limit: 10, threshold_exceeded: true}

            // Review & Action
            $table->enum('status', ['pending', 'approved', 'rejected', 'manual_review'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();

            // Audit
            $table->timestamp('flagged_at'); // When did the flag trigger?
            $table->timestamps();

            // Constraints & Indexes
            // Prevent duplicate flags on same transaction from same rule
            $table->unique(['transaction_id', 'rule_name'], 'unique_txn_rule');

            // Admin queue: "pending + high/critical flags"
            $table->index(
                ['status', 'severity', 'created_at'],
                'idx_aml_admin_queue'
            );

            // Fraud detection: "flags by user, last 30d"
            $table->index(
                ['user_id', 'flagged_at'],
                'idx_aml_user_date'
            );

            // Rule audit: "which rule fires most?"
            $table->index(
                ['rule_name', 'created_at'],
                'idx_aml_rule_date'
            );

            // Performance: Foreign key column always indexed
            $table->index('transaction_id', 'idx_aml_transaction');
            $table->index('user_id', 'idx_aml_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_flags');
    }
};
