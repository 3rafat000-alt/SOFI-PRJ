<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Identity
            $table->string('name');                 // shop / agent display name
            $table->string('agent_code')->unique(); // code used to identify the agent for cash withdrawal
            $table->string('owner_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();

            // Location
            $table->string('address');
            $table->string('city');
            $table->string('governorate')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Capabilities
            $table->json('services')->nullable();          // ["cash_in","cash_out"]
            $table->string('working_hours')->nullable();   // e.g. "9:00 ص - 9:00 م"
            $table->decimal('commission_rate', 5, 2)->default(0); // percentage charged per op
            $table->decimal('min_amount', 12, 2)->default(0);
            $table->decimal('max_amount', 12, 2)->nullable();

            // Reputation
            $table->decimal('rating', 2, 1)->default(5.0);
            $table->unsignedInteger('reviews_count')->default(0);

            // Flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'city']);
            $table->index('agent_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
