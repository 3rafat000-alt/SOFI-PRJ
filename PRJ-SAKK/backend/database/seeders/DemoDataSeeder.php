<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\VirtualCard;
use App\Models\SavingsGoal;
use App\Models\GoldWallet;
use App\Models\GoldTransaction;
use App\Models\PaymentRequest;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────
        // Use the existing "أحمد محمد" (user_id=2) as demo user
        // ─────────────────────────────────────────────────────────
        $user = User::find(2);
        if (!$user) {
            $this->command->warn('User 2 not found — skipping DemoDataSeeder');
            return;
        }

        // Upgrade user to Standard KYC (level 1)
        $user->update([
            'kyc_status' => 'verified',
            'kyc_level' => 1,
            'kyc_verified_at' => Carbon::now()->subDays(30),
            'is_active' => true,
            'status' => 'active',
            'daily_limit' => 2500,
            'monthly_limit' => 10000,
            'preferred_currency' => 'USD',
        ]);

        // ─────────────────────────────────────────────────────────
        // WALLETS — update with real balances
        // ─────────────────────────────────────────────────────────
        $usdWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
        $sypWallet = Wallet::where('user_id', $user->id)->where('currency', 'SYP')->first();

        if (!$usdWallet) {
            $usdWallet = Wallet::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'currency' => 'USD',
                'balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'is_default' => true,
            ]);
        }
        if (!$sypWallet) {
            $sypWallet = Wallet::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'currency' => 'SYP',
                'balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'is_default' => false,
            ]);
        }

        $now = Carbon::now();

        // ─────────────────────────────────────────────────────────
        // TRANSACTIONS — 25 realistic entries
        // Uses valid enum values from App\Enums:
        //   TransactionType: deposit, withdrawal, card_load, card_unload, card_payment,
        //                    card_refund, fee, reward, adjustment, exchange, transfer_out, transfer_in
        //   TransactionCategory: wallet, card, crypto, exchange, p2p, fee, reward, adjustment, investment, savings
        //   TransactionStatus: pending, processing, completed, failed, cancelled, reversed, refunded
        // ─────────────────────────────────────────────────────────
        $txData = [
            // Deposits
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'deposit', 'category' => 'wallet', 'currency' => 'USD', 'amount' => 1500, 'fee' => 0, 'net_amount' => 1500, 'status' => 'completed', 'title' => 'إيداع عبر CCPayment', 'description' => 'إيداع USDT عبر TRC20', 'completed_at' => $now->copy()->subDays(45), 'created_at' => $now->copy()->subDays(45)],
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'deposit', 'category' => 'wallet', 'currency' => 'USD', 'amount' => 750, 'fee' => 0, 'net_amount' => 750, 'status' => 'completed', 'title' => 'إيداع عبر تحويل بنكي', 'description' => 'تحويل من البنك العقاري', 'completed_at' => $now->copy()->subDays(20), 'created_at' => $now->copy()->subDays(20)],
            ['user_id' => $user->id, 'wallet_id' => $sypWallet->id, 'type' => 'deposit', 'category' => 'wallet', 'currency' => 'SYP', 'amount' => 2000000, 'fee' => 0, 'net_amount' => 2000000, 'status' => 'completed', 'title' => 'إيداع نقدي عبر وكيل', 'description' => 'صرافة الشام المركزية', 'completed_at' => $now->copy()->subDays(15), 'created_at' => $now->copy()->subDays(15)],

            // Transfers sent
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'transfer_out', 'category' => 'p2p', 'currency' => 'USD', 'amount' => 200, 'fee' => 1, 'net_amount' => 199, 'status' => 'completed', 'title' => 'تحويل إلى سارة علي', 'description' => 'فاتورة المطعم', 'recipient_id' => 3, 'completed_at' => $now->copy()->subDays(14), 'created_at' => $now->copy()->subDays(14)],
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'transfer_out', 'category' => 'p2p', 'currency' => 'USD', 'amount' => 50, 'fee' => 0.5, 'net_amount' => 49.5, 'status' => 'completed', 'title' => 'تحويل إلى سارة علي', 'description' => 'هدية عيد ميلاد', 'recipient_id' => 3, 'completed_at' => $now->copy()->subDays(7), 'created_at' => $now->copy()->subDays(7)],

            // Transfer received
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'transfer_in', 'category' => 'p2p', 'currency' => 'USD', 'amount' => 100, 'fee' => 0, 'net_amount' => 100, 'status' => 'completed', 'title' => 'استلام من سارة علي', 'description' => 'تسديد فاتورة', 'completed_at' => $now->copy()->subDays(10), 'created_at' => $now->copy()->subDays(10)],

            // Card funding
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'card_load', 'category' => 'card', 'currency' => 'USD', 'amount' => 300, 'fee' => 2, 'net_amount' => 298, 'status' => 'completed', 'title' => 'شحن البطاقة الافتراضية', 'description' => 'شحن SAKK Gold', 'completed_at' => $now->copy()->subDays(12), 'created_at' => $now->copy()->subDays(12)],
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'card_load', 'category' => 'card', 'currency' => 'USD', 'amount' => 150, 'fee' => 1.5, 'net_amount' => 148.5, 'status' => 'completed', 'title' => 'شحن البطاقة الافتراضية', 'description' => 'شحن SAKK Blue', 'completed_at' => $now->copy()->subDays(5), 'created_at' => $now->copy()->subDays(5)],

            // Card payments
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'card_payment', 'category' => 'card', 'currency' => 'USD', 'amount' => 45.99, 'fee' => 0, 'net_amount' => 45.99, 'status' => 'completed', 'title' => 'Netflix اشتراك', 'description' => 'الاشتراك الشهري', 'merchant_name' => 'Netflix', 'completed_at' => $now->copy()->subDays(13), 'created_at' => $now->copy()->subDays(13)],
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'card_payment', 'category' => 'card', 'currency' => 'USD', 'amount' => 12.99, 'fee' => 0, 'net_amount' => 12.99, 'status' => 'completed', 'title' => 'Spotify اشتراك', 'description' => 'الاشتراك الشهري', 'merchant_name' => 'Spotify', 'completed_at' => $now->copy()->subDays(10), 'created_at' => $now->copy()->subDays(10)],
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'card_payment', 'category' => 'card', 'currency' => 'USD', 'amount' => 89.50, 'fee' => 0, 'net_amount' => 89.50, 'status' => 'completed', 'title' => 'Google اشتراك Drive', 'description' => 'مساحة تخزين 2TB', 'merchant_name' => 'Google LLC', 'completed_at' => $now->copy()->subDays(8), 'created_at' => $now->copy()->subDays(8)],

            // Withdrawals
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'withdrawal', 'category' => 'wallet', 'currency' => 'USD', 'amount' => 500, 'fee' => 7.5, 'net_amount' => 492.5, 'status' => 'completed', 'title' => 'سحب إلى حساب بنكي', 'description' => 'سحب للحساب العقاري SY194700...', 'completed_at' => $now->copy()->subDays(6), 'created_at' => $now->copy()->subDays(6)],
            ['user_id' => $user->id, 'wallet_id' => $sypWallet->id, 'type' => 'withdrawal', 'category' => 'wallet', 'currency' => 'SYP', 'amount' => 500000, 'fee' => 0, 'net_amount' => 500000, 'status' => 'completed', 'title' => 'سحب نقدي من وكيل', 'description' => 'صرافة المزة — 500,000 ل.س', 'completed_at' => $now->copy()->subDays(4), 'created_at' => $now->copy()->subDays(4)],

            // Gold purchase (investment)
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'exchange', 'category' => 'investment', 'currency' => 'USD', 'amount' => 895, 'fee' => 0, 'net_amount' => 895, 'status' => 'completed', 'title' => 'شراء ذهب 10 غرام', 'description' => 'شراء 10 غرام عيار 24', 'completed_at' => $now->copy()->subDays(11), 'created_at' => $now->copy()->subDays(11)],

            // Rewards
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'reward', 'category' => 'reward', 'currency' => 'USD', 'amount' => 5.75, 'fee' => 0, 'net_amount' => 5.75, 'status' => 'completed', 'title' => 'مكافأة كاش باك', 'description' => 'كاش باك %5 من مشتريات Netflix', 'completed_at' => $now->copy()->subDays(13), 'created_at' => $now->copy()->subDays(13)],
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'reward', 'category' => 'reward', 'currency' => 'USD', 'amount' => 2.00, 'fee' => 0, 'net_amount' => 2.00, 'status' => 'completed', 'title' => 'مكافأة إحالة', 'description' => 'كاش باك إحالة صديق', 'completed_at' => $now->copy()->subDays(2), 'created_at' => $now->copy()->subDays(2)],
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'reward', 'category' => 'reward', 'currency' => 'USD', 'amount' => 10, 'fee' => 0, 'net_amount' => 10, 'status' => 'completed', 'title' => 'مكافأة إحالة — سارة انضمت', 'description' => 'مكافأة 10$ لدعوة صديق', 'completed_at' => $now->copy()->subDays(30), 'created_at' => $now->copy()->subDays(30)],

            // Pending transfer
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'transfer_out', 'category' => 'p2p', 'currency' => 'USD', 'amount' => 75, 'fee' => 0.75, 'net_amount' => 74.25, 'status' => 'pending', 'title' => 'تحويل إلى خالد', 'description' => 'الرقم غير مسجل في SAKK', 'created_at' => $now->copy()->subHours(2)],

            // Failed transaction
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'card_payment', 'category' => 'card', 'currency' => 'USD', 'amount' => 299.99, 'fee' => 0, 'net_amount' => 299.99, 'status' => 'failed', 'title' => 'فشلت عملية الشراء', 'description' => 'رصيد غير كافٍ', 'merchant_name' => 'Amazon', 'failure_reason' => 'insufficient_funds', 'created_at' => $now->copy()->subDays(1)],

            // Currency exchange
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'exchange', 'category' => 'exchange', 'currency' => 'USD', 'amount' => 100, 'fee' => 1, 'net_amount' => 99, 'status' => 'completed', 'title' => 'صرف USD → SYP', 'description' => 'صرف 100$ → 1,300,000 ل.س', 'exchange_rate' => 13000, 'completed_at' => $now->copy()->subDays(3), 'created_at' => $now->copy()->subDays(3)],

            // Savings deposit
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'exchange', 'category' => 'savings', 'currency' => 'USD', 'amount' => 100, 'fee' => 0, 'net_amount' => 100, 'status' => 'completed', 'title' => 'إيداع في هدف الادخار', 'description' => 'إيداع شهري — صندوق الطوارئ', 'completed_at' => $now->copy()->subDays(9), 'created_at' => $now->copy()->subDays(9)],

            // Fee
            ['user_id' => $user->id, 'wallet_id' => $usdWallet->id, 'type' => 'fee', 'category' => 'fee', 'currency' => 'USD', 'amount' => 3, 'fee' => 0, 'net_amount' => 3, 'status' => 'completed', 'title' => 'رسوم إنشاء البطاقة', 'description' => 'رسوم إصدار SAKK Gold (مرة واحدة)', 'completed_at' => $now->copy()->subDays(30), 'created_at' => $now->copy()->subDays(30)],
        ];

        $totalUsdInflow = 0;
        $totalUsdOutflow = 0;
        $totalSypInflow = 0;
        $totalSypOutflow = 0;

        foreach ($txData as $data) {
            $data['uuid'] = Str::uuid();
            $data['balance_before'] = 0;
            $data['balance_after'] = 0;
            $data['updated_at'] = $data['created_at'];
            if (!isset($data['completed_at'])) {
                $data['processed_at'] = $data['created_at'];
            } else {
                $data['processed_at'] = $data['completed_at'];
            }
            if (!isset($data['reference'])) {
                $data['reference'] = strtoupper(Str::random(14));
            }
            Transaction::create($data);

            // Track flows for balance computation (only completed)
            if ($data['status'] !== 'completed') continue;
            $wallet = $data['wallet_id'];
            $amount = $data['net_amount'];
            // Credit types (money in)
            if (in_array($data['type'], ['deposit', 'transfer_in', 'reward'])) {
                if ($wallet === $usdWallet->id) $totalUsdInflow += $amount;
                else $totalSypInflow += $amount;
            }
            // Debit types (money out)
            if (in_array($data['type'], ['withdrawal', 'transfer_out', 'card_load', 'card_payment', 'exchange', 'fee'])) {
                if ($wallet === $usdWallet->id) $totalUsdOutflow += $amount;
                else $totalSypOutflow += $amount;
            }
        }

        // ─────────────────────────────────────────────────────────
        // UPDATE WALLET BALANCES
        // ─────────────────────────────────────────────────────────
        $usdInitialBalance = 2500; // start with this much before tracking
        $sypInitialBalance = 5000000;

        $usdBalance = $usdInitialBalance + $totalUsdInflow - $totalUsdOutflow;
        $sypBalance = $sypInitialBalance + $totalSypInflow - $totalSypOutflow;

        $usdWallet->update([
            'balance' => $usdBalance,
            'available_balance' => $usdBalance,
            'pending_balance' => 75, // the pending transfer
            'total_deposits' => 1500 + 750,
            'total_withdrawals' => 492.5,
            'total_sent' => 200 + 50 + 75, // including pending
            'total_received' => 100,
            'transaction_count' => collect($txData)->where('wallet_id', $usdWallet->id)->count(),
        ]);

        $sypWallet->update([
            'balance' => $sypBalance,
            'available_balance' => $sypBalance,
            'pending_balance' => 0,
            'total_deposits' => 1980000,
            'total_withdrawals' => 492500,
            'total_sent' => 0,
            'total_received' => 0,
            'transaction_count' => collect($txData)->where('wallet_id', $sypWallet->id)->count(),
        ]);

        // ─────────────────────────────────────────────────────────
        // VIRTUAL CARDS — 2 cards
        // ─────────────────────────────────────────────────────────
        $cards = [
            [
                'user_id' => $user->id,
                'wallet_id' => $usdWallet->id,
                'card_number' => '4000 1234 5678 9010',
                'card_number_masked' => '**** **** **** 9010',
                'cvv' => '123',
                'expiry_month' => '12',
                'expiry_year' => '28',
                'cardholder_name' => 'AHMAD MOHAMMAD',
                'brand' => 'visa',
                'balance' => 250,
                'spending_limit' => 5000,
                'daily_limit' => 1000,
                'monthly_limit' => 10000,
                'per_transaction_limit' => 500,
                'status' => 'active',
                'is_active' => true,
                'activated_at' => $now->copy()->subDays(30),
                'expires_at' => $now->copy()->addYears(2),
                'color' => '#B58A3C',
                'nickname' => 'SAKK Gold',
                'card_type' => 'virtual',
            ],
            [
                'user_id' => $user->id,
                'wallet_id' => $usdWallet->id,
                'card_number' => '4000 5678 1234 9020',
                'card_number_masked' => '**** **** **** 9020',
                'cvv' => '456',
                'expiry_month' => '08',
                'expiry_year' => '27',
                'cardholder_name' => 'AHMAD MOHAMMAD',
                'brand' => 'mastercard',
                'balance' => 120,
                'spending_limit' => 2000,
                'daily_limit' => 500,
                'monthly_limit' => 5000,
                'per_transaction_limit' => 300,
                'status' => 'active',
                'is_active' => true,
                'activated_at' => $now->copy()->subDays(15),
                'expires_at' => $now->copy()->addYears(1),
                'color' => '#1E40AF',
                'nickname' => 'SAKK Blue',
                'card_type' => 'virtual',
            ],
        ];

        foreach ($cards as $cardData) {
            $cardData['uuid'] = Str::uuid();
            $cardData['bin'] = substr(str_replace(' ', '', $cardData['card_number']), 0, 6);
            VirtualCard::updateOrCreate(
                ['card_number_masked' => $cardData['card_number_masked'], 'user_id' => $user->id],
                $cardData
            );
        }

        // ─────────────────────────────────────────────────────────
        // SAVINGS GOALS — 3 goals
        // ─────────────────────────────────────────────────────────
        $savingsGoals = [
            [
                'user_id' => $user->id,
                'name' => 'صندوق الطوارئ',
                'target_amount' => 5000,
                'saved_amount' => 1200,
                'currency' => 'USD',
                'status' => 'active',
                'icon' => 'shield-exclamation',
                'color' => '#B58A3C',
            ],
            [
                'user_id' => $user->id,
                'name' => 'عمرة 2027',
                'target_amount' => 3000,
                'saved_amount' => 800,
                'currency' => 'USD',
                'status' => 'active',
                'icon' => 'plane',
                'color' => '#6E1B2D',
            ],
            [
                'user_id' => $user->id,
                'name' => 'لابتوب جديد',
                'target_amount' => 1500,
                'saved_amount' => 1500,
                'currency' => 'USD',
                'status' => 'completed',
                'icon' => 'device-laptop',
                'color' => '#1E40AF',
                'completed_at' => $now->copy()->subDays(5),
            ],
        ];

        foreach ($savingsGoals as $goal) {
            $goal['uuid'] = Str::uuid();
            SavingsGoal::updateOrCreate(
                ['user_id' => $user->id, 'name' => $goal['name']],
                $goal
            );
        }

        // ─────────────────────────────────────────────────────────
        // GOLD WALLET + TRANSACTIONS
        // ─────────────────────────────────────────────────────────
        $goldWallet = GoldWallet::updateOrCreate(
            ['user_id' => $user->id],
            [
                'balance_grams' => 10,
                'total_bought_grams' => 15,
                'total_sold_grams' => 5,
                'total_invested_usd' => 1342.5,
                'current_value_usd' => 895,
                'is_active' => true,
            ]
        );

        GoldTransaction::create([
            'user_id' => $user->id,
            'gold_wallet_id' => $goldWallet->id,
            'type' => 'buy',
            'karat' => '24',
            'grams' => 15,
            'price_per_gram_usd' => 89.5,
            'total_usd' => 1342.5,
            'status' => 'completed',
            'created_at' => $now->copy()->subDays(11),
        ]);

        GoldTransaction::create([
            'user_id' => $user->id,
            'gold_wallet_id' => $goldWallet->id,
            'type' => 'sell',
            'karat' => '24',
            'grams' => 5,
            'price_per_gram_usd' => 88.7,
            'total_usd' => 443.5,
            'status' => 'completed',
            'created_at' => $now->copy()->subDays(2),
        ]);

        // ─────────────────────────────────────────────────────────
        // PAYMENT REQUESTS
        // ─────────────────────────────────────────────────────────
        PaymentRequest::create([
            'uuid' => Str::uuid(),
            'user_id' => 3, // سارة
            'currency' => 'USD',
            'amount' => 35,
            'note' => 'نصيبك بفاتورة الكهرباء',
            'status' => 'pending',
            'payer_id' => $user->id,
            'created_at' => $now->copy()->subDays(1),
            'expires_at' => $now->copy()->addDays(6),
        ]);

        PaymentRequest::create([
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'currency' => 'USD',
            'amount' => 150,
            'note' => 'قسط الشهر',
            'status' => 'paid',
            'payer_id' => 3,
            'paid_at' => $now->copy()->subDays(20),
            'created_at' => $now->copy()->subDays(25),
            'expires_at' => $now->copy()->subDays(10),
        ]);

        // ─────────────────────────────────────────────────────────
        // NOTIFICATIONS
        // ─────────────────────────────────────────────────────────
        $notifications = [
            ['channel' => 'in_app', 'title' => '💰 استلمت 100$ من سارة', 'body' => 'تم استلام 100$ دولار من سارة علي', 'is_read' => true, 'status' => 'read', 'created_at' => $now->copy()->subDays(10)],
            ['channel' => 'in_app', 'title' => '🎉 كاش باك 5.75$', 'body' => 'حصلت على 5.75$ كاش باك من Netflix', 'is_read' => true, 'status' => 'read', 'created_at' => $now->copy()->subDays(13)],
            ['channel' => 'in_app', 'title' => '🎁 مكافأة إحالة 10$', 'body' => 'انضمت سارة علي بدعوتك — كسبت 10$', 'is_read' => true, 'status' => 'read', 'created_at' => $now->copy()->subDays(30)],
            ['channel' => 'in_app', 'title' => '✅ هدف الادخار تحقق', 'body' => 'أكملت هدف "لابتوب جديد" — أحسنت! 🎉', 'is_read' => false, 'status' => 'sent', 'created_at' => $now->copy()->subDays(5)],
            ['channel' => 'in_app', 'title' => '📉 الذهب: 88.2$ للغرام', 'body' => 'انخفاض طفيف في سعر الذهب — اغتنم الفرصة للشراء', 'is_read' => false, 'status' => 'sent', 'created_at' => $now->copy()->subDays(1)],
            ['channel' => 'in_app', 'title' => '📋 طلب استلام 35$ من سارة', 'body' => 'سارة علي تطلب منك 35$ — فاتورة الكهرباء', 'is_read' => false, 'status' => 'sent', 'created_at' => $now->copy()->subHours(6)],
            ['channel' => 'in_app', 'title' => '⚠️ فشل التحويل إلى خالد', 'body' => 'تعذر إتمام التحويل 75$ — رقم الهاتف غير مسجل', 'is_read' => false, 'status' => 'sent', 'created_at' => $now->copy()->subHours(2)],
        ];

        foreach ($notifications as $n) {
            $n['uuid'] = Str::uuid();
            $n['user_id'] = $user->id;
            $n['updated_at'] = $n['created_at'];
            UserNotification::create($n);
        }

        $this->command->info('✅ Demo data seeded for user: ' . $user->first_name . ' ' . $user->last_name);
        $this->command->info("   USD wallet: \${$usdBalance} | SYP wallet: {$sypBalance} ل.س");
    }
}
