<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case CARD_LOAD = 'card_load';
    case CARD_UNLOAD = 'card_unload';
    case CARD_PAYMENT = 'card_payment';
    case CARD_REFUND = 'card_refund';
    case FEE = 'fee';
    case REWARD = 'reward';
    case ADJUSTMENT = 'adjustment';
    case EXCHANGE = 'exchange';
    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';
    case PAYROLL_OUT = 'payroll_out';
    case SALARY_IN = 'salary_in';

    public function label(): string
    {
        return match($this) {
            self::DEPOSIT => 'Deposit',
            self::WITHDRAWAL => 'Withdrawal',
            self::CARD_LOAD => 'Card Load',
            self::CARD_UNLOAD => 'Card Unload',
            self::CARD_PAYMENT => 'Card Payment',
            self::CARD_REFUND => 'Card Refund',
            self::FEE => 'Fee',
            self::REWARD => 'Reward',
            self::ADJUSTMENT => 'Adjustment',
            self::EXCHANGE => 'Currency Exchange',
            self::TRANSFER_OUT => 'Transfer Sent',
            self::TRANSFER_IN => 'Transfer Received',
            self::PAYROLL_OUT => 'Payroll Payout',
            self::SALARY_IN => 'Salary Received',
        };
    }

    public function labelAr(): string
    {
        return match($this) {
            self::DEPOSIT => 'إيداع',
            self::WITHDRAWAL => 'سحب',
            self::CARD_LOAD => 'شحن البطاقة',
            self::CARD_UNLOAD => 'تفريغ البطاقة',
            self::CARD_PAYMENT => 'دفع بالبطاقة',
            self::CARD_REFUND => 'استرداد',
            self::FEE => 'رسوم',
            self::REWARD => 'مكافأة',
            self::ADJUSTMENT => 'تعديل',
            self::EXCHANGE => 'تحويل عملة',
            self::TRANSFER_OUT => 'تحويل صادر',
            self::TRANSFER_IN => 'تحويل وارد',
            self::PAYROLL_OUT => 'دفع رواتب',
            self::SALARY_IN => 'راتب',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::DEPOSIT => 'arrow-down-circle',
            self::WITHDRAWAL => 'arrow-up-circle',
            self::CARD_LOAD, self::CARD_UNLOAD => 'credit-card',
            self::CARD_PAYMENT => 'shopping-cart',
            self::CARD_REFUND => 'refresh-ccw',
            self::FEE => 'percent',
            self::REWARD => 'gift',
            self::ADJUSTMENT => 'settings',
            self::EXCHANGE => 'repeat',
            self::TRANSFER_OUT => 'send',
            self::TRANSFER_IN => 'download',
            self::PAYROLL_OUT => 'briefcase',
            self::SALARY_IN => 'briefcase',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [
            self::DEPOSIT,
            self::CARD_UNLOAD,
            self::CARD_REFUND,
            self::REWARD,
            self::TRANSFER_IN,
            self::SALARY_IN,
        ]);
    }

    public function isDebit(): bool
    {
        return in_array($this, [
            self::WITHDRAWAL,
            self::CARD_LOAD,
            self::CARD_PAYMENT,
            self::FEE,
            self::TRANSFER_OUT,
            self::PAYROLL_OUT,
        ]);
    }
}
