<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_prices', function (Blueprint $table) {
            // 'manual' = admin set by hand, 'auto' = fetched from global spot
            $table->string('source', 12)->default('manual')->after('spread');
        });
    }

    public function down(): void
    {
        Schema::table('gold_prices', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
