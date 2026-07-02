<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('creator_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6366F1');
            $table->string('status', 20)->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('workspace_id')
                  ->references('id')
                  ->on('workspaces')
                  ->onDelete('cascade');

            $table->foreign('creator_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->index('workspace_id', 'idx_projects_workspace_id');
            $table->index('creator_id', 'idx_projects_creator_id');
        });

        // GIN index for Arabic full-text search on projects (PostgreSQL only).
        // On sqlite/other dev drivers the app falls back to ILIKE search,
        // so we add a plain b-tree index on name instead.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                CREATE INDEX idx_projects_search
                ON projects
                USING GIN (to_tsvector('arabic', name || ' ' || COALESCE(description, '')))
            ");
        } else {
            DB::statement('CREATE INDEX idx_projects_search ON projects (name)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_projects_search');

        Schema::dropIfExists('projects');
    }
};
