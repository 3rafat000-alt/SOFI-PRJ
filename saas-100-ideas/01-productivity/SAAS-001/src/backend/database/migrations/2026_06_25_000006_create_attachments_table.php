<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic-ish attachment: belongs to either a task or a comment.
     * CHECK constraint ensures at least one parent is set.
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id')->nullable();
            $table->uuid('comment_id')->nullable();
            $table->uuid('user_id');
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->integer('size');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('task_id')
                  ->references('id')
                  ->on('tasks')
                  ->onDelete('cascade');

            $table->foreign('comment_id')
                  ->references('id')
                  ->on('comments')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->index('task_id', 'idx_attachments_task');
            $table->index('comment_id', 'idx_attachments_comment');
        });

        // Ensure attachment belongs to at least one parent. Laravel's Blueprint
        // has no table-level CHECK helper, so add it via raw SQL on PostgreSQL.
        // sqlite cannot ALTER TABLE ADD CONSTRAINT, so the rule is enforced in
        // application validation there.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('
                ALTER TABLE attachments
                ADD CONSTRAINT chk_attachments_parent
                CHECK ((task_id IS NOT NULL) OR (comment_id IS NOT NULL))
            ');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
