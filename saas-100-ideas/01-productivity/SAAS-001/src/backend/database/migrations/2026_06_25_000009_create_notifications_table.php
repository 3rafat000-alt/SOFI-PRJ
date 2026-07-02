<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel-compatible notifications table.
     * Uses jsonb for data storage (PostgreSQL).
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type');
            $table->jsonb('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Unread notifications query: user's unread first
            $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');

            // Chronological sort
            $table->index('created_at', 'idx_notifications_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
