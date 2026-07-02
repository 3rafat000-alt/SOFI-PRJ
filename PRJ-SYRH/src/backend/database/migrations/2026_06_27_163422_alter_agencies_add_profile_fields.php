<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('email')->nullable()->after('slug');
            $table->string('whatsapp', 20)->nullable()->after('phone');
            $table->text('description_ar')->nullable()->after('whatsapp');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->string('address')->nullable()->after('description_en');
            $table->string('license_no', 50)->nullable()->after('address');
            $table->string('status', 20)->default('pending')->after('verified_at')->comment('pending/active/suspended');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['email', 'whatsapp', 'description_ar', 'description_en', 'address', 'license_no', 'status']);
        });
    }
};
