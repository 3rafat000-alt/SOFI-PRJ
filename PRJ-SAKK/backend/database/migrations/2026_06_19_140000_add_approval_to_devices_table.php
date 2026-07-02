<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Approval lifecycle for newly linked devices.
            $table->string('status')->default('approved')->after('public_key'); // pending | approved | rejected
            $table->timestamp('approved_at')->nullable()->after('status');
            // A freshly approved device cannot transact until this time (48h security hold).
            $table->timestamp('transactions_locked_until')->nullable()->after('approved_at');
            $table->string('last_ip')->nullable()->after('transactions_locked_until');
            $table->timestamp('last_active_at')->nullable()->after('last_ip');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'approved_at',
                'transactions_locked_until',
                'last_ip',
                'last_active_at',
            ]);
        });
    }
};
