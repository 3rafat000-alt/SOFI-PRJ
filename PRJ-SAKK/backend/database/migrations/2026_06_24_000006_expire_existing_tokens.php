<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Expire existing Sanctum tokens that have no expiration.
 *
 * Sanctum token expiry was set to null in config/sanctum.php, meaning every
 * issued token had indefinite validity. After setting 'expiration' => 1440
 * (24h), pre-existing tokens must be expired so they are not grandfathered
 * in with permanent validity.
 *
 * This migration sets expires_at to 24h from now for all tokens where
 * expires_at is null. Fresh tokens issued after the config change will
 * get their expiry from Sanctum automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('personal_access_tokens')
            ->whereNull('expires_at')
            ->update(['expires_at' => now()->addHours(24)]);
    }

    public function down(): void
    {
        // Irreversible — we cannot know which tokens were originally permanent.
        // The fix is one-way: tokens must now have an expiry date.
    }
};
