<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            // Merchant/brand display name for the requester when the request comes
            // from an integrated platform (e.g. "TaskSync Pro") rather than from
            // a personal SAKK account. The mobile app shows this prominently on
            // the pay page instead of the requester's personal name.
            $table->string('merchant_name', 60)->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('merchant_name');
        });
    }
};
