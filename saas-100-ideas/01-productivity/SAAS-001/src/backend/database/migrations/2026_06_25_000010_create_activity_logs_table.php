<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic activity log (compatible with spatie/laravel-activitylog).
     * Tracks all destructive operations for audit trail.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('subject_type');
            $table->uuid('subject_id');
            $table->string('description');
            $table->string('event', 50)->nullable();
            $table->jsonb('properties')->nullable();
            $table->timestamp('created_at');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Polymorphic subject lookup (per-entity timeline)
            $table->index(['subject_type', 'subject_id'], 'idx_activity_logs_subject');

            // Actor lookup
            $table->index('user_id', 'idx_activity_logs_user');

            // Event-based queries
            $table->index(['event', 'created_at'], 'idx_activity_logs_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
