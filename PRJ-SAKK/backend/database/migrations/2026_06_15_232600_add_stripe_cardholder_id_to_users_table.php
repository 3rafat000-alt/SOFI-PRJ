<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_cardholder_id')->nullable()->after('apple_id');
            $table->string('stripe_customer_id')->nullable()->after('stripe_cardholder_id');
            $table->index('stripe_cardholder_id');
            $table->index('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['stripe_cardholder_id']);
            $table->dropIndex(['stripe_customer_id']);
            $table->dropColumn(['stripe_cardholder_id', 'stripe_customer_id']);
        });
    }
};
