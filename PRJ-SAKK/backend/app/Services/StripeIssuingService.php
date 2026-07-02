<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\User;
use App\Models\Wallet;
use App\Models\VirtualCard;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\CardStatus;
use App\Models\ActivityLog;
use App\Services\CardService;
use App\Services\KycService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Stripe Issuing Service
 * 
 * Handles:
 * - Cardholder creation
 * - Virtual card issuance  
 * - Real-time authorization (2-second webhook timeout)
 * - Card lifecycle management
 * 
 * CRITICAL: issuing_authorization.request webhook has 2-second timeout
 * Response must be approve/decline within 2 seconds or auto-decline
 */
class StripeIssuingService
{
    protected ?StripeClient $stripe = null;
    protected string $webhookSecret;
    protected bool $isTestMode;

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * SEV-4 fail-closed kill-switch: when the Integration row EXISTS and is
     * explicitly is_active=false, the admin has turned Stripe Issuing OFF —
     * do NOT fall through to env/config credentials, or a stray STRIPE_SECRET
     * in the environment would silently re-enable card issuance/authorization
     * behind the admin's back (see CardsFeature — the same doctrine applies
     * to this service's own client, not just the feature flag). Env fallback
     * stays available ONLY when the row is absent entirely.
     */
    protected function loadConfig(): void
    {
        // Prefer admin-managed credentials in the Integration store (admin → النظام →
        // الطرف الثالث والأمان); fall back to env/config when not set there.
        $integration = Integration::where('key', 'stripe')->first();

        if ($integration && !$integration->is_active) {
            // Admin-toggled OFF. Hard-disable — never read env creds, never
            // instantiate a Stripe client.
            $this->webhookSecret = '';
            $this->isTestMode = true;
            $this->stripe = null;
            return;
        }

        if ($integration && $integration->is_active) {
            $apiKey = $integration->getCredential('secret') ?: config('services.stripe.secret');
            $this->webhookSecret = $integration->getCredential('issuing_webhook_secret')
                ?: (config('services.stripe.issuing_webhook_secret') ?? '');
            $this->isTestMode = (bool) (data_get($integration->settings, 'test_mode')
                ?? config('services.stripe.test_mode') ?? true);
        } else {
            // No row at all -> fallback to env (unchanged behavior).
            $apiKey = config('services.stripe.secret');
            $this->webhookSecret = config('services.stripe.issuing_webhook_secret') ?? '';
            $this->isTestMode = config('services.stripe.test_mode') ?? true;
        }

        // Only instantiate when the Stripe SDK is present. Absence degrades
        // gracefully (isConfigured() → false) instead of a fatal class-not-found,
        // so the admin can save keys before `composer require stripe/stripe-php`.
        if ($apiKey && class_exists(StripeClient::class)) {
            $this->stripe = new StripeClient($apiKey);
        }
    }

    /**
     * Check if Stripe is configured
     */
    public function isConfigured(): bool
    {
        return $this->stripe !== null;
    }

    // ==================== CARDHOLDER MANAGEMENT ====================

