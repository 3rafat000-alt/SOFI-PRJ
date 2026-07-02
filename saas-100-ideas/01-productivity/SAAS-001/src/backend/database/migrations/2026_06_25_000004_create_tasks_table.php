<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('creator_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 10)->default('medium');
            $table->string('status', 20)->default('todo');
            $table->integer('position')->default(0);
            $table->timestamp('due_date')->nullable();
            $table->integer('estimated_minutes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->onDelete('cascade');

            $table->foreign('creator_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Kanban read path: tasks by project + status, ordered by position
            $table->index(['project_id', 'status', 'position'], 'idx_tasks_project_status_position');
        });

        // Partial index: upcoming deadlines (non-null due dates)
        DB::statement('
            CREATE INDEX idx_tasks_due_date
            ON tasks (due_date)
            WHERE due_date IS NOT NULL
        ');

        // GIN index for Arabic full-text search on tasks (PostgreSQL only).
        // On sqlite/other dev drivers the app falls back to ILIKE search,
        // so we add a plain b-tree index on title instead.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                CREATE INDEX idx_tasks_search
                ON tasks
                USING GIN (to_tsvector('arabic', title || ' ' || COALESCE(description, '')))
            ");
        } else {
            DB::statement('CREATE INDEX idx_tasks_search ON tasks (title)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_tasks_due_date');
        DB::statement('DROP INDEX IF EXISTS idx_tasks_search');

        Schema::dropIfExists('tasks');
    }
};
