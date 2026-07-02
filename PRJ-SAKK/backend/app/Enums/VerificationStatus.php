<?php

namespace App\Enums;

/**
 * Item-level verification status for a single KYC document / verification step
 * (email, phone, id_document, selfie). Distinct from the user-level KycStatus.
 *
 *   PENDING  → submitted / code-sent, awaiting confirmation
 *   APPROVED → confirmed or auto-approved (may still await admin review)
 *   REJECTED → rejected by an admin during review
 */
enum VerificationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function labelAr(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::APPROVED => 'مقبول',
            self::REJECTED => 'مرفوض',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
        };
    }
}
