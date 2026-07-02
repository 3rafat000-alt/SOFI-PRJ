<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('happy_clients')->default(0);
            $table->unsignedInteger('properties_listed')->default(0);
            $table->unsignedInteger('agents_count')->default(0);
            $table->unsignedTinyInteger('satisfaction_pct')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_stats');
    }
};
