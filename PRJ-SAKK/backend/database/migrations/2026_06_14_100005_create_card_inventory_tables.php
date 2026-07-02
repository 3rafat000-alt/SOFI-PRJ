<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('card_number_encrypted');
            $table->string('card_number_hash')->unique();
            $table->string('cvv_encrypted');
            $table->string('expiry_month', 2);
            $table->string('expiry_year', 4);
            $table->string('cardholder_name')->default('SAKK WALLET');
            $table->enum('brand', ['visa', 'mastercard']);
            $table->enum('type', ['virtual', 'physical']);
            $table->string('bin', 6)->nullable();
            $table->string('source_file')->nullable();
            $table->decimal('purchase_price', 18, 8)->default(10);
            $table->decimal('min_load', 18, 8)->default(100);
            $table->decimal('max_load', 18, 8)->default(5000);
            $table->boolean('is_assigned')->default(false);
            $table->foreignId('assigned_to')->nullable()->constrained('virtual_cards');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['is_assigned', 'brand', 'type']);
        });

        Schema::create('card_pricing', function (Blueprint $table) {
            $table->id();
            $table->enum('brand', ['visa', 'mastercard', 'all']);
            $table->enum('type', ['virtual', 'physical', 'all']);
            $table->decimal('purchase_price', 18, 8)->default(10);
            $table->decimal('monthly_fee', 18, 8)->default(0);
            $table->decimal('min_load', 18, 8)->default(100);
            $table->decimal('max_load', 18, 8)->default(5000);
            $table->decimal('load_fee_percentage', 8, 4)->default(0);
            $table->decimal('load_fee_fixed', 18, 8)->default(0);
            $table->decimal('transaction_fee_percentage', 8, 4)->default(0);
            $table->decimal('transaction_fee_fixed', 18, 8)->default(0);
            $table->decimal('atm_fee', 18, 8)->default(2);
            $table->decimal('international_fee_percentage', 8, 4)->default(3);
            $table->integer('kyc_level_required')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_pricing');
        Schema::dropIfExists('card_inventory');
    }
};
