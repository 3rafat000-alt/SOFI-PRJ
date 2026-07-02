<?php

namespace App\Enums;

enum CardStatus: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::FROZEN => 'Frozen',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::PENDING => 'Pending Activation',
        };
    }

    public function labelAr(): string
    {
        return match($this) {
            self::ACTIVE => 'نشط',
            self::FROZEN => 'مجمد',
            self::EXPIRED => 'منتهي',
            self::CANCELLED => 'ملغي',
            self::PENDING => 'في انتظار التفعيل',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::FROZEN => 'blue',
            self::EXPIRED => 'gray',
            self::CANCELLED => 'red',
            self::PENDING => 'yellow',
        };
    }
}
