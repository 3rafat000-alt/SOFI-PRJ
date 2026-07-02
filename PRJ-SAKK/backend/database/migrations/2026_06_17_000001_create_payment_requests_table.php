<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // public code used in the share link / QR

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // requester (payee)
            $table->string('currency', 10); // USD, SYP
            $table->decimal('amount', 18, 8);
            $table->string('note', 140)->nullable();

            // pending | paid | cancelled | expired
            $table->string('status', 20)->default('pending');

            $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
