<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'kyc_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('kyc_level')->default(0)->after('kyc_status');
                $table->decimal('daily_limit', 18, 2)->default(100)->after('kyc_level');
                $table->decimal('monthly_limit', 18, 2)->default(500)->after('daily_limit');
                $table->string('preferred_currency', 10)->default('USD')->after('monthly_limit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'kyc_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['kyc_level', 'daily_limit', 'monthly_limit', 'preferred_currency']);
            });
        }
    }
};
