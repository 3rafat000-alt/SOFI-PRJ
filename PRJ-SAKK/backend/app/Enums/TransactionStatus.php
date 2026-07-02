<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REVERSED = 'reversed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REVERSED => 'Reversed',
            self::REFUNDED => 'Refunded',
        };
    }

    public function labelAr(): string
    {
        return match($this) {
            self::PENDING => 'معلق',
            self::PROCESSING => 'جاري المعالجة',
            self::COMPLETED => 'مكتمل',
            self::FAILED => 'فشل',
            self::CANCELLED => 'ملغي',
            self::REVERSED => 'معكوس',
            self::REFUNDED => 'مسترد',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::COMPLETED => 'green',
            self::PENDING, self::PROCESSING => 'yellow',
            self::FAILED, self::CANCELLED => 'red',
            self::REVERSED, self::REFUNDED => 'blue',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REVERSED,
            self::REFUNDED,
        ]);
    }
}
