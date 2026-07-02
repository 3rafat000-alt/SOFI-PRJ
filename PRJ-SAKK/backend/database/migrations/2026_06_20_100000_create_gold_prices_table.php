<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_prices', function (Blueprint $table) {
            $table->id();
            $table->string('karat', 10); // 24, 22, 21, 18
            $table->decimal('buy_price', 12, 2); // price per gram USD
            $table->decimal('sell_price', 12, 2);
            $table->decimal('spread', 5, 2)->default(0); // difference %
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('karat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_prices');
    }
};
