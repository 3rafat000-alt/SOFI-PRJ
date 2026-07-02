<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\UserStatus;
use App\Support\LedgerHaltGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\ActivityLog;

/**
 * Peer-to-peer money transfer between SAKK users.
 *
 * Same-currency, instant, FREE (no fee). Race-condition safe via lockForUpdate
 * on both wallets inside a single DB transaction. A recipient can be resolved by
 * SAKK tag (referral_code), email, or phone number.
 */
class TransferService
{
    public function __construct(protected KycService $kyc = new KycService()) {}

    /**
     * Resolve a recipient user from a free-text identifier:
     * SAKK tag (referral_code), email, or phone.
     */
    public function resolveRecipient(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        // Account number (SAKK): "SK" + zero-padded user id, e.g. SK00000002.
        // Allow optional leading '@'/'#' and surrounding spaces.
        $cleaned = strtoupper(ltrim($identifier, '@#'));
        if (preg_match('/^SK0*(\d+)$/', $cleaned, $m)) {
            $byAccount = User::find((int) $m[1]);
            if ($byAccount) {
                return $byAccount;
            }
        }

        // Email
        if (str_contains($identifier, '@')) {
            return User::whereRaw('LOWER(email) = ?', [strtolower($identifier)])->first();
        }

        // SAKK tag (referral_code) — case-insensitive, allow leading '@' or '#'
        $tag = ltrim($identifier, '@#');
        $byTag = User::whereRaw('UPPER(referral_code) = ?', [strtoupper($tag)])->first();
        if ($byTag) {
            return $byTag;
        }

        // Phone — strip spaces/dashes
        $phone = preg_replace('/[\s\-]+/', '', $identifier);
        return User::where('phone', $phone)->first();
    }

    /**
     * Build a lightweight, privacy-preserving recipient card for the confirm UI.
     */
    public function recipientCard(User $recipient): array
    {
        return [
            'id' => $recipient->id,
            'name' => $recipient->full_name,
            'initials' => $this->initials($recipient),
            'tag' => $recipient->referral_code,
            'account_number' => 'SK' . str_pad((string) $recipient->id, 8, '0', STR_PAD_LEFT),
            'avatar' => $recipient->avatar,
        ];
    }

