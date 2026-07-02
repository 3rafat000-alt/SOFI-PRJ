<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('system_settings', 'is_editable')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->boolean('is_editable')->default(true)->after('is_public');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('system_settings', 'is_editable')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->dropColumn('is_editable');
            });
        }
    }
};
