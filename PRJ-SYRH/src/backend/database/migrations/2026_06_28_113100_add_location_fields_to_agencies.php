<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->foreignId('governorate_id')->nullable()->constrained('governorates')->nullOnDelete()->after('address');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete()->after('governorate_id');
            $table->decimal('lat', 10, 7)->nullable()->after('area_id');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropForeign(['governorate_id']);
            $table->dropForeign(['area_id']);
            $table->dropColumn(['governorate_id', 'area_id', 'lat', 'lng']);
        });
    }
};
