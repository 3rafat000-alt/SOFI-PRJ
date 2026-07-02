<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Basic Info
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            
            // Profile
            $table->string('avatar')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('country_code', 3)->default('EG');
            $table->string('language', 5)->default('ar');
            $table->string('timezone')->default('Africa/Cairo');
            
            // KYC
            $table->enum('kyc_status', ['pending', 'submitted', 'verified', 'rejected'])->default('pending');
            $table->timestamp('kyc_verified_at')->nullable();
            $table->json('kyc_data')->nullable();
            
            // Account Status
            $table->enum('status', ['active', 'suspended', 'banned', 'pending'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->string('pin_code', 6)->nullable(); // Transaction PIN
            
            // Security
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->json('two_factor_recovery_codes')->nullable();
            
            // OAuth
            $table->string('google_id')->nullable();
            $table->string('apple_id')->nullable();
            
            // Referral
            $table->string('referral_code', 10)->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Metadata
            $table->string('fcm_token')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'is_active']);
            $table->index('kyc_status');
            $table->index('referral_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
