<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Core system settings (must be first)
        $this->call(SystemSettingsSeeder::class);

        // 2. KYC levels (required for user verification)
        $this->call(KycLevelSeeder::class);

        // 3. Admin accounts
        $this->call(AdminSeeder::class);

        // 4. Exchange rates (USD/SYP)
        $this->call(ExchangeRateSeeder::class);

        // 5. Platform fees
        $this->call(FeeSeeder::class);

        // 5. Integrations (each creates its own docs + templates)
        $this->call(CCPaymentSeeder::class);
        $this->call(StripeSeeder::class);
        $this->call(VirtualCardsSeeder::class);
        $this->call(MessagingSeeder::class);
        $this->call(PushNotificationsSeeder::class);
        $this->call(GoogleMapsSeeder::class);

        // 6. Service user (M2M — TaskSync Pro)
        $this->call(ServiceUserSeeder::class);

        $this->call(AgentSeeder::class);
        $this->call(MerchantSeeder::class);
        $this->call(GoldPriceSeeder::class);

        // 6. Admin panel: 3rd-party/security, notification channels, templates, SEO
        $this->call(SystemConfigSeeder::class);
    }
}
