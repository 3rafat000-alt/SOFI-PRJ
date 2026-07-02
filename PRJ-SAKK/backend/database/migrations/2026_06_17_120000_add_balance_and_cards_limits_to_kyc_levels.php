<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_levels', function (Blueprint $table) {
            $table->json('balance_limit')->nullable()->after('limits');
            $table->integer('cards_limit')->default(0)->after('balance_limit');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_levels', function (Blueprint $table) {
            $table->dropColumn(['balance_limit', 'cards_limit']);
        });
    }
};
