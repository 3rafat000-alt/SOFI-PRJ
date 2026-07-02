<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Document Type
            $table->enum('document_type', [
                'national_id',
                'passport',
                'drivers_license',
                'residence_permit',
                'selfie',
                'selfie_with_id',
                'proof_of_address',
            ]);
            
            // File Info
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // image/jpeg, application/pdf
            $table->unsignedInteger('file_size');
            
            // Document Details
            $table->string('document_number')->nullable();
            $table->string('issuing_country', 3)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            // Verification
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            
            // OCR Data
            $table->json('ocr_data')->nullable();
            $table->json('extracted_data')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'document_type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
