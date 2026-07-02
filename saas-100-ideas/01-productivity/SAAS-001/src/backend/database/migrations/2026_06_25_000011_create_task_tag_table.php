<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Many-to-many pivot: tasks <-> tags
     */
    public function up(): void
    {
        Schema::create('task_tag', function (Blueprint $table) {
            $table->uuid('task_id');
            $table->uuid('tag_id');

            $table->primary(['task_id', 'tag_id']);

            $table->foreign('task_id')
                  ->references('id')
                  ->on('tasks')
                  ->onDelete('cascade');

            $table->foreign('tag_id')
                  ->references('id')
                  ->on('tags')
                  ->onDelete('cascade');

            // Reverse lookup: all tasks for a given tag
            $table->index('tag_id', 'idx_task_tag_tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_tag');
    }
};
