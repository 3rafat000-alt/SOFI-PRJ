<?php

namespace Database\Seeders;

use App\Models\KycLevel;
use Illuminate\Database\Seeder;

/**
 * Seeds the 3-level KYC system (Unverified, Standard, Verified) from config/kyc.php.
 *
 * Idempotent: upserts the configured levels and deactivates any legacy levels
 * not part of the current system.
 */
class KycLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = config('kyc.levels', []);
        $keepLevels = [];

        foreach ($levels as $def) {
            $keepLevels[] = $def['level'];

            $usd = $def['limits']['USD'];

            KycLevel::updateOrCreate(
                ['level' => $def['level']],
                [
                    'key' => $def['key'],
                    'name' => $def['name'],
                    'name_ar' => $def['name_ar'],
                    'description' => $def['description'],
                    'description_ar' => $def['description_ar'],
                    'requirements' => $def['requirements'],
                    'limits' => $def['limits'],
                    'balance_limit' => $def['balance_limit'] ?? null,
                    'cards_limit' => $def['cards_limit'] ?? 0,
                    // Legacy decimal columns mirror the USD limits for backward compatibility.
                    'daily_limit' => $usd['daily'],
                    'monthly_limit' => $usd['monthly'],
                    'single_transaction_limit' => $usd['single'],
                    'withdrawal_limit' => $usd['single'],
                    'can_transfer' => $def['can_transfer'],
                    'can_withdraw' => $def['can_withdraw'],
                    'can_create_card' => $def['can_create_card'],
                    'is_active' => true,
                ]
            );
        }

        // Deactivate any legacy levels not part of the current 3-level system.
        KycLevel::whereNotIn('level', $keepLevels)->update(['is_active' => false]);
    }
}
