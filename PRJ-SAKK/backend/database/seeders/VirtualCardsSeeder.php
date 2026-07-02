<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Seeder;

/**
 * Seeds the Virtual Cards integration, docs, and templates.
 */
class VirtualCardsSeeder extends Seeder
{
    use SeedsIntegrationContent;

    public function run(): void
    {
        $integration = Integration::firstOrCreate(['key' => 'virtual_cards'], [
            'key' => 'virtual_cards',
            'name' => 'Virtual Cards',
            'name_ar' => 'البطاقات الافتراضية',
            'description' => 'Virtual card issuance and management system.',
            'description_ar' => 'نظام إصدار وإدارة البطاقات الافتراضية.',
            'icon' => 'credit_card',
            'category' => 'cards',
            'is_active' => false,
            'is_visible' => true,
            'config' => [
                'card_types' => ['visa', 'mastercard'],
                'issuance_fee' => 5,
                'monthly_fee' => 2,
                'transaction_fee' => 0.5,
                'max_cards_per_user' => 5,
                'default_currency' => 'USD',
                'require_kyc_level' => 2,
            ],
            'credentials' => [
                'stripe_key' => '',
                'stripe_secret' => '',
                'stripe_webhook_secret' => '',
            ],
            'settings' => [
                'provider' => 'stripe',
                'environment' => 'sandbox',
                'timeout' => 30,
            ],
        ]);

    }
}
