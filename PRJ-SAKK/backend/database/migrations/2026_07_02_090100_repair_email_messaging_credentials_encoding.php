<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * SEV-3 data repair. The original 2026_06_30_104910_create_email_integration
 * migration wrote credentials with Crypt::encrypt(json_encode(...)) (PHP
 * serialize()-based ciphertext) while the Integration model cast reads
 * Crypt::decryptString() + json_decode() (raw-string ciphertext, no PHP
 * serialize wrapper). On any database where that migration already ran,
 * the model reads back null for both the 'email' and 'messaging' rows'
 * credentials, AND every read-attempt in that migration's own logic fell
 * through its try/catch into json_decode(ciphertext) -> [] -> the
 * 'messaging' row got its mail keys silently stripped with nothing
 * recovered on the 'email' side either.
 *
 * This migration repairs both rows in place: try the model-correct read
 * first, then the legacy serialized read, keep whichever produced a
 * non-empty array, and always rewrite in the correct encryptString format.
 * Idempotent — safe to run twice (a row already in the correct format is
 * read successfully by the first attempt and rewritten byte-different but
 * value-identical).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->repairRow('email', [
            'mail_host' => '',
            'mail_port' => '587',
            'mail_username' => '',
            'mail_password' => '',
            'mail_from_address' => '',
            'mail_from_name' => 'SAKK',
        ]);

        $this->repairRow('messaging', []);
    }

    public function down(): void
    {
        // No-op: this migration only re-encodes already-present ciphertext
        // into the correct readable format. There is nothing structural to
        // revert, and reverting to a known-broken encoding would reintroduce
        // the SEV-3 bug on purpose. Kept for migration-history symmetry.
    }

    /**
     * @param  array<string,mixed>  $defaults  Shape to write when nothing recoverable exists.
     */
    private function repairRow(string $key, array $defaults): void
    {
        $row = DB::table('integrations')->where('key', $key)->first();
        if (!$row) {
            return; // row doesn't exist yet on this DB — nothing to repair
        }

        $creds = $this->bestEffortDecode($row->credentials);

        if ($creds === null) {
            Log::warning("integrations.{$key} credentials unrecoverable in both encryptString and legacy encrypt formats — resetting to encrypted empty defaults", [
                'integration_id' => $row->id,
            ]);
            $creds = $defaults;
        }

        DB::table('integrations')
            ->where('id', $row->id)
            ->update(['credentials' => Crypt::encryptString(json_encode($creds))]);
    }

    /**
     * Attempt to recover a plausible credentials array from whatever format
     * the stored ciphertext is actually in. Returns null only when nothing
     * usable could be extracted (empty/blank input, or both decrypt paths
     * plus a raw json_decode all fail/produce a non-array).
     *
     * @return array<string,mixed>|null
     */
    private function bestEffortDecode(?string $raw): ?array
    {
        if (!$raw) {
            return null;
        }

        // Path 1: correct format (model-readable) — Crypt::encryptString.
        try {
            $decoded = json_decode(Crypt::decryptString($raw), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        // Path 2: legacy format written by the original buggy migration —
        // Crypt::encrypt (PHP serialize()-wrapped ciphertext).
        try {
            $decoded = json_decode(Crypt::decrypt($raw), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        // Path 3: defensive — raw plaintext JSON (should not normally occur,
        // but costs nothing to check before giving up).
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return null;
    }
};
