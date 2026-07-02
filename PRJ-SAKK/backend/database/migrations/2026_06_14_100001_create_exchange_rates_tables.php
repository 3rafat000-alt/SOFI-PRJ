<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 10);
            $table->string('to_currency', 10);
            $table->decimal('rate', 20, 8);
            $table->decimal('buy_rate', 20, 8)->nullable();
            $table->decimal('sell_rate', 20, 8)->nullable();
            $table->decimal('spread', 8, 4)->default(0);
            $table->string('source')->default('manual');
            $table->boolean('is_active')->default(true);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency']);
            $table->index('is_active');
        });

        Schema::create('exchange_rate_history', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 10);
            $table->string('to_currency', 10);
            $table->decimal('rate', 20, 8);
            $table->decimal('buy_rate', 20, 8)->nullable();
            $table->decimal('sell_rate', 20, 8)->nullable();
            $table->string('source')->default('manual');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['from_currency', 'to_currency', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_history');
        Schema::dropIfExists('exchange_rates');
    }
};
