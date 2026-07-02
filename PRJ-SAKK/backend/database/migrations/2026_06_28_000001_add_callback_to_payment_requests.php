<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            // Webhook callback: when this payment request is paid, SAKK fires
            // a POST to callback_url with an HMAC-SHA256 signature.
            $table->string('callback_url', 512)->nullable()->after('response_note');
            $table->string('callback_secret', 64)->nullable()->after('callback_url');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['callback_url', 'callback_secret']);
        });
    }
};
