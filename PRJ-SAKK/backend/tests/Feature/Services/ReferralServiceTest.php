<?php

use App\Enums\KycStatus;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\ReferralReward;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ReferralService;

beforeEach(function () {
    $this->service = app(ReferralService::class);
});

it('returns default reward amount', function () {
    $amount = $this->service->rewardAmount();
    expect($amount)->toBe(ReferralService::DEFAULT_REWARD);
});

it('returns configured reward amount from settings', function () {
    SystemSetting::set(ReferralService::SETTING_KEY, 10.0);

    $amount = $this->service->rewardAmount();
    expect($amount)->toBe(10.0);
});

it('attaches referrer when valid referral code provided', function () {
    $referrer = User::factory()->create(['referral_code' => 'ABCD1234']);
    $user = User::factory()->create();

    $this->service->attachReferrer($user, 'ABCD1234');

    expect($user->fresh()->referred_by)->toBe($referrer->id);
});

it('does not attach referrer when code is empty', function () {
    $user = User::factory()->create();

    $this->service->attachReferrer($user, '');

    expect($user->fresh()->referred_by)->toBeNull();
});

it('does not attach referrer when code is whitespace', function () {
    $user = User::factory()->create();

    $this->service->attachReferrer($user, '   ');

    expect($user->fresh()->referred_by)->toBeNull();
});

it('ignores self-referral', function () {
    $user = User::factory()->create(['referral_code' => 'SELFCODE']);

    $this->service->attachReferrer($user, 'SELFCODE');

    expect($user->fresh()->referred_by)->toBeNull();
});

it('handles at-sign referral code prefix', function () {
    $referrer = User::factory()->create(['referral_code' => 'CODE1234']);
    $user = User::factory()->create();

    $this->service->attachReferrer($user, '@CODE1234');

    expect($user->fresh()->referred_by)->toBe($referrer->id);
});

it('handles hash referral code prefix', function () {
    $referrer = User::factory()->create(['referral_code' => 'HASHCODE']);
    $user = User::factory()->create();

    $this->service->attachReferrer($user, '#HASHCODE');

    expect($user->fresh()->referred_by)->toBe($referrer->id);
});

it('is case insensitive for referral codes', function () {
    $referrer = User::factory()->create(['referral_code' => 'UPPER123']);
    $user = User::factory()->create();

    $this->service->attachReferrer($user, 'upper123');

    expect($user->fresh()->referred_by)->toBe($referrer->id);
});

it('returns false when referred user is not KYC verified', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::PENDING]);

    expect($this->service->referredQualifies($user))->toBeFalse();
});

it('returns false when referred user has no deposits', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);

    expect($this->service->referredQualifies($user))->toBeFalse();
});

it('returns true when referred user qualifies', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::DEPOSIT,
        'currency' => 'USD',
        'status' => TransactionStatus::COMPLETED,
        'amount' => 200,
    ]);

    expect($this->service->referredQualifies($user))->toBeTrue();
});

it('does not grant reward when no referrer', function () {
    $user = User::factory()->create(['kyc_status' => KycStatus::VERIFIED, 'referred_by' => null]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::DEPOSIT,
        'currency' => 'USD',
        'status' => TransactionStatus::COMPLETED,
        'amount' => 200,
    ]);

    $this->service->maybeGrant($user);

    expect(ReferralReward::count())->toBe(0);
});

it('grants reward to referrer when conditions met', function () {
    $referrer = User::factory()->create();
    $referred = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'referred_by' => $referrer->id,
    ]);

    // Give referrer a USD wallet
    Wallet::factory()->create([
        'user_id' => $referrer->id,
        'currency' => 'USD',
        'balance' => 0,
    ]);

    Transaction::factory()->create([
        'user_id' => $referred->id,
        'type' => TransactionType::DEPOSIT,
        'currency' => 'USD',
        'status' => TransactionStatus::COMPLETED,
        'amount' => ReferralService::DEPOSIT_THRESHOLD,
    ]);

    $this->service->maybeGrant($referred);

    expect(ReferralReward::count())->toBe(1);

    $reward = ReferralReward::first();
    expect($reward->referrer_id)->toBe($referrer->id);
    expect($reward->referred_id)->toBe($referred->id);
    expect((float) $reward->referrer_reward)->toBe(ReferralService::DEFAULT_REWARD);
});

