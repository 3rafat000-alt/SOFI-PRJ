<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_levels', function (Blueprint $table) {
            $table->id();
            $table->integer('level');
            $table->string('name');
            $table->string('name_ar');
            $table->text('description');
            $table->text('description_ar')->nullable();
            $table->json('requirements');
            $table->decimal('daily_limit', 18, 2);
            $table->decimal('monthly_limit', 18, 2);
            $table->decimal('single_transaction_limit', 18, 2);
            $table->decimal('withdrawal_limit', 18, 2);
            $table->boolean('can_transfer')->default(true);
            $table->boolean('can_withdraw')->default(true);
            $table->boolean('can_create_card')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('level');
        });

        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('level');
            $table->enum('verification_type', ['email', 'phone', 'id_document', 'selfie', 'address_proof', 'video']);
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'expired']);
            $table->string('document_path')->nullable();
            $table->string('document_type')->nullable();
            $table->json('extracted_data')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'verification_type']);
            $table->index(['status', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
        Schema::dropIfExists('kyc_levels');
    }
};
