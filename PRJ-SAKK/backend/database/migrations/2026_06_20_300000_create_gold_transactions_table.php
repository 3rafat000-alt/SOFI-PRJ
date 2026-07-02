<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gold_wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // buy / sell
            $table->string('karat', 10);
            $table->decimal('grams', 12, 4);
            $table->decimal('price_per_gram_usd', 12, 2);
            $table->decimal('total_usd', 14, 2);
            $table->decimal('fee_usd', 12, 2)->default(0);
            $table->decimal('usd_rate_at_time', 12, 2)->nullable(); // SYP->USD rate
            $table->string('reference', 36)->unique();
            $table->string('status', 20)->default('completed'); // pending, completed, failed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_transactions');
    }
};