    /**
     * Execute a P2P transfer.
     *
     * @throws \RuntimeException on validation / balance failures
     */
    public function transfer(User $sender, User $recipient, float $amount, string $currency, ?string $note = null): array
    {
        LedgerHaltGuard::assertNotHalted();

        $currency = strtoupper($currency);

        if (!in_array($currency, ['USD', 'SYP'], true)) {
            throw new \RuntimeException('العملة غير مدعومة');
        }
        if ($amount <= 0) {
            throw new \RuntimeException('المبلغ غير صالح');
        }
        if ($sender->id === $recipient->id) {
            throw new \RuntimeException('لا يمكنك التحويل إلى نفسك');
        }
        if ($sender->status === UserStatus::SUSPENDED || $sender->status === UserStatus::BANNED) {
            throw new \RuntimeException('حسابك مقيّد، لا يمكن إجراء التحويل');
        }

        $note = $note !== null ? mb_substr(trim($note), 0, 140) : null;

        return DB::transaction(function () use ($sender, $recipient, $amount, $currency, $note) {
            // KYC limit check INSIDE the transaction, after wallets are locked
            // to prevent TOCTOU race on daily/monthly spent totals.
            $this->assertWithinKycLimits($sender, $amount, $currency);
            // Resolve wallet ids WITHOUT locking first, so both rows exist before
            // we acquire locks in a deterministic order (see E-SEV-1 below).
            $senderWalletId = Wallet::where('user_id', $sender->id)
                ->where('currency', $currency)
                ->value('id');

            if (!$senderWalletId) {
                throw new \RuntimeException("لا تملك محفظة {$currency}");
            }

            // Provision the recipient wallet up front (if missing) so its id
            // exists before ordered locking — no lock held during creation.
            $recipientWalletId = Wallet::where('user_id', $recipient->id)
                ->where('currency', $currency)
                ->value('id');

            if (!$recipientWalletId) {
                $created = $recipient->wallets()->create([
                    'currency' => $currency,
                    'is_default' => false,
                ]);
                $recipientWalletId = $created->id;
            }

            // deadlock-safe: lock wallets in ascending id order (see E-SEV-1)
            $orderedIds = [$senderWalletId, $recipientWalletId];
            sort($orderedIds);
            $lockedById = Wallet::lockForUpdate()
                ->whereIn('id', $orderedIds)
                ->orderBy('id')
                ->get()
                ->keyBy('id');

            $senderWallet = $lockedById->get($senderWalletId);
            $recipientWallet = $lockedById->get($recipientWalletId);

            if (!$senderWallet || !$recipientWallet) {
                throw new \RuntimeException('المحفظة غير موجودة');
            }

            if ($senderWallet->is_frozen || $recipientWallet->is_frozen) {
                throw new \RuntimeException('المحفظة مجمّدة، لا يمكن إتمام التحويل');
            }

            if ((float) $senderWallet->available_balance < $amount) {
                throw new \RuntimeException('رصيد غير كافٍ');
            }

            $senderBefore = (float) $senderWallet->balance;
            $recipientBefore = (float) $recipientWallet->balance;

            if (!$senderWallet->debit($amount)) {
                throw new \RuntimeException('رصيد غير كافٍ');
            }
            $recipientWallet->credit($amount);

            $sharedMeta = [
                'note' => $note,
                'sender_name' => $sender->full_name,
                'recipient_name' => $recipient->full_name,
            ];

            // Outgoing transaction (sender's ledger)
            $out = Transaction::create([
                'user_id' => $sender->id,
                'wallet_id' => $senderWallet->id,
                'recipient_id' => $recipient->id,
                'recipient_wallet_id' => $recipientWallet->id,
                'type' => TransactionType::TRANSFER_OUT,
                'category' => TransactionCategory::P2P,
                'currency' => $currency,
                'amount' => -$amount,
                'fee' => 0,
                'net_amount' => -$amount,
                'balance_before' => $senderBefore,
                'balance_after' => $senderWallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => "تحويل إلى {$recipient->full_name}",
                'description' => $note,
                'metadata' => array_merge($sharedMeta, ['counterparty_name' => $recipient->full_name]),
                'completed_at' => now(),
            ]);

            // Incoming transaction (recipient's ledger)
            $in = Transaction::create([
                'user_id' => $recipient->id,
                'wallet_id' => $recipientWallet->id,
                'recipient_id' => $sender->id,
                'recipient_wallet_id' => $senderWallet->id,
                'type' => TransactionType::TRANSFER_IN,
                'category' => TransactionCategory::P2P,
                'currency' => $currency,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $recipientBefore,
                'balance_after' => $recipientWallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => "تحويل من {$sender->full_name}",
                'description' => $note,
                'metadata' => array_merge($sharedMeta, ['counterparty_name' => $sender->full_name]),
                'completed_at' => now(),
            ]);

            $this->notifyRecipient($recipient, $sender, $amount, $currency, $note);
            $this->notifySender($sender, $recipient, $amount, $currency, $note);

            // Auto-cashback: reward the sender a small % of each transfer.
            $this->creditCashback($sender, $senderWallet, $amount, $currency);

            // Audit log: transfer executed
            ActivityLog::log(
                'transfer.executed',
                user: $sender,
                entity: $out,
                newValues: [
                    'recipient_id' => $recipient->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'note' => $note,
                ],
                description: "Transfer {$amount} {$currency} to {$recipient->full_name}"
            );

            return [
                'from_transaction' => $out,
                'to_transaction' => $in,
                'amount' => $amount,
                'currency' => $currency,
                'note' => $note,
                'recipient' => $this->recipientCard($recipient),
                'sender_wallet' => $senderWallet->fresh(),
            ];
        });
    }

