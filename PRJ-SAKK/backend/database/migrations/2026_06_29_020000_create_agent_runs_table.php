<?php

declare(strict_types=1);

use App\Enums\AgentRunStatus;
use App\Enums\AgentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            // Agent identity
            $table->string('agent_type');           // AgentType enum value
            $table->string('agent_version', 32)->default('1.0.0');

            // Trigger context
            $table->string('trigger')->default('scheduled');   // scheduled | manual | webhook
            $table->nullableMorphs('triggerable');             // wallet, user, transaction, etc.

            // Execution state
            $table->string('status')->default(AgentRunStatus::RUNNING->value);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            // Results
            $table->unsignedInteger('items_scanned')->default(0);
            $table->unsignedInteger('anomalies_found')->default(0);
            $table->unsignedInteger('auto_repairs_triggered')->default(0);
            $table->unsignedInteger('escalations')->default(0);
            $table->json('summary')->nullable();              // structured findings summary
            $table->text('log')->nullable();                  // free-text execution log

            // Performance
            $table->unsignedInteger('duration_ms')->default(0);
            $table->boolean('threshold_breached')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('agent_type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['agent_type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_runs');
    }
};
