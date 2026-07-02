<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll batches + items — bulk salary distribution from a company wallet.
 *
 * Idempotency: both tables carry a UNIQUE idempotency_key so a double-submit or
 * a job retry pays each employee at most once. A batch ends `completed` or
 * `partially_completed` with {paid, held, failed} rollups; "Retry failed"
 * re-runs only `pending|failed` items (each item re-checks its own status under
 * a row lock before paying).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 10); // USD | SYP (one currency per batch)
            $table->string('status', 24)->default('draft'); // draft, pending, processing, partially_completed, completed, failed, cancelled

            $table->string('idempotency_key')->unique();

            $table->decimal('total_amount', 18, 8)->default(0);
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('paid_count')->default(0);
            $table->unsignedInteger('held_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('title')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payroll_batch_id')->constrained()->cascadeOnDelete();
            // Denormalized for the held-salary release lookup (avoids a join on
            // the hot path KycService::verifyPhoneCode runs).
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_employee_id')->nullable()->constrained('company_employees')->nullOnDelete();
            $table->foreignId('employee_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('employee_phone'); // canonical normalized digits
            $table->string('employee_name')->nullable();
            $table->string('currency', 10);
            $table->decimal('amount', 18, 8);

            $table->string('status', 20)->default('pending'); // pending, paid, held, failed, cancelled
            $table->string('idempotency_key')->unique();

            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamp('held_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Release lookup: held items for a given phone.
            $table->index(['status', 'employee_phone']);
            $table->index(['payroll_batch_id', 'status']);
            $table->index('employee_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_batches');
    }
};
