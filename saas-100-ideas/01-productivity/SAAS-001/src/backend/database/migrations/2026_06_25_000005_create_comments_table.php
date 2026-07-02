<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->uuid('user_id');
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('task_id')
                  ->references('id')
                  ->on('tasks')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Comment thread read path: comments per task, newest first
            $table->index(['task_id', 'created_at'], 'idx_comments_task_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
