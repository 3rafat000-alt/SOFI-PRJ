<?php

namespace App\Services;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\KycStatus;
use App\Models\ReferralReward;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Referral program: invite people who don't have an account yet. When an
 * invited user registers (with the referrer's code) and completes KYC, the
 * referrer earns a configurable reward (default $1, set by admin).
 */
class ReferralService
{
    public const SETTING_KEY = 'referral_bonus_referrer';
    public const DEFAULT_REWARD = 5.0;
    public const REWARD_CURRENCY = 'USD';

    /// The referred user must deposit at least this (USD) before the reward unlocks.
    public const DEPOSIT_THRESHOLD = 100.0;

    /** Admin-configurable reward amount (USD). */
    public function rewardAmount(): float
    {
        return (float) SystemSetting::get(self::SETTING_KEY, self::DEFAULT_REWARD);
    }

    /** Link a newly registered user to their referrer via a referral code. */
    public function attachReferrer(User $user, ?string $referralCode): void
    {
        $code = trim((string) $referralCode);
        if ($code === '') {
            return;
        }
        $referrer = User::whereRaw('UPPER(referral_code) = ?', [strtoupper(ltrim($code, '@#'))])->first();
        if ($referrer && $referrer->id !== $user->id) {
            $user->update(['referred_by' => $referrer->id]);
        }
    }

    /**
     * Grant the referral reward to the referrer when [referredUser] becomes
     * KYC-verified. Idempotent: only ever pays out once per referred user.
     */
    /** Backward-compatible trigger — called when KYC becomes verified. */
    public function grantOnKycVerified(User $referredUser): void
    {
        $this->maybeGrant($referredUser);
    }

    /** True when the referred user is verified AND deposited >= $100 (USD). */
    public function referredQualifies(User $referredUser): bool
    {
        if ($referredUser->kyc_status !== KycStatus::VERIFIED) {
            return false;
        }
        $deposited = (float) Transaction::where('user_id', $referredUser->id)
            ->where('type', TransactionType::DEPOSIT)
            ->where('currency', self::REWARD_CURRENCY)
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('amount');
        return $deposited >= self::DEPOSIT_THRESHOLD;
    }

    /**
     * Grant the referral reward to the referrer once the referred user BOTH
     * verifies their identity AND deposits their first $100. Called from the
     * KYC-verified and deposit triggers; idempotent (pays out once).
     */
    public function maybeGrant(User $referredUser): void
    {
        if (!$referredUser->referred_by) {
            return;
        }

        // Already rewarded for this referred user?
        if (ReferralReward::where('referred_id', $referredUser->id)->exists()) {
            return;
        }

        // Both conditions must be met before paying out.
        if (!$this->referredQualifies($referredUser)) {
            return;
        }

        $referrer = User::find($referredUser->referred_by);
        if (!$referrer) {
            return;
        }

        $amount = $this->rewardAmount();
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($referrer, $referredUser, $amount) {
            $wallet = Wallet::where('user_id', $referrer->id)
                ->where('currency', self::REWARD_CURRENCY)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $created = $referrer->wallets()->create([
                    'currency' => self::REWARD_CURRENCY,
                    'is_default' => false,
                ]);
                $wallet = Wallet::where('id', $created->id)->lockForUpdate()->first();
            }

            $before = (float) $wallet->balance;
            $wallet->credit($amount);
            $wallet->increment('total_received', $amount);

            $tx = Transaction::create([
                'user_id' => $referrer->id,
                'wallet_id' => $wallet->id,
                'type' => TransactionType::REWARD,
                'category' => TransactionCategory::REWARD,
                'currency' => self::REWARD_CURRENCY,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $wallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => "مكافأة إحالة — {$referredUser->full_name}",
                'metadata' => ['referred_id' => $referredUser->id, 'referred_name' => $referredUser->full_name],
                'completed_at' => now(),
            ]);

            ReferralReward::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referredUser->id,
                'transaction_id' => $tx->id,
                'referrer_reward' => $amount,
                'referred_reward' => 0,
                'currency' => self::REWARD_CURRENCY,
                'trigger' => 'kyc_verified',
                'status' => 'credited',
            ]);
        });
    }

    /** Stats + share payload for the referral screen. */
    public function info(User $user): array
    {
        $count = User::where('referred_by', $user->id)->count();
        $earned = (float) ReferralReward::where('referrer_id', $user->id)
            ->where('status', 'credited')
            ->sum('referrer_reward');

        $inviteBase = rtrim((string) config('app.invite_url_base', 'https://sakk.app/invite'), '/');

        return [
            'referral_code' => $user->referral_code,
            'invite_url' => $inviteBase . '/' . $user->referral_code,
            'reward_amount' => $this->rewardAmount(),
            'reward_currency' => self::REWARD_CURRENCY,
            'total_referrals' => $count,
            'total_earned' => $earned,
        ];
    }
}
