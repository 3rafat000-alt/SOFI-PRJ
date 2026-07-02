<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->uuid('user_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('task_id')
                  ->references('id')
                  ->on('tasks')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Report read path: time entries per user, chronological
            $table->index(['user_id', 'started_at'], 'idx_time_entries_user_started');

            // Per-task time aggregation
            $table->index('task_id', 'idx_time_entries_task');

            // Composite for unique constraint checks
            $table->index(['task_id', 'user_id'], 'idx_time_entries_task_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
