<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\VirtualCard;
use App\Models\CardInventory;
use App\Models\CardPricing;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\CardStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\ActivityLog;

class CardService
{
    protected FeeService $feeService;

    /**
     * Single source of truth for per-card daily/monthly spending caps.
     *
     * Chosen values: the tighter (Stripe-side) figures win — $500/day,
     * $5,000/month — since these are enforced on real money movement via a
     * real card network, whereas the old $1,000/$10,000 pair only ever backed
     * the fake local-card path. CardService::createCard() (legacy/local path)
     * and StripeIssuingService::issueVirtualCard() both read these constants
     * so a user gets one consistent answer regardless of path.
     */
    public const DAILY_LIMIT = 500.0;
    public const MONTHLY_LIMIT = 5000.0;

    public function __construct(FeeService $feeService)
    {
        $this->feeService = $feeService;
    }

    /**
     * Get card pricing
     */
    public function getPricing(string $brand = 'all', string $type = 'virtual'): array
    {
        $pricing = CardPricing::where('is_active', true)
            ->where(function ($q) use ($brand) {
                $q->where('brand', $brand)->orWhere('brand', 'all');
            })
            ->where(function ($q) use ($type) {
                $q->where('type', $type)->orWhere('type', 'all');
            })
            ->first();

        if (!$pricing) {
            // Default pricing
            return [
                'purchase_price' => 10.00,
                'monthly_fee' => 0.00,
                'min_load' => 100.00,
                'max_load' => 5000.00,
                'load_fee_percentage' => 1.0,
                'load_fee_fixed' => 0.00,
                'transaction_fee_percentage' => 0.5,
                'atm_fee' => 2.50,
                'international_fee_percentage' => 3.0,
                'kyc_level_required' => 2,
            ];
        }

        return [
            'purchase_price' => (float) $pricing->purchase_price,
            'monthly_fee' => (float) $pricing->monthly_fee,
            'min_load' => (float) $pricing->min_load,
            'max_load' => (float) $pricing->max_load,
            'load_fee_percentage' => (float) $pricing->load_fee_percentage,
            'load_fee_fixed' => (float) $pricing->load_fee_fixed,
            'transaction_fee_percentage' => (float) $pricing->transaction_fee_percentage,
            'atm_fee' => (float) $pricing->atm_fee,
            'international_fee_percentage' => (float) $pricing->international_fee_percentage,
            'kyc_level_required' => $pricing->kyc_level_required,
        ];
    }

