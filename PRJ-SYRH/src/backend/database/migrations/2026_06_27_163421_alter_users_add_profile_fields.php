<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar_url')->nullable()->after('phone');
            $table->char('locale', 2)->default('ar')->after('avatar_url');
            $table->string('status', 20)->default('active')->after('locale');
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->dropColumn(['phone', 'avatar_url', 'locale', 'status']);
        });
    }
};
