<?php

declare(strict_types=1);

namespace App\Enums;

enum RepairActionStatus: string
{
    case PENDING_SIGNING = 'pending_signing';
    case SIGNED = 'signed';
    case EXECUTED = 'executed';
    case FAILED = 'failed';
    case ROLLED_BACK = 'rolled_back';
    case ESCALATED = 'escalated';

    public function isExecutable(): bool
    {
        return in_array($this, [self::SIGNED]);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::EXECUTED,
            self::FAILED,
            self::ROLLED_BACK,
            self::ESCALATED,
        ]);
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::PENDING_SIGNING => 'بانتظار التوقيع',
            self::SIGNED => 'موقّع',
            self::EXECUTED => 'منفّذ',
            self::FAILED => 'فشل',
            self::ROLLED_BACK => 'تم التراجع',
            self::ESCALATED => 'مُصعّد لمشرف بشري',
        };
    }
}
