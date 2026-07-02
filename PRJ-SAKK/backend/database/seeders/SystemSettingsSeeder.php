<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the default system settings.
 */
class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Maintenance Mode',
                'description' => 'Enable/disable maintenance mode',
            ],
            [
                'key' => 'registration_open',
                'value' => true,
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Registration Open',
                'description' => 'Allow new user registrations',
            ],
            [
                'key' => 'min_deposit',
                'value' => 1,
                'type' => 'decimal',
                'group' => 'limits',
                'label' => 'Minimum Deposit',
                'description' => 'Minimum deposit amount in USD',
            ],
            [
                'key' => 'max_deposit',
                'value' => 100000,
                'type' => 'decimal',
                'group' => 'limits',
                'label' => 'Maximum Deposit',
                'description' => 'Maximum deposit amount in USD',
            ],
            [
                'key' => 'min_withdrawal',
                'value' => 1,
                'type' => 'decimal',
                'group' => 'limits',
                'label' => 'Minimum Withdrawal',
                'description' => 'Minimum withdrawal amount in USD',
            ],
            [
                'key' => 'max_withdrawal',
                'value' => 50000,
                'type' => 'decimal',
                'group' => 'limits',
                'label' => 'Maximum Withdrawal',
                'description' => 'Maximum withdrawal amount in USD',
            ],
            [
                'key' => 'withdrawal_fee_percent',
                'value' => 1.0,
                'type' => 'decimal',
                'group' => 'fees',
                'label' => 'Withdrawal Fee %',
                'description' => 'Withdrawal fee percentage',
            ],
            [
                'key' => 'default_currency',
                'value' => 'USD',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Default Currency',
                'description' => 'Default currency for the platform',
            ],
            [
                'key' => 'supported_currencies',
                'value' => json_encode(['USD', 'SYP']),
                'type' => 'json',
                'group' => 'general',
                'label' => 'Supported Currencies',
                'description' => 'List of supported currencies',
            ],
            [
                'key' => 'referral_bonus_referrer',
                'value' => 5,
                'type' => 'decimal',
                'group' => 'referral',
                'label' => 'Referrer Bonus',
                'description' => 'Bonus amount (USD) credited to the referrer when an invited user completes KYC',
            ],
            [
                'key' => 'referral_bonus_referred',
                'value' => 5,
                'type' => 'decimal',
                'group' => 'referral',
                'label' => 'Referred Bonus',
                'description' => 'Bonus amount for referred user',
            ],
            [
                'key' => 'referral_enabled',
                'value' => true,
                'type' => 'boolean',
                'group' => 'referral',
                'label' => 'Referral Enabled',
                'description' => 'Enable referral system',
            ],
            [
                'key' => 'limit_daily_withdrawal',
                'value' => 5000,
                'type' => 'decimal',
                'group' => 'limits',
                'label' => 'Daily Withdrawal Limit',
                'description' => 'Maximum daily withdrawal limit',
            ],
            [
                'key' => 'limit_monthly_withdrawal',
                'value' => 50000,
                'type' => 'decimal',
                'group' => 'limits',
                'label' => 'Monthly Withdrawal Limit',
                'description' => 'Maximum monthly withdrawal limit',
            ],
            [
                'key' => 'limit_card_daily',
                'value' => 5000,
                'type' => 'decimal',
                'group' => 'limits',
                'label' => 'Card Daily Limit',
                'description' => 'Maximum daily card spending limit',
            ],
            [
                'key' => 'limit_card_monthly',
                'value' => 20000,
                'type' => 'decimal',
                'group' => 'limits',
                'label' => 'Card Monthly Limit',
                'description' => 'Maximum monthly card spending limit',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
