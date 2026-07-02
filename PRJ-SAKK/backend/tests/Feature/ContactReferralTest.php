<?php

use App\Models\ReferralReward;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuthService;
use App\Enums\KycStatus;
use Laravel\Sanctum\Sanctum;

/**
 * Contacts matching + referral reward program.
 */

it('matches phone contacts against registered users', function () {
    $me = User::factory()->create(['phone' => '+963933000000']);
    $friend = User::factory()->create(['phone' => '+963944222333', 'first_name' => 'سارة', 'last_name' => 'علي']);
    Sanctum::actingAs($me);

    $response = $this->postJson('/api/v1/contacts/match', [
        'phones' => ['00963944222333', '+10000000000', '0599123456'],
    ])->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['account_number'])->toBe('SK' . str_pad((string) $friend->id, 8, '0', STR_PAD_LEFT));
});

it('returns referral info with configurable reward', function () {
    SystemSetting::set('referral_bonus_referrer', 1, 'decimal');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/referral/info')
        ->assertStatus(200)
        ->assertJsonPath('data.referral_code', $user->referral_code)
        ->assertJsonPath('data.reward_amount', 1);
});

it('grants the referrer a reward when the referred user completes KYC', function () {
    SystemSetting::set('referral_bonus_referrer', 1, 'decimal');
    $referrer = User::factory()->create();
    $before = (float) $referrer->wallets()->where('currency', 'USD')->first()->balance;

    // Register a referred user with the referrer's code
    $referred = app(AuthService::class)->register([
        'first_name' => 'مدعو',
        'last_name' => 'جديد',
        'email' => 'referred_'.uniqid().'@test.com',
        'password' => 'password123',
        'referral_code' => $referrer->referral_code,
    ]);

    expect($referred->referred_by)->toBe($referrer->id);

    // Create a $100 deposit so the referred user qualifies for the reward
    $referredWallet = $referred->wallets()->where('currency', 'USD')->first();
    $referredWallet->credit(100);
    \App\Models\Transaction::create([
        'user_id' => $referred->id,
        'wallet_id' => $referredWallet->id,
        'type' => \App\Enums\TransactionType::DEPOSIT,
        'category' => \App\Enums\TransactionCategory::WALLET,
        'currency' => 'USD',
        'amount' => 100,
        'fee' => 0,
        'net_amount' => 100,
        'balance_before' => 0,
        'balance_after' => 100,
        'status' => \App\Enums\TransactionStatus::COMPLETED,
        'title' => 'إيداع',
        'completed_at' => now(),
    ]);

    // Verify KYC -> triggers reward
    $referred->forceFill(['kyc_status' => KycStatus::VERIFIED, 'kyc_verified_at' => now()])->save();

    $after = (float) $referrer->wallets()->where('currency', 'USD')->first()->fresh()->balance;
    expect($after - $before)->toBe(1.0);
    expect(ReferralReward::where('referred_id', $referred->id)->where('status', 'credited')->exists())->toBeTrue();

    // Idempotent
    $referred->forceFill(['kyc_status' => KycStatus::VERIFIED])->save();
    $again = (float) $referrer->wallets()->where('currency', 'USD')->first()->fresh()->balance;
    expect($again)->toBe($after);
});
