<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('merchant_code')->unique();

            $table->enum('type', ['physical', 'ecommerce', 'both'])->default('physical');

            $table->string('store_name');
            $table->string('owner_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('governorate')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('website_url')->nullable();
            $table->boolean('has_api_access')->default(true);

            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('environment', 20)->default('sandbox');

            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('total_earned', 14, 2)->default(0);

            $table->json('payment_methods')->nullable();
            $table->json('settings')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'type']);
            $table->index('merchant_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
