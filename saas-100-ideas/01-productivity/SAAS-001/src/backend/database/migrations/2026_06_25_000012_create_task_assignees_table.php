<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Many-to-many pivot: tasks <-> users (assignees)
     * Supports multiple assignees per task.
     */
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->uuid('task_id');
            $table->uuid('user_id');

            $table->primary(['task_id', 'user_id']);

            $table->foreign('task_id')
                  ->references('id')
                  ->on('tasks')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // "My Tasks" query: find all tasks assigned to a user
            $table->index('user_id', 'idx_task_assignees_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
    }
};
