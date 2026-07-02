<?php

namespace App\Enums;

enum CardType: string
{
    case VIRTUAL = 'virtual';
    case PHYSICAL = 'physical';

    public function label(): string
    {
        return match($this) {
            self::VIRTUAL => 'Virtual Card',
            self::PHYSICAL => 'Physical Card',
        };
    }

    public function labelAr(): string
    {
        return match($this) {
            self::VIRTUAL => 'بطاقة افتراضية',
            self::PHYSICAL => 'بطاقة فعلية',
        };
    }
}