    /**
     * Charge the card-purchase fee against a wallet, under a pessimistic lock,
     * and record a FEE transaction. Reused by both the legacy local-card path
     * and the Stripe issuance path so the fee logic + audit trail stay identical.
     *
     * On failure (insufficient balance) no debit/transaction is created.
     * Callers that go on to attempt a provider-side card issuance MUST refund
     * (see {@see refundPurchaseFee}) if issuance fails after this succeeds.
     *
     * @return array{success:bool,error?:string,required?:float,available?:float,
     *               fee?:float,transaction_id?:int|null,wallet?:Wallet}
     */
    public function chargePurchaseFee(User $user, Wallet $wallet, string $brand = 'visa', string $type = 'virtual'): array
    {
        if ($wallet->currency !== 'USD') {
            return ['success' => false, 'error' => 'البطاقات تعمل بمحفظة الدولار (USD) فقط'];
        }

        $pricing = $this->getPricing($brand, $type);

        if (($user->kyc_level ?? 0) < $pricing['kyc_level_required']) {
            return [
                'success' => false,
                'error' => 'يرجى إكمال التحقق من الهوية (المستوى 2) لإنشاء بطاقة',
                'required_level' => $pricing['kyc_level_required'],
                'current_level' => $user->kyc_level ?? 0,
            ];
        }

        $purchasePrice = (float) $pricing['purchase_price'];

        return DB::transaction(function () use ($user, $wallet, $brand, $type, $purchasePrice) {
            $lockedWallet = Wallet::lockForUpdate()->find($wallet->id);
            if (!$lockedWallet || (float) $lockedWallet->available_balance < $purchasePrice) {
                return [
                    'success' => false,
                    'error' => 'رصيد المحفظة غير كافٍ لإنشاء البطاقة (رسوم الإصدار $'
                        . number_format($purchasePrice, 2) . ')',
                    'required' => $purchasePrice,
                    'available' => $lockedWallet ? $lockedWallet->available_balance : 0,
                ];
            }

            $balanceBefore = $lockedWallet->balance;
            if ($purchasePrice > 0) {
                $lockedWallet->debit($purchasePrice);
            }

            $purchaseTransactionId = null;
            if ($purchasePrice > 0) {
                $purchaseTransaction = Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $lockedWallet->id,
                    'type' => TransactionType::FEE,
                    'category' => TransactionCategory::CARD,
                    'currency' => $lockedWallet->currency,
                    'amount' => -$purchasePrice,
                    'fee' => 0,
                    'net_amount' => -$purchasePrice,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $lockedWallet->balance,
                    'status' => TransactionStatus::COMPLETED,
                    'title' => 'Card Purchase Fee',
                    'description' => ucfirst($brand) . ' ' . ucfirst($type) . ' Card',
                    'completed_at' => now(),
                ]);
                $purchaseTransactionId = $purchaseTransaction->id;
            }

            return [
                'success' => true,
                'fee' => $purchasePrice,
                'transaction_id' => $purchaseTransactionId,
                'wallet' => $lockedWallet,
            ];
        });
    }

    /**
     * Refund a previously charged purchase fee (e.g. Stripe issuance failed
     * after the fee was already taken). Credits the wallet back and records
     * a reversing transaction — never silently swallow a charge for a card
     * that was never actually issued.
     */
    public function refundPurchaseFee(User $user, Wallet $wallet, float $fee, ?int $originalTransactionId = null): array
    {
        if ($fee <= 0) {
            return ['success' => true, 'refunded' => 0];
        }

        return DB::transaction(function () use ($user, $wallet, $fee, $originalTransactionId) {
            $lockedWallet = Wallet::lockForUpdate()->find($wallet->id);
            if (!$lockedWallet) {
                return ['success' => false, 'error' => 'المحفظة غير موجودة'];
            }

            $balanceBefore = $lockedWallet->balance;
            $lockedWallet->credit($fee);

            $refundTxn = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $lockedWallet->id,
                'type' => TransactionType::FEE,
                'category' => TransactionCategory::CARD,
                'currency' => $lockedWallet->currency,
                'amount' => $fee,
                'fee' => 0,
                'net_amount' => $fee,
                'balance_before' => $balanceBefore,
                'balance_after' => $lockedWallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'Card Purchase Fee Refund',
                'description' => 'Refund: card issuance failed after fee was charged',
                'completed_at' => now(),
            ]);

            ActivityLog::log(
                'card.fee_refunded',
                user: $user,
                entity: $refundTxn,
                newValues: [
                    'amount' => $fee,
                    'original_transaction_id' => $originalTransactionId,
                ],
                description: "Card purchase fee refunded (\${$fee}) — provider issuance failed"
            );

            return [
                'success' => true,
                'refunded' => $fee,
                'transaction_id' => $refundTxn->id,
            ];
        });
    }

    /**
     * Create a new virtual card
     */
    public function createCard(
        User $user,
        Wallet $wallet,
        string $brand = 'visa',
        string $type = 'virtual',
        ?string $nickname = null,
        ?string $color = null,
        ?float $spendingLimit = null
    ): array {
        // Cards are issued & operate on the USD wallet only.
        if ($wallet->currency !== 'USD') {
            return [
                'success' => false,
                'error' => 'البطاقات تعمل بمحفظة الدولار (USD) فقط',
            ];
        }

        // Check KYC level
        $pricing = $this->getPricing($brand, $type);

        if (($user->kyc_level ?? 0) < $pricing['kyc_level_required']) {
            return [
                'success' => false,
                'error' => 'يرجى إكمال التحقق من الهوية (المستوى 2) لإنشاء بطاقة',
                'required_level' => $pricing['kyc_level_required'],
                'current_level' => $user->kyc_level ?? 0,
            ];
        }

        // Check balance for purchase price
        $purchasePrice = (float) $pricing['purchase_price'];

        if ($wallet->available_balance < $purchasePrice) {
            return [
                'success' => false,
                'error' => 'رصيد المحفظة غير كافٍ لإنشاء البطاقة (رسوم الإصدار $'
                    . number_format($purchasePrice, 2) . ')',
                'required' => $purchasePrice,
                'available' => $wallet->available_balance,
            ];
        }

        return DB::transaction(function () use ($user, $wallet, $brand, $type, $nickname, $color, $spendingLimit, $purchasePrice) {
            // Lock wallet under pessimistic lock to prevent TOCTOU double-spend
            $lockedWallet = Wallet::lockForUpdate()->find($wallet->id);
            if (!$lockedWallet || (float) $lockedWallet->available_balance < $purchasePrice) {
                return [
                    'success' => false,
                    'error' => 'رصيد المحفظة غير كافٍ لإنشاء البطاقة (رسوم الإصدار $'
                        . number_format($purchasePrice, 2) . ')',
                    'required' => $purchasePrice,
                    'available' => $lockedWallet ? $lockedWallet->available_balance : 0,
                ];
            }

            // Charge purchase fee
            $balanceBefore = $lockedWallet->balance;
            if ($purchasePrice > 0) {
                $lockedWallet->debit($purchasePrice);
            }

            // Create transaction for card purchase (only if there is a fee)
            $purchaseTransactionId = null;
            if ($purchasePrice > 0) {
                $purchaseTransaction = Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $lockedWallet->id,
                    'type' => TransactionType::FEE,
                    'category' => TransactionCategory::CARD,
                    'currency' => $lockedWallet->currency,
                    'amount' => -$purchasePrice,
                    'fee' => 0,
                    'net_amount' => -$purchasePrice,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $lockedWallet->balance,
                    'status' => TransactionStatus::COMPLETED,
                    'title' => 'Card Purchase Fee',
                    'description' => ucfirst($brand) . ' ' . ucfirst($type) . ' Card',
                    'completed_at' => now(),
                ]);
                $purchaseTransactionId = $purchaseTransaction->id;
            }

            // Create virtual card.
            // card_number / cvv / masked / expiry / bin are generated by the
            // model's `creating` boot hook — never pass non-existent columns.
            $card = VirtualCard::create([
                'user_id' => $user->id,
                'wallet_id' => $lockedWallet->id,
                'card_type' => $type,
                'brand' => $brand,
                'cardholder_name' => strtoupper($user->full_name),
                'balance' => 0,
                'status' => 'active',
                'is_active' => true,
                'nickname' => $nickname,
                // SAKK burgundy brand default (was off-brand indigo #6366f1).
                // NB: this legacy local-card path is no longer reached by the
                // mobile app's POST /cards (store() now issues via Stripe),
                // kept only in case a non-provider path is ever re-enabled.
                'color' => $color ?? '#7A1F2B',
                'spending_limit' => $spendingLimit ?? 5000,
                'daily_limit' => self::DAILY_LIMIT,
                'monthly_limit' => self::MONTHLY_LIMIT,
                'per_transaction_limit' => $spendingLimit !== null && $spendingLimit < 500 ? $spendingLimit : 500,
                'online_enabled' => true,
                'international_enabled' => true,
                'contactless_enabled' => true,
                'atm_enabled' => false,
            ]);

            // Audit log: card creation
            ActivityLog::log(
                'card.created',
                user: $user,
                entity: $card,
                newValues: [
                    'brand' => $brand,
                    'type' => $type,
                    'nickname' => $nickname,
                    'spending_limit' => $spendingLimit,
                ],
                description: "Virtual {$brand} card created for wallet {$lockedWallet->currency}"
            );

            return [
                'success' => true,
                'card' => [
                    'id' => $card->id,
                    'uuid' => $card->uuid,
                    'brand' => $card->brand->value ?? $brand,
                    'type' => $type,
                    'last4' => substr((string) $card->card_number, -4),
                    'cardholder_name' => $card->cardholder_name,
                    'expiry' => $card->expiry_month . '/' . substr((string) $card->expiry_year, -2),
                    'status' => 'active',
                    'balance' => (float) $card->balance,
                ],
                'purchase_fee' => $purchasePrice,
                'transaction_id' => $purchaseTransactionId,
            ];
        });
    }

    /**
     * Get card from inventory
     */
    protected function getCardFromInventory(string $brand, string $type): ?CardInventory
    {
        return CardInventory::where('brand', $brand)
            ->where('type', $type)
            ->where('is_assigned', false)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Import cards from file (admin function).
     *
     * DISABLED (PCI): this ingested plaintext PAN+CVV from a hardcoded local
     * filesystem path outside the PCI-DSS boundary (/home/.../Desktop/tmp/),
     * building a manual card inventory (CardInventory) that was never even
     * wired into issuance (getCardFromInventory() is dead — createCard() never
     * called it). Card issuance is now Stripe-only: Stripe is the card vault
     * and holds all real PAN/CVV data. There is no legitimate reason left to
     * pull raw card numbers onto this server, so the import is neutralized
     * here rather than deleted (keeps the admin route from 500ing).
     */
    public function importCardsFromFile(string $filePath): array
    {
        return [
            'success' => false,
            'error' => 'Manual card inventory import is disabled; cards are issued via Stripe.',
        ];
    }

    /**
     * Load funds to card
     */
    public function loadCard(
        VirtualCard $card,
        Wallet $wallet,
        float $amount
    ): array {
        // Verify card ownership
        if ($card->user_id !== $wallet->user_id) {
            return ['success' => false, 'error' => 'غير مصرح'];
        }

        // Check card status. `status` is cast to the CardStatus enum, so it must
        // be compared against the enum — comparing to the raw string 'active' is
        // always true and wrongly blocks every card (even active ones).
        if ($card->status === CardStatus::FROZEN) {
            return ['success' => false, 'error' => 'البطاقة مجمّدة — ألغِ التجميد أولاً'];
        }
        if ($card->status !== CardStatus::ACTIVE || !$card->is_active) {
            return ['success' => false, 'error' => 'البطاقة غير نشطة'];
        }

        // Check limits
        // brand & card_type are backed enums — getPricing() expects strings.
        $pricing = $this->getPricing($card->brand->value, $card->card_type->value);
        
        if ($amount < $pricing['min_load']) {
            return [
                'success' => false,
                'error' => "Minimum load amount is \${$pricing['min_load']}",
            ];
        }

        if ($amount > $pricing['max_load']) {
            return [
                'success' => false,
                'error' => "Maximum load amount is \${$pricing['max_load']}",
            ];
        }

        // Calculate fee
        $fee = ($amount * $pricing['load_fee_percentage'] / 100) + $pricing['load_fee_fixed'];
        $totalDebit = $amount + $fee;

        if ($wallet->available_balance < $totalDebit) {
            return [
                'success' => false,
                'error' => 'رصيد غير كافٍ',
                'required' => $totalDebit,
                'available' => $wallet->available_balance,
            ];
        }

        return DB::transaction(function () use ($card, $wallet, $amount, $fee, $totalDebit) {
            // Lock both wallet and card under pessimistic lock
            $lockedWallet = Wallet::lockForUpdate()->find($wallet->id);
            $lockedCard = VirtualCard::lockForUpdate()->find($card->id);

            if (!$lockedWallet || !$lockedCard) {
                return ['success' => false, 'error' => 'المحفظة أو البطاقة غير موجودة'];
            }

            // Re-verify card ownership and status under lock
            if ($lockedCard->user_id !== $lockedWallet->user_id) {
                return ['success' => false, 'error' => 'غير مصرح'];
            }
            if ($lockedCard->status !== CardStatus::ACTIVE || !$lockedCard->is_active) {
                return ['success' => false, 'error' => 'البطاقة غير نشطة'];
            }

            // Re-check wallet balance under lock
            if ((float) $lockedWallet->available_balance < $totalDebit) {
                return ['success' => false, 'error' => 'رصيد غير كافٍ'];
            }

            $walletBalanceBefore = $lockedWallet->balance;
            $cardBalanceBefore = $lockedCard->balance;

            // Debit wallet
            $lockedWallet->debit($totalDebit);

            // Credit card (locked model)
            $lockedCard->increment('balance', $amount);

            // Create transactions
            $walletTxn = Transaction::create([
                'user_id' => $lockedWallet->user_id,
                'wallet_id' => $lockedWallet->id,
                'card_id' => $lockedCard->id,
                'type' => TransactionType::CARD_LOAD,
                'category' => TransactionCategory::CARD,
                'currency' => $lockedWallet->currency,
                'amount' => -$amount,
                'fee' => $fee,
                'net_amount' => -$totalDebit,
                'balance_before' => $walletBalanceBefore,
                'balance_after' => $lockedWallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'Card Load',
                'description' => "Load to card ending {$lockedCard->card_number_last4}",
                'completed_at' => now(),
            ]);

            // Audit log: card load
            ActivityLog::log(
                'card.load',
                user: $lockedWallet->user,
                entity: $walletTxn,
                newValues: [
                    'card_id' => $lockedCard->id,
                    'amount' => $amount,
                    'fee' => $fee,
                    'total_debited' => $totalDebit,
                ],
                description: "Card load: {$amount} + {$fee} fee"
            );

            return [
                'success' => true,
                'amount_loaded' => $amount,
                'fee' => $fee,
                'total_debited' => $totalDebit,
                'card_balance' => $lockedCard->balance,
                'wallet_balance' => $lockedWallet->balance,
                'transaction_id' => $walletTxn->id,
            ];
        });
    }

    /**
     * Unload funds from card back to wallet
     */
    public function unloadCard(
        VirtualCard $card,
        Wallet $wallet,
        float $amount
    ): array {
        if ($card->user_id !== $wallet->user_id) {
            return ['success' => false, 'error' => 'غير مصرح'];
        }

        if ($card->balance < $amount) {
            return [
                'success' => false,
                'error' => 'رصيد البطاقة غير كافٍ',
                'available' => $card->balance,
            ];
        }

        return DB::transaction(function () use ($card, $wallet, $amount) {
            // Lock both wallet and card under pessimistic lock
            $lockedWallet = Wallet::lockForUpdate()->find($wallet->id);
            $lockedCard = VirtualCard::lockForUpdate()->find($card->id);

            if (!$lockedWallet || !$lockedCard) {
                return ['success' => false, 'error' => 'المحفظة أو البطاقة غير موجودة'];
            }

            // Re-verify card ownership under lock
            if ($lockedCard->user_id !== $lockedWallet->user_id) {
                return ['success' => false, 'error' => 'غير مصرح'];
            }

            // Re-check card balance under lock
            if ($lockedCard->balance < $amount) {
                return ['success' => false, 'error' => 'رصيد البطاقة غير كافٍ', 'available' => $lockedCard->balance];
            }

            $walletBalanceBefore = $lockedWallet->balance;
            $cardBalanceBefore = $lockedCard->balance;

            // Debit card
            $lockedCard->decrement('balance', $amount);

            // Credit wallet
            $lockedWallet->credit($amount);

            // Create transaction
            $txn = Transaction::create([
                'user_id' => $lockedWallet->user_id,
                'wallet_id' => $lockedWallet->id,
                'card_id' => $lockedCard->id,
                'type' => TransactionType::CARD_UNLOAD,
                'category' => TransactionCategory::CARD,
                'currency' => $lockedWallet->currency,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $walletBalanceBefore,
                'balance_after' => $lockedWallet->balance,
                'status' => TransactionStatus::COMPLETED,
                'title' => 'Card Unload',
                'description' => "Unload from card ending {$lockedCard->card_number_last4}",
                'completed_at' => now(),
            ]);

            // Audit log: card unload
            ActivityLog::log(
                'card.unload',
                user: $lockedWallet->user,
                entity: $txn,
                newValues: [
                    'card_id' => $lockedCard->id,
                    'amount' => $amount,
                ],
                description: "Card unload: {$amount}"
            );

            return [
                'success' => true,
                'amount_unloaded' => $amount,
                'card_balance' => $lockedCard->balance,
                'wallet_balance' => $lockedWallet->balance,
                'transaction_id' => $txn->id,
            ];
        });
    }

    /**
     * Get card details — PCI-DSS safe: never expose full PAN or CVV.
     *
     * Returns only the masked card number, last 4 digits, and expiry.
     * The full card number and CVV must never leave the server except
     * through the payment processor (Stripe) over a TLS connection.
     */
    public function getCardDetails(VirtualCard $card): array
    {
        return [
            'success' => true,
            'card' => [
                'card_number_masked' => $card->card_number_masked,
                'last4' => substr((string) $card->card_number, -4),
                'bin' => $card->bin,
                'expiry_month' => $card->expiry_month,
                'expiry_year' => $card->expiry_year,
                'cardholder_name' => $card->cardholder_name,
                'balance' => (float) $card->balance,
                'brand' => $card->brand->value ?? null,
            ],
        ];
    }

    /**
     * Freeze/Unfreeze card
     */
    public function toggleFreeze(VirtualCard $card): array
    {
        // status is cast to CardStatus — compare to the enum, not a string literal,
        // and forceFill since status is guarded (SEC-003).
        $isFrozen = $card->status === CardStatus::FROZEN;
        $newStatus = $isFrozen ? CardStatus::ACTIVE : CardStatus::FROZEN;
        $card->forceFill(['status' => $newStatus])->save();

        return [
            'success' => true,
            'status' => $newStatus->value,
            'message' => $isFrozen ? 'Card unfrozen successfully' : 'Card frozen successfully',
        ];
    }

    /**
     * Cancel card permanently
     */
    public function cancelCard(VirtualCard $card, Wallet $wallet): array
    {
        // Ownership check: card must belong to the wallet's user (IDOR guard)
        if ($card->user_id !== $wallet->user_id) {
            return ['success' => false, 'error' => 'غير مصرح'];
        }

        return DB::transaction(function () use ($card, $wallet) {
            // Lock both card and wallet under pessimistic lock
            $lockedCard = VirtualCard::lockForUpdate()->find($card->id);
            $lockedWallet = Wallet::lockForUpdate()->find($wallet->id);

            if (!$lockedCard || !$lockedWallet) {
                return ['success' => false, 'error' => 'البطاقة أو المحفظة غير موجودة'];
            }

            // Re-verify ownership under lock
            if ($lockedCard->user_id !== $lockedWallet->user_id) {
                return ['success' => false, 'error' => 'غير مصرح'];
            }

            // Refund remaining balance to wallet
            $refundedAmount = $lockedCard->balance;
            if ($refundedAmount > 0) {
                $lockedWallet->credit($refundedAmount);
                
                Transaction::create([
                    'user_id' => $lockedWallet->user_id,
                    'wallet_id' => $lockedWallet->id,
                    'card_id' => $lockedCard->id,
                    'type' => TransactionType::CARD_UNLOAD,
                    'category' => TransactionCategory::CARD,
                    'currency' => $lockedWallet->currency,
                    'amount' => $refundedAmount,
                    'fee' => 0,
                    'net_amount' => $refundedAmount,
                    'status' => TransactionStatus::COMPLETED,
                    'title' => 'Card Cancellation Refund',
                    'completed_at' => now(),
                ]);
            }

            // NB: no cancelled_at column exists on virtual_cards; status='cancelled' is the marker.
            $lockedCard->forceFill([
                'status' => 'cancelled',
                'balance' => 0,
            ])->save();

            return [
                'success' => true,
                'refunded' => $refundedAmount,
                'message' => 'Card cancelled successfully',
            ];
        });
    }

    // Helper methods

    protected function generateCardNumber(string $brand): string
    {
        $prefix = $brand === 'visa' ? '4' : '5';
        $number = $prefix . str_pad((string) random_int(0, 999999999999999), 15, '0', STR_PAD_LEFT);
        
        // Apply Luhn algorithm for valid checksum
        return $this->applyLuhn(substr($number, 0, 15));
    }

    protected function generateCVV(): string
    {
        return str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    protected function generateExpiry(): array
    {
        $future = now()->addYears(3);
        return [
            'month' => $future->format('m'),
            'year' => $future->format('Y'),
        ];
    }

    protected function detectBrand(string $cardNumber): string
    {
        $firstDigit = $cardNumber[0] ?? '';
        return match($firstDigit) {
            '4' => 'visa',
            '5' => 'mastercard',
            '3' => 'amex',
            default => 'visa',
        };
    }

    protected function validateLuhn(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];
            
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return $sum % 10 === 0;
    }

    protected function applyLuhn(string $partialNumber): string
    {
        for ($check = 0; $check <= 9; $check++) {
            $full = $partialNumber . $check;
            if ($this->validateLuhn($full)) {
                return $full;
            }
        }
        
        return $partialNumber . '0';
    }
}
