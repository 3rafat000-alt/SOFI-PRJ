<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->comment('sale/rent');
            $table->decimal('price', 12, 2)->comment('transaction price');
            $table->string('currency', 3)->default('USD');
            $table->decimal('commission_rate', 5, 2)->default(0.50)->comment('percentage');
            $table->decimal('commission_amount', 10, 2)->comment('calculated: price * rate / 100');
            $table->date('deal_date');
            $table->string('client_name');
            $table->string('client_phone', 20)->nullable();
            $table->string('status', 20)->default('pending')->comment('pending/confirmed/cancelled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
