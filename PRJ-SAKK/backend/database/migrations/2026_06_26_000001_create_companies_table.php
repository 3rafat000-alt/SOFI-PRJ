<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الشركات — third business audience after agents (الوكلاء) and merchants (التجار).
 * A company prefunds a dedicated wallet and distributes salaries to employees.
 * Mirrors the merchants table + the shared partner KYC columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('company_code')->unique(); // CO-XXXXXX

            // Portal operator — the human SAKK user who logs in to run payroll.
            // Nullable so admins can create a company before linking an operator.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('tax_id')->nullable();              // الرقم الضريبي
            $table->string('commercial_register')->nullable();  // السجل التجاري
            $table->text('description')->nullable();
            $table->string('logo')->nullable();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('governorate')->nullable();

            // Payroll is GATED: only flips true once KYC docs are approved by admin.
            $table->boolean('payroll_enabled')->default(false);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);

            // Shared partner KYC columns (verbatim from the agent/merchant pattern).
            $table->string('kyc_status', 20)->default('pending'); // pending, documents_required, approved, rejected, suspended
            $table->timestamp('kyc_submitted_at')->nullable();
            $table->timestamp('kyc_approved_at')->nullable();
            $table->text('kyc_rejection_reason')->nullable();

            $table->json('settings')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('company_code');
            $table->index('user_id');
            $table->index(['is_active', 'is_verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
