<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('uuid')
                    ->constrained()->nullOnDelete();
            }
        });

        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('uuid')
                    ->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
