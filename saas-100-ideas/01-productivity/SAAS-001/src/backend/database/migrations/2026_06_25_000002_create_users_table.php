<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create users table, workspace_user pivot, and resolve
     * circular FK between workspaces.owner_id and users.current_workspace_id.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->string('locale', 10)->default('ar');
            $table->string('timezone', 100)->default('UTC');
            $table->uuid('current_workspace_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('current_workspace_id', 'idx_users_current_workspace');
        });

        // Add FK: workspaces.owner_id → users.id (resolved after users table exists)
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreign('owner_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // Add FK: users.current_workspace_id → workspaces.id
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('current_workspace_id')
                  ->references('id')
                  ->on('workspaces')
                  ->onDelete('set null');
        });

        // Create workspace_user pivot table
        Schema::create('workspace_user', function (Blueprint $table) {
            $table->uuid('workspace_id');
            $table->uuid('user_id');
            $table->string('role', 20)->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->primary(['workspace_id', 'user_id']);

            $table->foreign('workspace_id')
                  ->references('id')
                  ->on('workspaces')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->index('user_id', 'idx_workspace_user_user_id');
            $table->index(['workspace_id', 'role'], 'idx_workspace_user_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_workspace_id']);
        });

        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        Schema::dropIfExists('users');
    }
};
