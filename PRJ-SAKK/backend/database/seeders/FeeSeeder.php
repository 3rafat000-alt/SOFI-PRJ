<?php

namespace Database\Seeders;

use App\Models\Fee;
use Illuminate\Database\Seeder;

/**
 * Seeds default fees for SAKK Wallet
 * 
 * Fee Structure:
 * - Deposits: Low fees to encourage deposits
 * - Withdrawals: Slightly higher to cover processing
 * - Card: Competitive rates
 */
class FeeSeeder extends Seeder
{
    public function run(): void
    {
        $fees = [
            // ==========================================
            // DEPOSIT FEES
            // ==========================================
            [
                'code' => Fee::CODE_DEPOSIT_USDT,
                'name_ar' => 'رسوم إيداع USDT',
                'name_en' => 'USDT Deposit Fee',
                'description' => 'رسوم إيداع العملات الرقمية USDT-TRC20 عبر CCPayment',
                'type' => Fee::TYPE_DEPOSIT,
                'currency' => 'USD',
                'payment_method' => 'ccpayment',
                'fixed_amount' => 0,
                'percentage' => 1.0, // 1%
                'min_fee' => 1.00, // Minimum $1
                'max_fee' => 50.00, // Maximum $50
                'min_amount' => 10.00, // Minimum deposit $10
                'max_amount' => 10000.00, // Maximum deposit $10,000
                'is_active' => true,
                'sort_order' => 1,
            ],

            // ==========================================
            // WITHDRAWAL FEES
            // ==========================================
            [
                'code' => Fee::CODE_WITHDRAW_USDT,
                'name_ar' => 'رسوم سحب USDT',
                'name_en' => 'USDT Withdrawal Fee',
                'description' => 'رسوم سحب العملات الرقمية USDT-TRC20 عبر CCPayment',
                'type' => Fee::TYPE_WITHDRAWAL,
                'currency' => 'USD',
                'payment_method' => 'ccpayment',
                'fixed_amount' => 1.00, // $1 network fee
                'percentage' => 1.5, // 1.5%
                'min_fee' => 2.00, // Minimum $2
                'max_fee' => 75.00, // Maximum $75
                'min_amount' => 20.00, // Minimum withdrawal $20
                'max_amount' => 5000.00, // Maximum withdrawal $5,000
                'is_active' => true,
                'sort_order' => 1,
            ],

            // ==========================================
            // CARD FEES
            // ==========================================
            [
                'code' => Fee::CODE_CARD_FUND,
                'name_ar' => 'رسوم شحن البطاقة',
                'name_en' => 'Card Funding Fee',
                'description' => 'رسوم شحن البطاقة الافتراضية من المحفظة',
                'type' => Fee::TYPE_CARD_FUND,
                'currency' => 'USD',
                'payment_method' => 'stripe',
                'fixed_amount' => 0.50, // $0.50 fixed
                'percentage' => 2.0, // 2%
                'min_fee' => 1.00, // Minimum $1
                'max_fee' => 100.00, // Maximum $100
                'min_amount' => 5.00, // Minimum fund $5
                'max_amount' => 2000.00, // Maximum fund $2,000
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => Fee::CODE_CARD_CREATION,
                'name_ar' => 'رسوم إنشاء البطاقة',
                'name_en' => 'Card Creation Fee',
                'description' => 'رسوم إنشاء بطاقة افتراضية جديدة (مرة واحدة)',
                'type' => Fee::TYPE_CARD_FUND,
                'currency' => 'USD',
                'payment_method' => 'stripe',
                'fixed_amount' => 3.00, // $3 one-time
                'percentage' => 0,
                'min_fee' => 3.00,
                'max_fee' => 3.00,
                'min_amount' => 0,
                'max_amount' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],

            // ==========================================
            // GOLD FEES
            // ==========================================
            [
                'code' => Fee::CODE_GOLD_BUY,
                'name_ar' => 'رسوم شراء الذهب',
                'name_en' => 'Gold Buy Fee',
                'description' => 'رسوم شراء الذهب من المنصة',
                'type' => Fee::TYPE_GOLD,
                'currency' => 'USD',
                'payment_method' => null,
                'fixed_amount' => 0,
                'percentage' => 1.0,
                'min_fee' => 0,
                'max_fee' => null,
                'min_amount' => 0,
                'max_amount' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => Fee::CODE_GOLD_SELL,
                'name_ar' => 'رسوم بيع الذهب',
                'name_en' => 'Gold Sell Fee',
                'description' => 'رسوم بيع الذهب من المنصة',
                'type' => Fee::TYPE_GOLD,
                'currency' => 'USD',
                'payment_method' => null,
                'fixed_amount' => 0,
                'percentage' => 0.5,
                'min_fee' => 0,
                'max_fee' => null,
                'min_amount' => 0,
                'max_amount' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($fees as $feeData) {
            Fee::updateOrCreate(
                ['code' => $feeData['code']],
                $feeData
            );
        }

        // Null-safe: $this->command is unset when run() is called directly
        // (e.g. from the installer) rather than via `artisan db:seed`.
        $this->command?->info('✅ Created/Updated ' . count($fees) . ' fee configurations');
        $this->command?->info('   Deposit fees: 1');
        $this->command?->info('   Withdrawal fees: 1');
        $this->command?->info('   Card fees: 2');
        $this->command?->info('   Gold fees: 2');
    }
}
