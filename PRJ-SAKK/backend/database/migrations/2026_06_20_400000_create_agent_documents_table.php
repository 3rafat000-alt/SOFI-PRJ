<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50); // license, id_card, commercial_record, tax_card, bank_account, contract, photo
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agent_id', 'document_type']);
            $table->index('status');
        });

        Schema::create('merchant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50); // commercial_record, tax_card, bank_account, license, id_card, contract, ownership
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['merchant_id', 'document_type']);
            $table->index('status');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->string('kyc_status', 20)->default('approved')->after('is_verified');
            $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_status');
            $table->timestamp('kyc_approved_at')->nullable()->after('kyc_submitted_at');
            $table->text('kyc_rejection_reason')->nullable()->after('kyc_approved_at');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->string('kyc_status', 20)->default('pending')->after('is_verified');
            $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_status');
            $table->timestamp('kyc_approved_at')->nullable()->after('kyc_submitted_at');
            $table->text('kyc_rejection_reason')->nullable()->after('kyc_approved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_documents');
        Schema::dropIfExists('agent_documents');

        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['kyc_status', 'kyc_submitted_at', 'kyc_approved_at', 'kyc_rejection_reason']);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['kyc_status', 'kyc_submitted_at', 'kyc_approved_at', 'kyc_rejection_reason']);
        });
    }
};