    /** Credit the sender a small cashback (reward) on each transfer. */
    protected function creditCashback(User $sender, Wallet $senderWallet, float $amount, string $currency): void
    {
        try {
            $rate = 0.01; // 1% cashback
            $cb = $currency === 'USD'
                ? round($amount * $rate, 2)
                : (float) round($amount * $rate);
            if ($cb <= 0) {
                return;
            }

            $before = (float) $senderWallet->balance;
            $senderWallet->credit($cb);

            Transaction::create([
                'user_id' => $sender->id,
                'wallet_id' => $senderWallet->id,
                'type' => TransactionType::REWARD,
                'category' => TransactionCategory::REWARD,
                'currency' => $currency,
                'amount' => $cb,
                'fee' => 0,
                'net_amount' => $cb,
                'balance_before' => $before,
                'balance_after' => (float) $senderWallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'كاش باك على تحويل',
                'metadata' => ['source' => 'cashback', 'rate' => $rate],
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Cashback is non-critical; never break the transfer.
        }
    }

    /**
     * Enforce the sender's KYC-level limits for the given currency:
     * transfer permission, single-transaction, daily, and monthly caps.
     *
     * @throws \RuntimeException when a limit would be exceeded
     */
    protected function assertWithinKycLimits(User $sender, float $amount, string $currency): void
    {
        // Delegates to the shared KYC cap enforcer so transfer + withdraw hold the
        // SAME identity-based caps and count cumulative outbound across both channels.
        $this->kyc->assertWithinKycLimits($sender, $amount, $currency, 'transfer');
    }

    protected function fmt(float $amount, string $currency): string
    {
        return \App\Support\Money::format($amount, $currency);
    }

    protected function notifyRecipient(User $recipient, User $sender, float $amount, string $currency, ?string $note): void
    {
        $formatted = \App\Support\Money::format($amount, $currency);

        $title = 'استلمت تحويلاً';
        $body = "استلمت {$formatted} من {$sender->full_name}" . ($note ? " — {$note}" : '');

        UserNotification::create([
            'user_id' => $recipient->id,
            'uuid' => Str::uuid(),
            'template_code' => 'p2p_received',
            'channel' => 'in_app',
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'transfer_in',
                'amount' => $amount,
                'currency' => $currency,
                'sender_name' => $sender->full_name,
            ],
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        try {
            app(\App\Services\FCMService::class)->send(
                $recipient->fcm_token,
                $title,
                $body,
                ['type' => 'p2p_received', 'currency' => $currency, 'amount' => (string) $amount],
            );
        } catch (\Throwable $e) {
            logger()->error('Transfer FCM failed: ' . $e->getMessage());
        }
    }

    protected function notifySender(User $sender, User $recipient, float $amount, string $currency, ?string $note): void
    {
        $formatted = \App\Support\Money::format($amount, $currency);

        $title = 'تم التحويل';
        $body = "أرسلت {$formatted} إلى {$recipient->full_name}" . ($note ? " — {$note}" : '');

        UserNotification::create([
            'user_id' => $sender->id,
            'uuid' => Str::uuid(),
            'template_code' => 'p2p_sent',
            'channel' => 'in_app',
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'transfer_out',
                'amount' => $amount,
                'currency' => $currency,
                'recipient_name' => $recipient->full_name,
            ],
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        try {
            app(\App\Services\FCMService::class)->send(
                $sender->fcm_token,
                $title,
                $body,
                ['type' => 'p2p_sent', 'currency' => $currency, 'amount' => (string) $amount],
            );
        } catch (\Throwable $e) {
            logger()->error('Transfer sender FCM failed: ' . $e->getMessage());
        }
    }

    protected function initials(User $user): string
    {
        $f = mb_substr($user->first_name ?? '', 0, 1);
        $l = mb_substr($user->last_name ?? '', 0, 1);
        return mb_strtoupper($f . $l);
    }
}
