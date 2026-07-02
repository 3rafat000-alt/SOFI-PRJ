<?php

namespace App\Enums;

enum CardBrand: string
{
    case VISA = 'visa';
    case MASTERCARD = 'mastercard';

    public function label(): string
    {
        return match($this) {
            self::VISA => 'Visa',
            self::MASTERCARD => 'Mastercard',
        };
    }

    public function logo(): string
    {
        return match($this) {
            self::VISA => '/images/cards/visa.svg',
            self::MASTERCARD => '/images/cards/mastercard.svg',
        };
    }
}
