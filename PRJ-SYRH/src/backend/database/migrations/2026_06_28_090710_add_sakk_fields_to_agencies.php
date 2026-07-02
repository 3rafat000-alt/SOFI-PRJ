<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('sakk_merchant_id', 100)->nullable()->after('commission_rate');
            $table->text('sakk_api_key_encrypted')->nullable()->after('sakk_merchant_id');
            $table->boolean('sakk_verified')->default(false)->after('sakk_api_key_encrypted');
            $table->timestamp('sakk_verified_at')->nullable()->after('sakk_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['sakk_merchant_id', 'sakk_api_key_encrypted', 'sakk_verified', 'sakk_verified_at']);
        });
    }
};
