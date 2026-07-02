<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('channel', ['push', 'email', 'sms', 'in_app']);
            $table->string('subject')->nullable();
            $table->string('subject_ar')->nullable();
            $table->text('body');
            $table->text('body_ar')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('template_code')->nullable();
            $table->enum('channel', ['push', 'email', 'sms', 'in_app']);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['channel', 'status']);
        });

        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');
            $table->json('config');
            $table->string('sender_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(1);
            $table->timestamps();
        });

        Schema::create('email_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');
            $table->json('config');
            $table->string('from_email');
            $table->string('from_name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_providers');
        Schema::dropIfExists('sms_providers');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('notification_templates');
    }
};
