<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('balance_grams', 12, 4)->default(0);
            $table->decimal('total_bought_grams', 12, 4)->default(0);
            $table->decimal('total_sold_grams', 12, 4)->default(0);
            $table->decimal('total_invested_usd', 14, 2)->default(0);
            $table->decimal('current_value_usd', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_wallets');
    }
};
