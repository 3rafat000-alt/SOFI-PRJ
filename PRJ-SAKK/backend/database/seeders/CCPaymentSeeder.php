<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Seeder;

/**
 * Seeds the CCPayment integration, docs, and templates.
 */
class CCPaymentSeeder extends Seeder
{
    use SeedsIntegrationContent;

    public function run(): void
    {
        $integration = Integration::withTrashed()->updateOrCreate(
            ['key' => 'ccpayment'],
            [
                'name' => 'CCPayment',
                'name_ar' => 'سي سي بايمنت',
                'description' => 'CCPayment crypto payment gateway for cryptocurrency transactions.',
                'description_ar' => 'بوابة دفع سي سي بايمنت للمعاملات بالعملات الرقمية.',
                'icon' => 'currency_bitcoin',
                'category' => 'payment',
                // SEC C4: do not auto-activate the gateway when no real credential
                // is present — an "active" gateway with an empty secret is the state
                // that made forged webhooks possible.
                'is_active' => env('CCPAYMENT_APP_SECRET', '') !== '',
                'is_visible' => true,
                'config' => [
                    'supported_cryptos' => ['BTC', 'ETH', 'USDT', 'USDC'],
                    'auto_convert' => true,
                    'settlement_currency' => 'USD',
                    'min_amount' => 10,
                    'max_amount' => 100000,
                ],
                'credentials' => [
                    // 🔒 SEC-001: نُقلت الأسرار إلى env — اضبط CCPAYMENT_* في .env (وألغِ المفتاح المسرَّب!)
                    'app_id' => env('CCPAYMENT_APP_ID', ''),
                    'app_secret' => env('CCPAYMENT_APP_SECRET', ''),
                ],
                'settings' => [
                    'environment' => 'sandbox',
                    'base_url' => 'https://ccpayment.com/ccpayment/v2',
                    'timeout' => 30,
                'webhook_deposit' => config('services.ccpayment.webhook_base', env('APP_URL', 'http://localhost:8000')) . '/webhooks/ccpayment/deposit',
                'webhook_withdraw' => config('services.ccpayment.webhook_base', env('APP_URL', 'http://localhost:8000')) . '/webhooks/ccpayment/withdraw',
                    'ip_whitelist' => '127.0.0.1,54.150.123.157',
                    // SEC C4: debug_mode=true SKIPS the webhook IP check entirely.
                    // Default OFF so the gateway never ships with a disabled control.
                    'debug_mode' => false,
                ],
                'deleted_at' => null,
            ]
        );
    }
}
