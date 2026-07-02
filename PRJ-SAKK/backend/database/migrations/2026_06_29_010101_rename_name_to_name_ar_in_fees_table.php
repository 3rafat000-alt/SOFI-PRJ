<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->string('name_ar')->after('code')->nullable();
        });

        DB::table('fees')->whereNotNull('name')->update([
            'name_ar' => DB::raw('name'),
        ]);

        Schema::table('fees', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn('name_ar');
        });
    }
};
