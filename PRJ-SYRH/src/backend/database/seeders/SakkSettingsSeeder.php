<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SakkSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate(
            ['key' => 'sakk_merchant_id'],
            ['value' => '', 'group' => 'payment', 'description' => 'SAKK merchant ID for platform owner', 'is_public' => false]
        );
        Setting::firstOrCreate(
            ['key' => 'sakk_api_key'],
            ['value' => '', 'group' => 'payment', 'description' => 'SAKK API key', 'is_public' => false]
        );
        Setting::firstOrCreate(
            ['key' => 'sakk_webhook_secret'],
            ['value' => '', 'group' => 'payment', 'description' => 'SAKK webhook signing secret', 'is_public' => false]
        );
        Setting::firstOrCreate(
            ['key' => 'sakk_sandbox'],
            ['value' => 'true', 'group' => 'payment', 'description' => 'Use SAKK sandbox mode', 'is_public' => false]
        );
    }
}
