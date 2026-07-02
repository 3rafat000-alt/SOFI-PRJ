<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SystemSetting;

/**
 * System-wide disbursement halt flag.
 *
 * Set by AuditLedgerIntegrity (audit:ledger) when a critical ledger-integrity
 * anomaly is detected in production. While active, every money-OUT entrypoint
 * (transfer / withdraw / convert / payroll disbursal) must refuse new
 * disbursals — money already reserved/held is untouched, only NEW outbound
 * movement is blocked.
 *
 * Cheap read path: SystemSetting::get() is cache-backed (60 min TTL, single
 * row lookup), so every money-out call pays one cache hit, not a query.
 */
class LedgerHaltGuard
{
    public const SETTING_KEY = 'system_disbursement_halt';

    /**
     * Is the platform currently under a disbursement halt?
     *
     * The stored value is a JSON object (`['active' => bool, ...]`), not a
     * bare bool — a naive `(bool) $value` cast is wrong here: PHP casts any
     * non-empty array to `true` regardless of its `active` key, so a
     * released state (`['active' => false]`) would still read as halted.
     * Read the `active` key explicitly instead.
     */
    public static function isHalted(): bool
    {
        $state = SystemSetting::get(self::SETTING_KEY, ['active' => false]);

        if (is_array($state)) {
            return (bool) ($state['active'] ?? false);
        }

        return (bool) $state;
    }

    /**
     * Engage the halt. Durable (SystemSetting row, cache invalidated on write
     * by SystemSetting::set) — survives process restarts and cache flushes.
     *
     * Production-only by design: this is a hard money-out lockdown and must
     * never trip in non-production environments (staging/testing), where
     * ledger drift is routinely seeded/test data and QA must keep working.
     * Callers (AuditLedgerIntegrity) already gate on environment before
     * calling this, but the guard enforces it here too so no caller can
     * accidentally engage a halt outside production.
     */
    public static function engage(string $reason): void
    {
        if (!app()->environment('production')) {
            return;
        }

        SystemSetting::set(self::SETTING_KEY, [
            'active' => true,
            'reason' => $reason,
            'engaged_at' => now()->toIso8601String(),
        ], 'json');
    }

    /**
     * Release the halt (admin/ops action after manual review — not called by
     * the auditor itself; a halt is a human-clears-it control by design).
     */
    public static function release(): void
    {
        SystemSetting::set(self::SETTING_KEY, [
            'active' => false,
        ], 'json');
    }

    /**
     * Throws when the platform is halted. Call this first in every money-out
     * service entrypoint (transfer/withdraw/convert/payroll disbursal).
     *
     * @throws \RuntimeException
     */
    public static function assertNotHalted(): void
    {
        if (self::isHalted()) {
            throw new \RuntimeException(
                'الخدمة متوقفة مؤقتاً لأسباب تتعلق بسلامة الأرصدة. يرجى المحاولة لاحقاً أو التواصل مع الدعم.'
            );
        }
    }
}
