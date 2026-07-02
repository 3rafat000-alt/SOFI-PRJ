<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Seeder;

/**
 * Seeds the Stripe Issuing integration — virtual Visa / Mastercard issuing.
 *
 * Credentials are managed from the admin panel
 * (النظام → الطرف الثالث والأمان) and read by StripeIssuingService.
 * Secrets default to env (STRIPE_*) and must never be committed.
 */
class StripeSeeder extends Seeder
{
    public function run(): void
    {
        Integration::withTrashed()->updateOrCreate(
            ['key' => 'stripe'],
            [
                'name' => 'Stripe Issuing',
                'name_ar' => 'ستريب — إصدار البطاقات',
                'description' => 'Stripe Issuing for virtual Visa / Mastercard cards.',
                'description_ar' => 'إصدار بطاقات فيزا وماستركارد الافتراضية عبر ستريب.',
                'icon' => 'credit_card',
                'category' => 'cards',
                // Off by default — turned on once the admin saves live keys.
                'is_active' => (bool) env('STRIPE_SECRET'),
                'is_visible' => true,
                'config' => [
                    'card_brands' => ['visa', 'mastercard'],
                    'card_type' => 'virtual',
                    'currency' => 'usd',
                ],
                'credentials' => [
                    'secret' => env('STRIPE_SECRET', ''),
                    'publishable_key' => env('STRIPE_KEY', ''),
                    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
                    'issuing_webhook_secret' => env('STRIPE_ISSUING_WEBHOOK_SECRET', ''),
                ],
                'settings' => [
                    'test_mode' => env('STRIPE_TEST_MODE', true),
                    'webhook_issuing' => env('APP_URL', 'http://localhost:8000') . '/api/webhooks/stripe/issuing',
                ],
                'deleted_at' => null,
            ]
        );
    }
}
