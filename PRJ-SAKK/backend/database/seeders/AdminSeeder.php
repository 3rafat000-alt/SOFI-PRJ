<?php

namespace Database\Seeders;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds admin and test user accounts for SAKK Wallet.
 * 
 * Accounts:
 * - 1 Admin account
 * - 2 Regular user accounts with wallets (USD only)
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // Admin Account
        // ==========================================
        $admin = User::firstOrCreate(
            ['email' => 'admin@sakk.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'مدير',
                'last_name' => 'النظام',
                'phone' => '+963912345678',
                'password' => Hash::make('password'),
                'status' => UserStatus::ACTIVE,
                'kyc_status' => KycStatus::VERIFIED,
                'kyc_level' => 2, // VERIFIED (max defined level); level 3 is undefined in config/kyc.php -> would fall back to level-0 perms
                'is_active' => true,
                'language' => 'ar',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'kyc_verified_at' => now(),
                'referral_code' => 'ADMIN001',
                'pin_code' => Hash::make('123456'),
            ]
        );

        // is_admin is a guarded attribute (not mass-assignable); grant it explicitly.
        $admin->forceFill(['is_admin' => true])->save();

        // ==========================================
        // Test User 1 (Verified - Level 2)
        // ==========================================
        $user1 = User::firstOrCreate(
            ['email' => 'ahmad@test.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'phone' => '+963933111222',
                'password' => Hash::make('password'),
                'status' => UserStatus::ACTIVE,
                'kyc_status' => KycStatus::VERIFIED,
                'kyc_level' => 2,
                'is_admin' => false,
                'is_active' => true,
                'language' => 'ar',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'kyc_verified_at' => now(),
                'referral_code' => 'AHMAD001',
                'pin_code' => Hash::make('123456'),
            ]
        );

        // Create wallets for User 1 (500 USD)
        $this->createWalletsForUser($user1, 500.00);

        // ==========================================
        // Test User 2 (Basic - Level 1)
        // ==========================================
        $user2 = User::firstOrCreate(
            ['email' => 'sara@test.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'سارة',
                'last_name' => 'علي',
                'phone' => '+963944222333',
                'password' => Hash::make('password'),
                'status' => UserStatus::ACTIVE,
                'kyc_status' => KycStatus::PENDING,
                'kyc_level' => 1,
                'is_admin' => false,
                'is_active' => true,
                'language' => 'ar',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'referral_code' => 'SARA0001',
                'pin_code' => Hash::make('123456'),
            ]
        );

        // Create wallets for User 2 (100 USD)
        $this->createWalletsForUser($user2, 100.00);

        $this->command->info('✅ Created accounts:');
        $this->command->info('   Admin: admin@sakk.com / password');
        $this->command->info('   User 1: ahmad@test.com / password (Level 2, $500 USD)');
        $this->command->info('   User 2: sara@test.com / password (Level 1, $100 USD)');
        $this->command->info('   PIN for all: 123456');
    }

    /**
     * Create wallets for a user (USD + SYP)
     */
    private function createWalletsForUser(User $user, float $usdBalance): void
    {
        // USD Wallet
        Wallet::firstOrCreate(
            ['user_id' => $user->id, 'currency' => 'USD'],
            [
                'balance' => $usdBalance,
                'available_balance' => $usdBalance,
                'pending_balance' => 0,
                'is_active' => true,
            ]
        );
        // SYP Wallet (zero balance initially)
        Wallet::firstOrCreate(
            ['user_id' => $user->id, 'currency' => 'SYP'],
            [
                'balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'is_active' => true,
            ]
        );
    }
}
