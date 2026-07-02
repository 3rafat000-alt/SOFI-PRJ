<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telegram support channel — mark tickets that originated from the support bot
 * and remember the chat to push agent replies back to. chat_id is the user's
 * stable Telegram id (same value the OTP link stored), so a linked user is
 * recognised automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('related_transaction');
            $table->index('telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['telegram_chat_id']);
            $table->dropColumn('telegram_chat_id');
        });
    }
};
