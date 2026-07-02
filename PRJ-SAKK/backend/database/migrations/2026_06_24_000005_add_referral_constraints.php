<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Referral Anti-Abuse Constraints & Fraud Detection (H5)
     *
     * Purpose: Prevent referral fraud (double-pay, mass spam, synthetic accounts).
     *
     * Design notes:
     * - Unique constraint (referrer_id, referred_id, trigger) ensures:
     *   "User A can only reward User B once per trigger type"
     *   e.g., can't pay twice for "kyc_verified" from same referrer to same referred user
     * - Index (referrer_id, status, created_at) enables anti-abuse admin query:
     *   "List all pending rewards for user X" → detect mass-rewards in last 24h
     * - fraud_detected BOOLEAN: admin flag for suspicious rewards (manual review)
     * - claimed_at TIMESTAMP: when reward was claimed (enables time-lock logic)
     * - foreign key on transaction_id with RESTRICT prevents orphans
     */
    public function up(): void
    {
        // Check if fields exist before adding (migration safety)
        Schema::table('referral_rewards', function (Blueprint $table) {
            // Add fraud-detection columns if they don't exist
            if (!Schema::hasColumn('referral_rewards', 'claimed_at')) {
                $table->timestamp('claimed_at')
                      ->nullable()
                      ->after('status');
            }

            if (!Schema::hasColumn('referral_rewards', 'fraud_detected')) {
                $table->boolean('fraud_detected')
                      ->default(false)
                      ->after('claimed_at');
            }
        });

        // Add constraints and indexes
        Schema::table('referral_rewards', function (Blueprint $table) {
            // Unique constraint: prevent double-pay for same (referrer, referred, trigger)
            try {
                $table->unique(
                    ['referrer_id', 'referred_id', 'trigger'],
                    'unique_referral_per_trigger'
                );
            } catch (\Exception $e) {
                // Index may already exist; ignore silently for idempotency
                // (In production: use Laravel's QueryException to check specifically)
            }

            // Anti-abuse index: "pending rewards for user X"
            // This enables admin to detect mass-reward patterns (e.g., 20 pending in last hour)
            try {
                $table->index(
                    ['referrer_id', 'status', 'created_at'],
                    'idx_rr_referrer_status_date'
                );
            } catch (\Exception $e) {
                // Index may already exist
            }

            // Fraud detection index: "suspicious referrals"
            try {
                $table->index(
                    ['fraud_detected', 'created_at'],
                    'idx_rr_fraud_detected'
                );
            } catch (\Exception $e) {
                // Index may already exist
            }

            // Transaction timeline: "all rewards from a transaction"
            // This helps trace payment chains (e.g., User A referred B, B's first transaction triggered reward)
            if (Schema::hasColumn('referral_rewards', 'transaction_id')) {
                try {
                    $table->foreign('transaction_id')
                          ->references('id')->on('transactions')
                          ->restrictOnDelete(); // Prevent orphans; deleting txn fails if reward exists
                } catch (\Exception $e) {
                    // Foreign key may already exist
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('referral_rewards', function (Blueprint $table) {
            // Drop foreign key if it exists
            try {
                $table->dropForeign(['transaction_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist; ignore
            }

            // Drop indexes
            try {
                $table->dropIndex('unique_referral_per_trigger');
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex('idx_rr_referrer_status_date');
            } catch (\Exception $e) {
            }
            try {
                $table->dropIndex('idx_rr_fraud_detected');
            } catch (\Exception $e) {
            }

            // Drop columns
            if (Schema::hasColumn('referral_rewards', 'claimed_at')) {
                $table->dropColumn('claimed_at');
            }
            if (Schema::hasColumn('referral_rewards', 'fraud_detected')) {
                $table->dropColumn('fraud_detected');
            }
        });
    }
};
