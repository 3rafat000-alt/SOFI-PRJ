<?php

declare(strict_types=1);

use App\Enums\AgentType;
use App\Enums\RepairActionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_repair_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            // Link to agent run
            $table->foreignId('agent_run_id')->constrained('agent_runs')->cascadeOnDelete();

            // Action identity
            $table->string('agent_type');
            $table->string('action_type', 64);       // freeze_wallet | reverse_txn | adjust_balance | approve_kyc | reject_kyc
            $table->string('action_category', 32);   // financial | kyc | aml | system

            // Target
            $table->nullableMorphs('targetable');    // wallet, transaction, kyc_verification, user
            $table->json('target_snapshot')->nullable(); // before-state snapshot for rollback

            // Action payload
            $table->json('payload');                 // what to do
            $table->text('reason');                  // human-readable justification
            $table->decimal('financial_impact', 18, 8)->default(0); // SYP/USD amount affected

            // Cryptographic signing
            $table->string('signing_key_fingerprint', 64)->nullable();
            $table->text('signature')->nullable();
            $table->timestamp('signed_at')->nullable();

            // Execution
            $table->string('status')->default(RepairActionStatus::PENDING_SIGNING->value);
            $table->timestamp('executed_at')->nullable();
            $table->json('execution_result')->nullable();

            // Escalation
            $table->boolean('escalated_to_human')->default(false);
            $table->foreignId('escalated_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable();
            $table->text('escalation_note')->nullable();

            // Rollback
            $table->boolean('is_rolled_back')->default(false);
            $table->timestamp('rolled_back_at')->nullable();
            $table->json('rollback_payload')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('action_type');
            $table->index(['agent_type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_repair_actions');
    }
};
