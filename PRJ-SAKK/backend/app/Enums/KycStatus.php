<?php

namespace App\Enums;

/**
 * User-level KYC status (2-level system).
 *
 * Only three states matter functionally:
 *   PENDING   → غير موثّق (unverified, default on signup)
 *   VERIFIED  → موثّق (completed email + phone + id_document + selfie)
 *   REJECTED  → مرفوض (an admin flagged/rejected a document after review)
 *
 * SUBMITTED is retained for backward compatibility with existing rows/enums
 * but is no longer assigned by the new flow.
 */
enum KycStatus: string
{
    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    /**
     * Alias: a fully verified user is, by definition, KYC level 2 (the max
     * VERIFIED_LEVEL). The DB `kyc_status` enum column only stores the four
     * backed values above, so this resolves to the VERIFIED case (value
     * 'verified') — safe to persist, while reading as "verified at level 2".
     */
    public const VERIFIED_LEVEL_2 = self::VERIFIED;

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Unverified',
            self::SUBMITTED => 'Under Review',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
        };
    }

    public function labelAr(): string
    {
        return match($this) {
            self::PENDING => 'غير موثّق',
            self::SUBMITTED => 'قيد المراجعة',
            self::VERIFIED => 'موثّق',
            self::REJECTED => 'مرفوض',
        };
    }

    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::SUBMITTED => 'yellow',
            self::VERIFIED => 'green',
            self::REJECTED => 'red',
        };
    }
}
