<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telegram OTP channel — bind a user to the Telegram chat that linked them so
 * phone OTPs can be delivered over Telegram (preferred when present) with the
 * existing WhatsApp gateway as fallback. A bot can only message a chat that has
 * started it, hence we store the chat id captured at link time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Telegram chat id (numeric, stored as string) the OTP is sent to.
            $table->string('telegram_chat_id')->nullable()->after('fcm_token');
            // Handle captured at link time (display/audit only).
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            // When the link was established.
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_username');

            $table->index('telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['telegram_chat_id']);
            $table->dropColumn(['telegram_chat_id', 'telegram_username', 'telegram_linked_at']);
        });
    }
};
