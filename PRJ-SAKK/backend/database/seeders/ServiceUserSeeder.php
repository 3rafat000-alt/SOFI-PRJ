<?php

namespace Database\Seeders;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the TaskSync Pro service user on SAKK.
 *
 * This user is the merchant entity that TaskSync Pro authenticates as when
 * creating payment requests via the SAKK API. Payment requests created
 * under this user collect money into its SAKK wallet.
 *
 * The user is looked up by email in ExternalAppToken middleware
 * (config: services.service_user_email).
 *
 * Auto-creates USD + SYP wallets via User::boot() created hook.
 */
class ServiceUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'tasksync@sakk.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'TaskSync',
                'last_name' => 'Pro',
                'phone' => '+963900000001',
                'password' => Hash::make(Str::random(32)),
                'status' => UserStatus::ACTIVE,
                'kyc_status' => KycStatus::VERIFIED,
                'kyc_level' => 2,
                'is_active' => true,
                'language' => 'ar',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'kyc_verified_at' => now(),
                'referral_code' => 'TASKSYNC',
                'pin_code' => Hash::make('000000'),
            ]
        );

        // Service user is NOT an admin — it's a merchant that receives payments.
        // is_admin defaults to false.

        if ($this->command) {
            $this->command->info('✅ TaskSync Pro service user ready: tasksync@sakk.com');
            $this->command->info('   User ID: ' . $user->id);
            $this->command->info('   Wallets: USD + SYP (auto-created, zero balance)');
        }
    }
}
