<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->string('url', 500);
            $table->jsonb('events');
            $table->string('secret', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->integer('last_status_code')->nullable();
            $table->text('last_response')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('workspace_id')
                  ->references('id')
                  ->on('workspaces')
                  ->onDelete('cascade');

            $table->index('workspace_id', 'idx_webhooks_workspace');
            $table->index('is_active', 'idx_webhooks_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