    /**
     * Create Stripe Cardholder for user
     * Required before issuing cards
     */
    public function createCardholder(User $user): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Stripe غير مُكون'];
        }

        // Check if user already has cardholder
        if ($user->stripe_cardholder_id) {
            return [
                'success' => true,
                'cardholder_id' => $user->stripe_cardholder_id,
                'message' => 'المستخدم لديه cardholder مسبقاً',
            ];
        }

        // Validate KYC level
        if (($user->kyc_level ?? 0) < 2) {
            return [
                'success' => false,
                'error' => 'يرجى إكمال التحقق من الهوية (KYC Level 2) أولاً',
                'required_level' => 2,
                'current_level' => $user->kyc_level ?? 0,
            ];
        }

        try {
            $cardholder = $this->stripe->issuing->cardholders->create([
                'type' => 'individual',
                'name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone,
                'status' => 'active',
                'individual' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'dob' => [
                        'day' => $user->date_of_birth?->day ?? 1,
                        'month' => $user->date_of_birth?->month ?? 1,
                        'year' => $user->date_of_birth?->year ?? 1990,
                    ],
                ],
                'billing' => [
                    'address' => [
                        'line1' => $user->kyc_data['address']['line1'] ?? 'N/A',
                        'city' => $user->kyc_data['address']['city'] ?? 'N/A',
                        'state' => $user->kyc_data['address']['state'] ?? 'N/A',
                        'postal_code' => $user->kyc_data['address']['postal_code'] ?? '00000',
                        'country' => $user->kyc_data['address']['country'] ?? 'US',
                    ],
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'platform' => 'sakk_wallet',
                ],
            ]);

            // Save cardholder ID
            $user->update(['stripe_cardholder_id' => $cardholder->id]);

            Log::info('Stripe cardholder created', [
                'user_id' => $user->id,
                'cardholder_id' => $cardholder->id,
            ]);

            return [
                'success' => true,
                'cardholder_id' => $cardholder->id,
                'message' => 'تم إنشاء Cardholder بنجاح',
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe cardholder creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->translateStripeError($e),
            ];
        }
    }

    /**
     * Update cardholder information
     */
    public function updateCardholder(User $user): array
    {
        if (!$this->isConfigured() || !$user->stripe_cardholder_id) {
            return ['success' => false, 'error' => 'لا يوجد Cardholder للمستخدم'];
        }

        try {
            $cardholder = $this->stripe->issuing->cardholders->update(
                $user->stripe_cardholder_id,
                [
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone,
                ]
            );

            return [
                'success' => true,
                'cardholder' => $cardholder,
            ];
        } catch (ApiErrorException $e) {
            return ['success' => false, 'error' => $this->translateStripeError($e)];
        }
    }

    // ==================== CARD ISSUANCE ====================

    /**
     * Issue a new virtual card via Stripe Issuing
     */
    public function issueVirtualCard(
        User $user,
        Wallet $wallet,
        string $currency = 'usd',
        ?array $spendingControls = null
    ): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Stripe غير مُكون'];
        }

        // Ensure cardholder exists
        if (!$user->stripe_cardholder_id) {
            $cardholderResult = $this->createCardholder($user);
            if (!$cardholderResult['success']) {
                return $cardholderResult;
            }
        }

        // Check card limit based on KYC. KycService::cardsLimitForUser is the
        // single source of truth for the count cap (config/kyc.php
        // levels[*].cards_limit) — the store()/CardController path and this
        // Stripe path must agree, so a user gets one consistent answer.
        $cardLimit = app(KycService::class)->cardsLimitForUser($user);
        $currentCards = $user->cards()->whereIn('status', ['active', 'inactive'])->count();

        if ($currentCards >= $cardLimit) {
            return [
                'success' => false,
                'error' => "وصلت للحد الأقصى من البطاقات ({$cardLimit}) لمستوى التحقق الخاص بك",
            ];
        }

        return DB::transaction(function () use ($user, $wallet, $currency, $spendingControls) {
            try {
                // Default spending controls
                // CardService::DAILY_LIMIT/MONTHLY_LIMIT are the single source
                // of truth for card spending caps (item 2: consolidate limits) —
                // Stripe wants cents, hence *100.
                $controls = $spendingControls ?? [
                    'spending_limits' => [
                        [
                            'amount' => (int) (CardService::DAILY_LIMIT * 100),
                            'interval' => 'daily',
                        ],
                        [
                            'amount' => (int) (CardService::MONTHLY_LIMIT * 100),
                            'interval' => 'monthly',
                        ],
                    ],
                    'allowed_categories' => null, // All categories
                    'blocked_categories' => ['adult_entertainment_stores'],
                ];

                // Create card in Stripe
                $stripeCard = $this->stripe->issuing->cards->create([
                    'cardholder' => $user->stripe_cardholder_id,
                    'currency' => strtolower($currency),
                    'type' => 'virtual',
                    'status' => 'active',
                    'spending_controls' => $controls,
                    'metadata' => [
                        'user_id' => $user->id,
                        'wallet_id' => $wallet->id,
                        'platform' => 'sakk_wallet',
                    ],
                ]);

                // Create local card record
                $localCard = VirtualCard::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'provider' => 'stripe',
                    'provider_card_id' => $stripeCard->id,
                    'brand' => $stripeCard->brand ?? 'visa',
                    'card_type' => 'virtual',
                    'cardholder_name' => strtoupper($user->full_name),
                    'card_number_masked' => '**** **** **** ' . $stripeCard->last4,
                    'expiry_month' => str_pad($stripeCard->exp_month, 2, '0', STR_PAD_LEFT),
                    'expiry_year' => (string) $stripeCard->exp_year,
                    'balance' => 0,
                    'status' => CardStatus::ACTIVE,
                    'is_active' => true,
                    'daily_limit' => CardService::DAILY_LIMIT,
                    'monthly_limit' => CardService::MONTHLY_LIMIT,
                    'per_transaction_limit' => 200,
                    'online_enabled' => true,
                    'international_enabled' => true,
                    'contactless_enabled' => true,
                    'atm_enabled' => false, // Virtual cards usually can't use ATM
                    'provider_data' => [
                        'stripe_card_id' => $stripeCard->id,
                        'last4' => $stripeCard->last4,
                        'brand' => $stripeCard->brand,
                        'created_at' => now()->toIso8601String(),
                    ],
                ]);

                Log::info('Stripe virtual card issued', [
                    'user_id' => $user->id,
                    'card_id' => $localCard->id,
                    'stripe_card_id' => $stripeCard->id,
                ]);

                return [
                    'success' => true,
                    'card' => [
                        'id' => $localCard->id,
                        'uuid' => $localCard->uuid,
                        'last4' => $stripeCard->last4,
                        'brand' => $stripeCard->brand,
                        'expiry' => $localCard->expiry_month . '/' . substr($localCard->expiry_year, -2),
                        'status' => 'active',
                    ],
                    'message' => 'تم إصدار البطاقة بنجاح',
                ];
            } catch (ApiErrorException $e) {
                Log::error('Stripe card issuance failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => $this->translateStripeError($e),
                ];
            }
        });
    }

    /**
     * Get full card details (PAN, CVV) from Stripe
     * SECURITY: Only call when user explicitly requests and is authenticated
     */
    public function getCardDetails(VirtualCard $card): array
    {
        if ($card->provider !== 'stripe' || !$card->provider_card_id) {
            return ['success' => false, 'error' => 'البطاقة ليست من Stripe'];
        }

        try {
            // Retrieve card with full details
            // Note: This requires specific Stripe permissions
            $stripeCard = $this->stripe->issuing->cards->retrieve(
                $card->provider_card_id,
                ['expand' => ['number', 'cvc']]
            );

            return [
                'success' => true,
                'card' => [
                    'number' => $stripeCard->number ?? null,
                    'cvc' => $stripeCard->cvc ?? null,
                    'exp_month' => str_pad($stripeCard->exp_month, 2, '0', STR_PAD_LEFT),
                    'exp_year' => (string) $stripeCard->exp_year,
                    'cardholder_name' => $card->cardholder_name,
                ],
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve card details', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'فشل جلب تفاصيل البطاقة',
            ];
        }
    }

    // ==================== REAL-TIME AUTHORIZATION ====================

    /**
     * Handle issuing_authorization.request webhook
     * 
     * CRITICAL: Must respond within 2 seconds or auto-decline
     * 
     * @param array $authorization Stripe authorization object
     * @return array ['approved' => bool, 'reason' => string]
     */
    public function handleAuthorizationRequest(array $authorization): array
    {
        $startTime = microtime(true);
        $authId = $authorization['id'] ?? 'unknown';
        $cardId = $authorization['card']['id'] ?? null;
        $amount = $authorization['pending_request']['amount'] ?? 0;
        $currency = strtoupper($authorization['pending_request']['currency'] ?? 'USD');
        $merchantName = $authorization['merchant_data']['name'] ?? 'Unknown';
        $merchantCategory = $authorization['merchant_data']['category'] ?? 'unknown';

        Log::info('Processing authorization request', [
            'auth_id' => $authId,
            'card_id' => $cardId,
            'amount' => $amount,
            'currency' => $currency,
            'merchant' => $merchantName,
        ]);

        try {
            // Find local card
            $card = VirtualCard::where('provider_card_id', $cardId)
                ->where('provider', 'stripe')
                ->with(['user', 'wallet'])
                ->first();

            if (!$card) {
                return $this->declineAuthorization($authId, 'card_not_found', $startTime);
            }

            // Check card status
            if ($card->status !== CardStatus::ACTIVE || !$card->is_active) {
                return $this->declineAuthorization($authId, 'card_inactive', $startTime);
            }

            // Check if card is frozen
            if ($card->status === CardStatus::FROZEN) {
                return $this->declineAuthorization($authId, 'card_frozen', $startTime);
            }

            // Convert amount to dollars (Stripe sends cents)
            $amountDollars = $amount / 100;

            // Check wallet balance
            $wallet = $card->wallet;
            if (!$wallet || $wallet->available_balance < $amountDollars) {
                return $this->declineAuthorization($authId, 'insufficient_funds', $startTime);
            }

            // Check spending limits
            if (!$this->checkSpendingLimits($card, $amountDollars)) {
                return $this->declineAuthorization($authId, 'spending_limit_exceeded', $startTime);
            }

            // Check merchant category restrictions
            if (!$this->checkMerchantAllowed($card, $merchantCategory)) {
                return $this->declineAuthorization($authId, 'merchant_blocked', $startTime);
            }

            // Check international transactions
            if (!$card->international_enabled && $this->isInternationalTransaction($authorization)) {
                return $this->declineAuthorization($authId, 'international_disabled', $startTime);
            }

            // APPROVE - Reserve funds
            return DB::transaction(function () use ($card, $wallet, $amountDollars, $authId, $authorization, $startTime) {
                // Lock wallet for update
                $wallet = Wallet::lockForUpdate()->find($wallet->id);

                // Idempotency guard: this event type is exempt from the controller's
                // event-id dedup (must answer within Stripe's 2s window), so a Stripe
                // retry or signed-payload replay can reach here twice. Short-circuit
                // with the SAME approved decision instead of double-holding funds.
                // Checked under the wallet lock so two concurrent deliveries can't
                // both pass.
                $existingTx = Transaction::where('metadata->authorization_id', $authId)->first();
                if ($existingTx) {
                    $elapsed = (microtime(true) - $startTime) * 1000;
                    Log::info('Authorization replay detected, returning cached decision', [
                        'auth_id' => $authId,
                        'transaction_id' => $existingTx->id,
                        'elapsed_ms' => $elapsed,
                    ]);

                    return [
                        'approved' => true,
                        'transaction_id' => $existingTx->id,
                        'elapsed_ms' => $elapsed,
                        'idempotent_replay' => true,
                    ];
                }

                // Double-check balance under lock
                if ($wallet->available_balance < $amountDollars) {
                    return $this->declineAuthorization($authId, 'insufficient_funds', $startTime);
                }

                // Reserve funds (hold): available_balance -> pending_balance
                if (!$wallet->hold($amountDollars)) {
                    return $this->declineAuthorization($authId, 'insufficient_funds', $startTime);
                }

                // Create pending transaction
                $transaction = Transaction::create([
                    'user_id' => $card->user_id,
                    'wallet_id' => $wallet->id,
                    'card_id' => $card->id,
                    'type' => TransactionType::CARD_PAYMENT,
                    'category' => TransactionCategory::CARD,
                    'currency' => $wallet->currency,
                    'amount' => -$amountDollars,
                    'fee' => 0,
                    'net_amount' => -$amountDollars,
                    'balance_before' => $wallet->balance,
                    'balance_after' => $wallet->balance, // Not debited yet
                    'status' => TransactionStatus::PROCESSING,
                    'title' => 'Card Payment',
                    'description' => $authorization['merchant_data']['name'] ?? 'Card transaction',
                    'metadata' => [
                        'provider' => 'stripe',
                        'authorization_id' => $authId,
                        'merchant' => $authorization['merchant_data'] ?? [],
                        'stripe_card_id' => $card->provider_card_id,
                    ],
                ]);

                // Update card spending
                $card->increment('daily_spent', $amountDollars);
                $card->increment('monthly_spent', $amountDollars);

                $elapsed = (microtime(true) - $startTime) * 1000;
                Log::info('Authorization approved', [
                    'auth_id' => $authId,
                    'amount' => $amountDollars,
                    'elapsed_ms' => $elapsed,
                ]);

                return [
                    'approved' => true,
                    'transaction_id' => $transaction->id,
                    'elapsed_ms' => $elapsed,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Authorization error', [
                'auth_id' => $authId,
                'error' => $e->getMessage(),
            ]);

            return $this->declineAuthorization($authId, 'system_error', $startTime);
        }
    }

    /**
     * Handle authorization capture (final settlement)
     */
    public function handleAuthorizationCapture(array $authorization): array
    {
        $authId = $authorization['id'] ?? 'unknown';
        $cardId = $authorization['card']['id'] ?? null;
        $amount = $authorization['approved_amount'] ?? 0;
        $amountDollars = $amount / 100;

        return DB::transaction(function () use ($authId, $cardId, $amountDollars) {
            // Find the pending transaction
            $transaction = Transaction::where('metadata->authorization_id', $authId)
                ->where('status', TransactionStatus::PROCESSING)
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                Log::warning('No pending transaction for capture', ['auth_id' => $authId]);
                return ['success' => false, 'error' => 'Transaction not found'];
            }

            $wallet = Wallet::lockForUpdate()->find($transaction->wallet_id);

            // Settle: pending_balance -> spent (Wallet::capture debits balance and
            // clears the held amount from pending_balance in one atomic step).
            $wallet->capture($amountDollars);

            // Update transaction
            $transaction->update([
                'status' => TransactionStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            Log::info('Authorization captured', [
                'auth_id' => $authId,
                'amount' => $amountDollars,
                'transaction_id' => $transaction->id,
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
            ];
        });
    }

    /**
     * Handle authorization reversal (void/refund)
     */
    public function handleAuthorizationReversal(array $authorization): array
    {
        $authId = $authorization['id'] ?? 'unknown';
        $reversedAmount = $authorization['amount_reversed'] ?? 0;
        $amountDollars = $reversedAmount / 100;

        return DB::transaction(function () use ($authId, $amountDollars) {
            $transaction = Transaction::where('metadata->authorization_id', $authId)
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                return ['success' => false, 'error' => 'Transaction not found'];
            }

            // SEC H4: never refund the same authorization twice. If it is already
            // REFUNDED, a second reversal event (or replay) must not re-credit.
            if ($transaction->status === TransactionStatus::REFUNDED) {
                Log::info('Authorization already reversed, skipping', ['auth_id' => $authId]);
                return ['success' => true, 'already_reversed' => true];
            }

            $wallet = Wallet::lockForUpdate()->find($transaction->wallet_id);
            $card = VirtualCard::find($transaction->card_id);

            // If still processing (hold), release reserved funds back to available
            if ($transaction->status === TransactionStatus::PROCESSING) {
                $wallet->release(abs($transaction->amount));
            } else {
                // If completed, refund to wallet
                $wallet->credit($amountDollars);
            }

            // Reverse card spending
            if ($card) {
                $card->decrement('daily_spent', $amountDollars);
                $card->decrement('monthly_spent', $amountDollars);
            }

            $transaction->update([
                'status' => TransactionStatus::REFUNDED,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'reversed_at' => now()->toIso8601String(),
                    'reversed_amount' => $amountDollars,
                ]),
            ]);

            Log::info('Authorization reversed', [
                'auth_id' => $authId,
                'amount' => $amountDollars,
            ]);

            return [
                'success' => true,
                'refunded' => $amountDollars,
            ];
        });
    }

    // ==================== CARD LIFECYCLE ====================

    /**
     * Freeze card
     */
    public function freezeCard(VirtualCard $card): array
    {
        if ($card->provider !== 'stripe' || !$card->provider_card_id) {
            // Local card - just update status
            $card->forceFill(['status' => CardStatus::FROZEN, 'is_active' => false])->save();
            $this->logCardLifecycle('card.freeze', $card);
            return ['success' => true, 'message' => 'تم تجميد البطاقة'];
        }

        try {
            $this->stripe->issuing->cards->update(
                $card->provider_card_id,
                ['status' => 'inactive']
            );

            $card->forceFill(['status' => CardStatus::FROZEN, 'is_active' => false])->save();
            $this->logCardLifecycle('card.freeze', $card);

            return ['success' => true, 'message' => 'تم تجميد البطاقة'];
        } catch (ApiErrorException $e) {
            return ['success' => false, 'error' => $this->translateStripeError($e)];
        }
    }

    /**
     * Unfreeze card
     */
    public function unfreezeCard(VirtualCard $card): array
    {
        if ($card->provider !== 'stripe' || !$card->provider_card_id) {
            $card->forceFill(['status' => CardStatus::ACTIVE, 'is_active' => true])->save();
            $this->logCardLifecycle('card.unfreeze', $card);
            return ['success' => true, 'message' => 'تم تفعيل البطاقة'];
        }

        try {
            $this->stripe->issuing->cards->update(
                $card->provider_card_id,
                ['status' => 'active']
            );

            $card->forceFill(['status' => CardStatus::ACTIVE, 'is_active' => true])->save();
            $this->logCardLifecycle('card.unfreeze', $card);

            return ['success' => true, 'message' => 'تم تفعيل البطاقة'];
        } catch (ApiErrorException $e) {
            return ['success' => false, 'error' => $this->translateStripeError($e)];
        }
    }

    /**
     * Cancel card permanently
     */
    public function cancelCard(VirtualCard $card, Wallet $wallet): array
    {
        return DB::transaction(function () use ($card, $wallet) {
            if ($card->provider === 'stripe' && $card->provider_card_id) {
                try {
                    $this->stripe->issuing->cards->update(
                        $card->provider_card_id,
                        ['status' => 'canceled']
                    );
                } catch (ApiErrorException $e) {
                    Log::warning('Failed to cancel Stripe card', [
                        'card_id' => $card->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Refund any remaining balance
            $refunded = 0;
            if ($card->balance > 0) {
                $wallet->credit($card->balance);
                $refunded = $card->balance;

                Transaction::create([
                    'user_id' => $card->user_id,
                    'wallet_id' => $wallet->id,
                    'card_id' => $card->id,
                    'type' => TransactionType::CARD_UNLOAD,
                    'category' => TransactionCategory::CARD,
                    'currency' => $wallet->currency,
                    'amount' => $refunded,
                    'status' => TransactionStatus::COMPLETED,
                    'title' => 'Card Cancellation Refund',
                    'completed_at' => now(),
                ]);
            }

            $card->forceFill([
                'status' => CardStatus::CANCELLED,
                'is_active' => false,
                'balance' => 0,
            ])->save();

            $this->logCardLifecycle('card.cancel', $card, ['refunded' => $refunded]);

            return [
                'success' => true,
                'refunded' => $refunded,
                'message' => 'تم إلغاء البطاقة',
            ];
        });
    }

    /**
     * Audit-log a card lifecycle action (freeze/unfreeze/cancel), mirroring
     * the pattern already used by CardService::createCard/loadCard/unloadCard.
     */
    protected function logCardLifecycle(string $action, VirtualCard $card, array $extra = []): void
    {
        ActivityLog::log(
            $action,
            user: $card->user,
            entity: $card,
            newValues: array_merge([
                'card_id' => $card->id,
                'provider' => $card->provider,
            ], $extra),
            description: "{$action} — card {$card->id} (provider: " . ($card->provider ?? 'local') . ')'
        );
    }

    // ==================== WEBHOOK VERIFICATION ====================

    /**
     * Verify Stripe webhook signature
     *
     * Implements direct HMAC verification instead of relying on the
     * stripe/stripe-php SDK so the service works without the package.
     * The Stripe webhook signature format is:
     *   t={timestamp},v1={hmac}
     * where hmac = HMAC-SHA256("{timestamp}.{payload}", secret)
     * with a default tolerance of 300 seconds against replay attacks.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            Log::warning('Stripe webhook secret not configured');
            return false;
        }

        // Parse the signature header: t={ts},v1={sig}[,v1={sig2}...]
        $parts = explode(',', $signature);
        $timestamp = null;
        $expectedSig = null;

        foreach ($parts as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) !== 2) {
                continue;
            }
            if ($pair[0] === 't') {
                $timestamp = (int) $pair[1];
            } elseif ($pair[0] === 'v1') {
                $expectedSig = $pair[1];
            }
        }

        if ($timestamp === null || $expectedSig === null) {
            Log::warning('Stripe webhook signature: missing timestamp or signature');
            return false;
        }

        // Reject timestamps older than 300 seconds (Stripe's default tolerance).
        if (abs(time() - $timestamp) > 300) {
            Log::warning('Stripe webhook signature: timestamp outside tolerance', [
                'timestamp' => $timestamp,
                'now' => time(),
            ]);
            return false;
        }

        // Compute the expected HMAC.
        $signedPayload = $timestamp . '.' . $payload;
        $computed = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        // Constant-time comparison.
        if (hash_equals($computed, $expectedSig)) {
            return true;
        }

        Log::warning('Stripe webhook signature: HMAC mismatch');
        return false;
    }

    // ==================== HELPER METHODS ====================

    protected function declineAuthorization(string $authId, string $reason, float $startTime): array
    {
        $elapsed = (microtime(true) - $startTime) * 1000;

        Log::info('Authorization declined', [
            'auth_id' => $authId,
            'reason' => $reason,
            'elapsed_ms' => $elapsed,
        ]);

        return [
            'approved' => false,
            'reason' => $reason,
            'elapsed_ms' => $elapsed,
        ];
    }

    protected function checkSpendingLimits(VirtualCard $card, float $amount): bool
    {
        // Reset limits if needed
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();

        if ($card->daily_reset_at?->toDateString() !== $today) {
            $card->forceFill(['daily_spent' => 0, 'daily_reset_at' => $today])->save();
        }

        if ($card->monthly_reset_at?->toDateString() !== $thisMonth) {
            $card->forceFill(['monthly_spent' => 0, 'monthly_reset_at' => $thisMonth])->save();
        }

        // Check limits
        if ($amount > $card->per_transaction_limit) {
            return false;
        }

        if (($card->daily_spent + $amount) > $card->daily_limit) {
            return false;
        }

        if (($card->monthly_spent + $amount) > $card->monthly_limit) {
            return false;
        }

        return true;
    }

    protected function checkMerchantAllowed(VirtualCard $card, string $category): bool
    {
        // Blocked categories
        $blocked = [
            'adult_entertainment_stores',
            'gambling',
            'money_transfer',
            'wire_transfer',
        ];

        return !in_array($category, $blocked);
    }

    protected function isInternationalTransaction(array $authorization): bool
    {
        $merchantCountry = $authorization['merchant_data']['country'] ?? 'US';
        return $merchantCountry !== 'US';
    }

    protected function translateStripeError(ApiErrorException $e): string
    {
        $code = $e->getStripeCode() ?? '';

        return match ($code) {
            'card_declined' => 'تم رفض البطاقة',
            'insufficient_funds' => 'رصيد غير كافٍ',
            'invalid_cvc' => 'رمز CVV غير صحيح',
            'expired_card' => 'البطاقة منتهية الصلاحية',
            'processing_error' => 'خطأ في المعالجة، حاول مرة أخرى',
            default => 'خطأ: ' . $e->getMessage(),
        };
    }
}
