<?php

namespace App\Enums;

enum TransactionCategory: string
{
    case WALLET = 'wallet';
    case CARD = 'card';
    case CRYPTO = 'crypto';
    case EXCHANGE = 'exchange';
    case P2P = 'p2p';
    case FEE = 'fee';
    case REWARD = 'reward';
    case ADJUSTMENT = 'adjustment';
    case INVESTMENT = 'investment';
    case SAVINGS = 'savings';
    case PAYROLL = 'payroll';

    public function label(): string
    {
        return match($this) {
            self::WALLET => 'Wallet',
            self::CARD => 'Card',
            self::CRYPTO => 'Crypto',
            self::EXCHANGE => 'Currency Exchange',
            self::P2P => 'Peer to Peer',
            self::FEE => 'Fee',
            self::REWARD => 'Reward',
            self::ADJUSTMENT => 'Adjustment',
            self::INVESTMENT => 'Investment',
            self::SAVINGS => 'Savings',
            self::PAYROLL => 'Payroll',
        };
    }

    public function labelAr(): string
    {
        return match($this) {
            self::WALLET => 'المحفظة',
            self::CARD => 'البطاقة',
            self::CRYPTO => 'عملات رقمية',
            self::EXCHANGE => 'تحويل عملات',
            self::P2P => 'تحويل بين الأشخاص',
            self::FEE => 'رسوم',
            self::REWARD => 'مكافأة',
            self::ADJUSTMENT => 'تعديل',
            self::INVESTMENT => 'استثمار',
            self::SAVINGS => 'ادخار',
            self::PAYROLL => 'رواتب',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::WALLET => 'wallet',
            self::CARD => 'credit-card',
            self::CRYPTO => 'bitcoin',
            self::EXCHANGE => 'repeat',
            self::P2P => 'users',
            self::FEE => 'percent',
            self::REWARD => 'gift',
            self::ADJUSTMENT => 'settings',
            self::INVESTMENT => 'trending-up',
            self::SAVINGS => 'piggy-bank',
            self::PAYROLL => 'briefcase',
        };
    }
}