it('is idempotent — does not grant reward twice', function () {
    $referrer = User::factory()->create();
    $referred = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'referred_by' => $referrer->id,
    ]);

    Wallet::factory()->create([
        'user_id' => $referrer->id,
        'currency' => 'USD',
        'balance' => 0,
    ]);

    Transaction::factory()->create([
        'user_id' => $referred->id,
        'type' => TransactionType::DEPOSIT,
        'currency' => 'USD',
        'status' => TransactionStatus::COMPLETED,
        'amount' => ReferralService::DEPOSIT_THRESHOLD,
    ]);

    $this->service->maybeGrant($referred);
    $this->service->maybeGrant($referred); // second call should be no-op

    expect(ReferralReward::count())->toBe(1);
});

it('grants on KYC verified triggers maybeGrant', function () {
    $referrer = User::factory()->create();
    $referred = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'referred_by' => $referrer->id,
    ]);

    Wallet::factory()->create([
        'user_id' => $referrer->id,
        'currency' => 'USD',
        'balance' => 0,
    ]);

    Transaction::factory()->create([
        'user_id' => $referred->id,
        'type' => TransactionType::DEPOSIT,
        'currency' => 'USD',
        'status' => TransactionStatus::COMPLETED,
        'amount' => ReferralService::DEPOSIT_THRESHOLD,
    ]);

    $this->service->grantOnKycVerified($referred);

    expect(ReferralReward::count())->toBe(1);
});

it('returns referral info for user', function () {
    $user = User::factory()->create(['referral_code' => 'MYCODE']);

    $info = $this->service->info($user);

    expect($info['referral_code'])->toBe('MYCODE');
    expect($info['reward_amount'])->toBe(ReferralService::DEFAULT_REWARD);
    expect($info['reward_currency'])->toBe('USD');
    expect($info['total_referrals'])->toBe(0);
    expect($info['total_earned'])->toBe(0.0);
    expect($info['invite_url'])->toContain('MYCODE');
});

it('returns referral info with earned rewards', function () {
    $user = User::factory()->create();
    $referred = User::factory()->create(['referred_by' => $user->id]);

    ReferralReward::factory()->create([
        'referrer_id' => $user->id,
        'referred_id' => $referred->id,
        'referrer_reward' => 10,
    ]);

    $info = $this->service->info($user);

    expect($info['total_referrals'])->toBeGreaterThanOrEqual(1);
    expect((float) $info['total_earned'])->toBe(10.0);
});

it('does not grant reward when reward amount is zero', function () {
    SystemSetting::set(ReferralService::SETTING_KEY, 0);
    $this->service = app(ReferralService::class);

    $referrer = User::factory()->create();
    $referred = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'referred_by' => $referrer->id,
    ]);

    Wallet::factory()->create([
        'user_id' => $referrer->id,
        'currency' => 'USD',
        'balance' => 0,
    ]);

    Transaction::factory()->create([
        'user_id' => $referred->id,
        'type' => TransactionType::DEPOSIT,
        'currency' => 'USD',
        'status' => TransactionStatus::COMPLETED,
        'amount' => ReferralService::DEPOSIT_THRESHOLD,
    ]);

    $this->service->maybeGrant($referred);

    expect(ReferralReward::count())->toBe(0);
});

it('creates USD wallet for referrer if none exists', function () {
    $referrer = User::factory()->create();
    $referred = User::factory()->create([
        'kyc_status' => KycStatus::VERIFIED,
        'referred_by' => $referrer->id,
    ]);

    // No wallet for referrer — should be auto-created

    Transaction::factory()->create([
        'user_id' => $referred->id,
        'type' => TransactionType::DEPOSIT,
        'currency' => 'USD',
        'status' => TransactionStatus::COMPLETED,
        'amount' => ReferralService::DEPOSIT_THRESHOLD,
    ]);

    $this->service->maybeGrant($referred);

    $wallet = Wallet::where('user_id', $referrer->id)->where('currency', 'USD')->first();
    expect($wallet)->not->toBeNull();
    expect((float) $wallet->balance)->toBe(ReferralService::DEFAULT_REWARD);
});
