<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Timestamp for automatic rejection. Devices pending for > 72h auto-reject.
            $table->timestamp('auto_reject_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('auto_reject_at');
        });
    }
};
