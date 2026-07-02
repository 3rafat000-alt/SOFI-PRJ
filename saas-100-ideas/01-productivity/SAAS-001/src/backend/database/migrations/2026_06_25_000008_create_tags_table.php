<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->string('name', 50);
            $table->string('color', 7)->default('#6366F1');
            $table->timestamps();

            $table->foreign('workspace_id')
                  ->references('id')
                  ->on('workspaces')
                  ->onDelete('cascade');

            // Unique tag name per workspace
            $table->unique(['workspace_id', 'name'], 'tags_workspace_id_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
