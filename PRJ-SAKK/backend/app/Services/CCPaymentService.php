<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Enums\TransactionType;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CCPayment Service - Cryptocurrency Payment Gateway Integration
 * 
 * Handles deposits, withdrawals, and balance queries via CCPayment API v2.
 * Base URL: https://ccpayment.com/ccpayment/v2/
 * Authentication: HMAC-SHA256 signature with AppID + Timestamp + Body
 */
class CCPaymentService
{
    protected ?Integration $integration = null;
    protected string $baseUrl = 'https://ccpayment.com/ccpayment/v2';
    protected string $appId = '';
    protected string $appSecret = '';
    protected bool $isActive = false;

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Load configuration from Integration model or env fallback.
     *
     * SEV-4 fail-closed kill-switch: when the Integration row EXISTS and is
     * explicitly is_active=false, the admin has turned this gateway OFF —
     * do NOT fall through to env/config credentials, or a stray .env secret
     * would silently resurrect a money-moving gateway behind the admin's
     * back. Env fallback stays available ONLY when the row is absent
     * entirely (no row = no admin opinion, existing bootstrap behavior).
     */
    protected function loadConfig(): void
    {
        $this->integration = Integration::where('key', 'ccpayment')->first();

        if ($this->integration && !$this->integration->is_active) {
            // Admin-toggled OFF. Hard-disable — never read env creds.
            $this->appId = '';
            $this->appSecret = '';
            $this->isActive = false;
            return;
        }

        if ($this->integration && $this->integration->is_active) {
            $this->appId = $this->integration->getCredential('app_id') ?? $this->integration->getCredential('api_key') ?? '';
            $this->appSecret = $this->integration->getCredential('app_secret') ?? $this->integration->getCredential('api_secret') ?? '';
            $this->isActive = !empty($this->appId) && !empty($this->appSecret);
        } else {
            // No row at all -> fallback to env (unchanged behavior).
            $this->appId = config('services.ccpayment.app_id') ?? '';
            $this->appSecret = config('services.ccpayment.app_secret') ?? '';
            $this->isActive = !empty($this->appId) && !empty($this->appSecret);
        }
    }

    /**
     * Check if service is configured and active
     */
    public function isActive(): bool
    {
        return $this->isActive && !empty($this->appId) && !empty($this->appSecret);
    }

    /**
     * Generate HMAC-SHA256 signature
     * Signature = HMAC-SHA256(AppID + Timestamp + Body, AppSecret)
     */
    protected function generateSign(string $body): array
    {
        $timestamp = (string) (time() * 1000); // Millisecond timestamp
        $signText = $this->appId . $timestamp . $body;
        
        $sign = hash_hmac('sha256', $signText, $this->appSecret);
        
        return [
            'sign' => $sign,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Make authenticated POST request to CCPayment API
     */
    protected function request(string $endpoint, array $data = []): array
    {
        if (!$this->isActive()) {
            throw new \RuntimeException('CCPayment غير مُكوّن أو غير نشط');
        }

        $body = empty($data) ? '{}' : json_encode($data);
        $signature = $this->generateSign($body);

        // Send the EXACT signed bytes as the raw body. Passing $data to ->post()
        // would let Guzzle re-encode it (e.g. [] -> "[]" while we signed "{}"),
        // producing a body that no longer matches the signature => 11005 VerifySignFailed.
        $response = Http::withHeaders([
            'Appid' => $this->appId,
            'Sign' => $signature['sign'],
            'Timestamp' => $signature['timestamp'],
        ])->withBody($body, 'application/json')->post($this->baseUrl . $endpoint);

        if ($response->failed()) {
            Log::error('CCPayment API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('فشل الاتصال بـ CCPayment: ' . $response->status());
        }

        $result = $response->json();

        if (($result['code'] ?? 0) !== 10000) {
            Log::error('CCPayment API Business Error', [
                'endpoint' => $endpoint,
                'code' => $result['code'] ?? 'unknown',
                'message' => $result['msg'] ?? 'خطأ غير معروف',
            ]);
            throw new \RuntimeException('خطأ CCPayment: ' . ($result['msg'] ?? 'كود ' . ($result['code'] ?? 'unknown')));
        }

        return $result['data'] ?? [];
    }

    // ==================== DEPOSIT ====================

    /**
     * Create a deposit address for an order
     * POST /createAppOrderDepositAddress
     */
    public function createDepositAddress(array $params): array
    {
        $validated = validator($params, [
            'orderId' => 'required|string|min:3|max:64',
            'coinId' => 'required|integer|min:1',
            'chain' => 'required|string|min:1',
            'price' => 'required|string|min:1',
            'fiatId' => 'nullable|integer',
            'expiredAt' => 'nullable|integer',
            'buyerEmail' => 'nullable|email',
            'generateCheckoutURL' => 'nullable|boolean',
            'product' => 'nullable|string|max:120',
            'returnUrl' => 'nullable|url|max:150',
            'notifyUrl' => 'nullable|url|max:150',
            'closeUrl' => 'nullable|url|max:150',
        ])->validate();

        return $this->request('/createAppOrderDepositAddress', $validated);
    }

    /**
     * Get or create a direct deposit address
     * POST /getOrCreateAppDepositAddress
     */
    public function getOrCreateDepositAddress(string $referenceId, string $chain, ?string $notifyUrl = null): array
    {
        $data = [
            'referenceId' => $referenceId,
            'chain' => $chain,
        ];

        if ($notifyUrl) {
            $data['notifyUrl'] = $notifyUrl;
        }

        return $this->request('/getOrCreateAppDepositAddress', $data);
    }

    /**
     * Query a single deposit record
     * POST /getAppDepositRecord
     */
    public function getDepositRecord(string $recordId): array
    {
        $result = $this->request('/getAppDepositRecord', [
            'recordId' => $recordId,
        ]);

        return $result['record'] ?? [];
    }

    /**
     * Query deposit record list
     * POST /getAppDepositRecordList
     */
    public function getDepositRecords(array $filters = []): array
    {
        $data = array_intersect_key($filters, array_flip([
            'chain', 'referenceId', 'orderId', 'toAddress', 'coinId',
            'startAt', 'endAt', 'nextId', 'recordIds', 'referenceIds', 'orderIds', 'limit'
        ]));

        return $this->request('/getAppDepositRecordList', $data);
    }

    // ==================== WITHDRAWAL ====================

    /**
     * Get withdrawal fee
     * POST /getWithdrawFee
     */
    public function getWithdrawFee(int $coinId, string $chain): array
    {
        $result = $this->request('/getWithdrawFee', [
            'coinId' => $coinId,
            'chain' => $chain,
        ]);

        return $result['fee'] ?? [];
    }

    /**
     * Withdraw to external network address
     * POST /applyAppWithdrawToNetwork
     */
    public function withdrawToNetwork(array $params): array
    {
        $validated = validator($params, [
            'orderId' => 'required|string|min:3|max:64',
            'coinId' => 'required|integer|min:1',
            'chain' => 'required|string|min:1',
            'address' => 'required|string|min:1',
            'amount' => 'required|string|min:1',
            'memo' => 'nullable|string',
            'merchantPayNetworkFee' => 'nullable|boolean',
            'networkFeeInquiryID' => 'nullable|string',
            'notifyUrl' => 'nullable|url|max:150',
        ])->validate();

        return $this->request('/applyAppWithdrawToNetwork', $validated);
    }

    /**
     * Withdraw to CWallet user
     * POST /applyAppWithdrawToCwallet
     */
    public function withdrawToCwallet(array $params): array
    {
        $validated = validator($params, [
            'orderId' => 'required|string|min:3|max:64',
            'coinId' => 'required|integer|min:1',
            'cwalletUser' => 'required|string|min:1',
            'amount' => 'required|string|min:1',
        ])->validate();

        return $this->request('/applyAppWithdrawToCwallet', $validated);
    }

    /**
     * Query a single withdrawal record
     * POST /getAppWithdrawRecord
     */
    public function getWithdrawRecord(?string $orderId = null, ?string $recordId = null): array
    {
        $data = [];
        if ($orderId) $data['orderId'] = $orderId;
        if ($recordId) $data['recordId'] = $recordId;

        $result = $this->request('/getAppWithdrawRecord', $data);
        return $result['record'] ?? [];
    }

    /**
     * Query withdrawal record list
     * POST /getAppWithdrawRecordList
     */
    public function getWithdrawRecords(array $filters = []): array
    {
        $data = array_intersect_key($filters, array_flip([
            'chain', 'coinId', 'orderIds', 'startAt', 'endAt', 'toAddress', 'nextId'
        ]));

        return $this->request('/getAppWithdrawRecordList', $data);
    }

    // ==================== ASSETS ====================

    /**
     * Get merchant coin asset list
     * POST /getAppCoinAssetList
     */
    public function getAssetList(): array
    {
        return $this->request('/getAppCoinAssetList', []);
    }

    /**
     * Get single coin asset details
     * POST /getAppCoinAsset
     */
    public function getAsset(int $coinId): array
    {
        $result = $this->request('/getAppCoinAsset', [
            'coinId' => $coinId,
        ]);

        return $result['asset'] ?? [];
    }

    // ==================== SAKK INTEGRATION ====================

    /**
     * Create a crypto deposit for a Sarva wallet user
     */
    public function createWalletDeposit(Wallet $wallet, string $chain, string $currency = 'USDT'): array
    {
        // Deterministic, REUSABLE reference per (user, wallet, chain). Re-opening the
        // deposit screen now returns the SAME address instead of churning a new one,
        // and — crucially — creates NO transaction row. A crypto deposit only becomes
        // a transaction when money actually arrives (handleDepositWebhook creates it).
        // This kills the "معاملة معلقة 0" placeholder rows that used to pile up every
        // time a user opened the deposit screen and left without paying.
        $ccChain = $this->ccChain($chain);
        $referenceId = 'sarva_' . $wallet->user_id . '_' . $wallet->id . '_' . $ccChain;

        $result = $this->getOrCreateDepositAddress(
            $referenceId,
            $ccChain,
            route('webhooks.ccpayment.deposit', [], true)
        );

        return [
            'address' => $result['address'] ?? null,
            'memo' => $result['memo'] ?? null,
            'reference_id' => $referenceId,
        ];
    }

    /**
     * Process a crypto withdrawal from a Sarva wallet.
     *
     * Convenience wrapper kept for direct/manual callers (e.g. tinker, admin
     * tooling). It performs the fee lookup, the gateway call, AND creates the
     * Transaction row in one go — i.e. it holds no wallet lock itself, but a
     * caller wrapping it in a DB transaction with the wallet locked would hold
     * that lock across the external HTTP call.
     *
     * The user-facing API controller does NOT use this method for that reason:
     * it debits + creates the Transaction under a short lock first (Phase A),
     * then calls {@see dispatchWithdrawToGateway()} directly, outside any lock
     * (Phase B). See CCPaymentController::withdraw().
     */
    public function processWalletWithdraw(
        Wallet $wallet,
        string $address,
        string $amount,
        string $chain,
        string $currency = 'USDT',
        ?string $memo = null
    ): array {
        $orderId = 'sarva_wd_' . Str::random(12);

        $result = $this->dispatchWithdrawToGateway($orderId, $address, $amount, $chain, $currency, $memo);

        // Create transaction record
        Transaction::create([
            'user_id' => $wallet->user_id,
            'wallet_id' => $wallet->id,
            'type' => TransactionType::WITHDRAWAL,
            'category' => TransactionCategory::CRYPTO,
            'status' => TransactionStatus::PENDING,
            'amount' => $amount,
            'title' => 'سحب كريبتو',
            'currency' => $currency,
            'reference' => $orderId,
            'description' => 'سحب CCPayment - ' . $chain,
            'metadata' => [
                'ccpayment_record_id' => $result['record_id'],
                'to_address' => $address,
                'chain' => $chain,
                'fee' => $result['fee'],
                'gateway_dispatched' => true,
            ],
        ]);

        return $result;
    }

    /**
     * Call the CCPayment gateway to dispatch a network withdrawal. Pure
     * gateway I/O — no DB writes, no wallet lock. Generates the orderId if
     * the caller does not already have one reserved (the API controller
     * reserves it in Phase A alongside the debit + Transaction row, then
     * passes it in here for Phase B).
     *
     * @return array{record_id: ?string, order_id: string, fee: array}
     */
    public function dispatchWithdrawToGateway(
        ?string $orderId,
        string $address,
        string $amount,
        string $chain,
        string $currency = 'USDT',
        ?string $memo = null
    ): array {
        $orderId ??= 'sarva_wd_' . Str::random(12);
        $coinId = $this->getCoinId($currency, $chain);
        $ccChain = $this->ccChain($chain);

        // Get fee estimate
        $fee = $this->getWithdrawFee($coinId, $ccChain);

        // Create withdrawal request (external HTTP call — no wallet lock held here)
        $result = $this->withdrawToNetwork([
            'orderId' => $orderId,
            'coinId' => $coinId,
            'chain' => $ccChain,
            'address' => $address,
            'amount' => $amount,
            'memo' => $memo,
            'notifyUrl' => route('webhooks.ccpayment.withdraw', [], true),
        ]);

        return [
            'record_id' => $result['recordId'] ?? null,
            'order_id' => $orderId,
            'fee' => $fee,
        ];
    }

    /**
     * Handle deposit webhook callback.
     *
     * CCPayment delivers the real DirectDeposit event nested under "msg" and with
     * a Capitalised status that carries NO amount, e.g.:
     *   {"type":"DirectDeposit","msg":{"recordId":"...","referenceId":"...","status":"Success"}}
     * Older/test callers post the fields flat with a lowercase status + amount.
     * This handler accepts both shapes, normalises the status case-insensitively,
     * and resolves the authoritative amount from the deposit record when the
     * payload omits it (otherwise we would credit 0 silently — Wallet::credit()
     * rejects non-positive amounts).
     */
    public function handleDepositWebhook(array $payload): void
    {
        Log::info('CCPayment Deposit Webhook', $payload);

        $data = $this->unwrapWebhook($payload);

        $recordId = $data['recordId'] ?? null;
        $referenceId = $data['referenceId'] ?? null;
        $rawStatus = (string) ($data['status'] ?? '');

        if (!$recordId || !$referenceId) {
            Log::warning('CCPayment webhook missing required fields', ['payload' => $payload]);
            return;
        }

        $newStatus = $this->mapDepositStatus($rawStatus);

        // SEC H3: for a COMPLETED credit, resolve the amount AUTHORITATIVELY from
        // the provider (getDepositRecord) and do NOT trust the webhook body amount —
        // a signed-but-tampered or test-shaped body must not control how much we
        // credit. The body amount is used only as a fallback when the provider API
        // is unavailable (e.g. local/test where the service is inactive), and that
        // fallback is logged. The real DirectDeposit webhook carries no amount anyway.
        $bodyAmount = $this->extractAmount($data);
        $amount = $bodyAmount;
        if ($newStatus === TransactionStatus::COMPLETED) {
            $authoritative = $this->fetchDepositAmount($recordId);
            if ($authoritative > 0) {
                if ($bodyAmount > 0 && abs($authoritative - $bodyAmount) > 0.00001) {
                    Log::warning('CCPayment webhook amount mismatch — using authoritative provider amount', [
                        'record' => $recordId,
                        'body_amount' => $bodyAmount,
                        'authoritative' => $authoritative,
                    ]);
                }
                $amount = $authoritative;
            } else {
                Log::warning('CCPayment webhook: authoritative amount unavailable, falling back to body amount (signature already verified)', [
                    'record' => $recordId,
                    'body_amount' => $bodyAmount,
                ]);
            }
        }

        // Use DB transaction with lockForUpdate to prevent idempotency bugs.
        // The transaction row is created HERE, on arrival — a deposit address never
        // pre-creates one — and idempotency is keyed on the per-deposit recordId so a
        // single reusable address can receive many deposits, each its own row.
        DB::transaction(function () use ($recordId, $referenceId, $rawStatus, $newStatus, $amount, $data) {
            // Each deposit row is keyed by its own CCPayment recordId in `reference`
            // (that column is UNIQUE), so one reusable address can receive many
            // deposits — each its own row. The address referenceId is kept in metadata
            // for grouping/owner resolution.

            // 1. Already recorded THIS deposit event? Then it is a status transition
            //    (e.g. Processing -> Success) or a duplicate callback. Fresh rows key
            //    `reference` on the recordId; claimed legacy placeholders carry it in
            //    metadata — the metadata lookup catches both.
            $tx = Transaction::where('metadata->ccpayment_record_id', $recordId)
                ->lockForUpdate()
                ->first();

            if ($tx) {
                // 🔒 Idempotency: identical status already applied -> nothing to do.
                if ((string) $tx->status->value === (string) $newStatus->value) {
                    Log::info('CCPayment webhook: Already processed, skipping', [
                        'reference' => $referenceId,
                        'record' => $recordId,
                        'status' => $newStatus->value,
                    ]);
                    return;
                }
            } else {
                // 2. First sighting of this recordId. Claim an unfunded placeholder
                //    left by an older build (keyed on the address referenceId) if one
                //    exists; otherwise this is a fresh deposit event we create now.
                //    `reference` is unique, so there is at most one such row.
                $placeholder = Transaction::where('reference', $referenceId)
                    ->where('status', TransactionStatus::PENDING)
                    ->lockForUpdate()
                    ->first();

                if ($placeholder && empty($placeholder->metadata['ccpayment_record_id'])) {
                    $tx = $placeholder;
                } else {
                    [$userId, $walletId] = $this->resolveDepositOwner($referenceId);

                    if (!$userId || !$walletId) {
                        Log::warning('CCPayment webhook: cannot resolve deposit owner', [
                            'reference' => $referenceId,
                            'record' => $recordId,
                        ]);
                        return;
                    }

                    $tx = new Transaction([
                        'user_id' => $userId,
                        'wallet_id' => $walletId,
                        'type' => TransactionType::DEPOSIT,
                        'category' => TransactionCategory::CRYPTO,
                        'currency' => 'USDT',
                        'reference' => $recordId,
                        'title' => 'إيداع كريبتو',
                        'description' => 'إيداع CCPayment',
                        'amount' => 0,
                    ]);
                }
            }

            // Apply the event (claimed placeholder, fresh row, or status transition).
            $tx->status = $newStatus;
            if ($amount > 0) {
                $tx->amount = $amount;
            }
            $tx->metadata = array_merge($tx->metadata ?? [], [
                'ccpayment_record_id' => $recordId,
                'ccpayment_reference_id' => $referenceId,
                'ccpayment_status' => $rawStatus,
                'tx_id' => $data['txId'] ?? null,
                'from_address' => $data['fromAddress'] ?? null,
                'confirmed_at' => now(),
            ]);
            $tx->save();

            // Credit wallet if successful (only once — the recordId idempotency guard
            // above blocks repeat Success callbacks for the same deposit).
            if ($newStatus === TransactionStatus::COMPLETED) {
                $creditAmount = $amount > 0 ? $amount : (float) $tx->amount;
                $wallet = Wallet::lockForUpdate()->find($tx->wallet_id);

                if ($wallet && $creditAmount > 0) {
                    $wallet->credit($creditAmount);
                } else {
                    // Money arrived but we could not resolve an amount — do NOT mark
                    // completed silently; surface it so it is reconciled, not lost.
                    Log::error('CCPayment deposit completed but amount unresolved — not credited', [
                        'reference' => $referenceId,
                        'record' => $recordId,
                        'wallet_id' => $tx->wallet_id,
                    ]);
                    throw new \RuntimeException('CCPayment deposit amount unresolved for ' . $referenceId);
                }
            }
        });
    }

    /**
     * Resolve the (user_id, wallet_id) that owns a deposit address referenceId.
     *
     * Prefers a sibling row already recorded under the same address ref — either a
     * legacy placeholder keyed on the referenceId, or an earlier deposit grouped by
     * `metadata.ccpayment_reference_id` (covers legacy random-suffix refs whose owner
     * can't be parsed, and repeat deposits to a reusable address). Falls back to
     * parsing the deterministic format `sarva_<userId>_<walletId>_<chain>`, validating
     * the wallet belongs to the user. Returns [null, null] when owner can't be trusted.
     */
    private function resolveDepositOwner(string $referenceId): array
    {
        $sibling = Transaction::where('reference', $referenceId)->first()
            ?? Transaction::where('metadata->ccpayment_reference_id', $referenceId)->first();

        if ($sibling) {
            return [$sibling->user_id, $sibling->wallet_id];
        }

        $parts = explode('_', $referenceId);
        if (count($parts) >= 4 && $parts[0] === 'sarva' && ctype_digit($parts[1]) && ctype_digit($parts[2])) {
            $userId = (int) $parts[1];
            $walletId = (int) $parts[2];

            $wallet = Wallet::where('id', $walletId)->where('user_id', $userId)->first();
            if ($wallet) {
                return [$userId, $walletId];
            }
        }

        return [null, null];
    }

    /**
     * Unwrap CCPayment's {type, msg:{...}} envelope to the flat event fields.
     * Falls back to the payload itself for flat/test callers.
     */
    private function unwrapWebhook(array $payload): array
    {
        if (isset($payload['msg']) && is_array($payload['msg']) && $payload['msg'] !== []) {
            return $payload['msg'];
        }

        return $payload;
    }

    /**
     * Map a CCPayment deposit status (any case: "Success"/"Processing"/...) to a
     * TransactionStatus. Unknown/in-flight states stay PENDING.
     */
    private function mapDepositStatus(string $status): TransactionStatus
    {
        return match (strtolower(trim($status))) {
            'success', 'completed' => TransactionStatus::COMPLETED,
            'failed', 'cancelled', 'canceled' => TransactionStatus::FAILED,
            default => TransactionStatus::PENDING, // e.g. "Processing"
        };
    }

    /**
     * Pull a numeric amount out of a webhook/record payload under any of the
     * field names CCPayment uses across its shapes.
     */
    private function extractAmount(array $data): float
    {
        foreach (['amount', 'depositAmount', 'value'] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (float) $data[$key];
            }
        }

        return 0.0;
    }

    /**
     * Fetch the authoritative deposit amount from CCPayment by record id.
     * Returns 0.0 if the service is inactive or the lookup fails — the caller
     * treats 0 as "unresolved" and refuses to credit.
     */
    private function fetchDepositAmount(string $recordId): float
    {
        if (!$this->isActive()) {
            Log::warning('CCPayment fetchDepositAmount: service inactive', ['record' => $recordId]);
            return 0.0;
        }

        try {
            $record = $this->getDepositRecord($recordId);
            return $this->extractAmount($record);
        } catch (\Throwable $e) {
            Log::error('CCPayment fetchDepositAmount failed', [
                'record' => $recordId,
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    /**
     * Handle withdrawal webhook callback
     */
    public function handleWithdrawWebhook(array $payload): void
    {
        Log::info('CCPayment Withdraw Webhook', $payload);

        $data = $this->unwrapWebhook($payload);

        $orderId = $data['orderId'] ?? $data['referenceId'] ?? null;
        $rawStatus = (string) ($data['status'] ?? '');

        if (!$orderId) {
            Log::warning('CCPayment withdraw webhook missing orderId', ['payload' => $payload]);
            return;
        }

        DB::transaction(function () use ($orderId, $rawStatus, $data) {
            $transaction = Transaction::where('reference', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                Log::warning('CCPayment withdraw webhook: Transaction not found', ['orderId' => $orderId]);
                return;
            }

            // 🔒 Idempotency guard: skip if already in this final status
            $newStatus = match (strtolower(trim($rawStatus))) {
                'success', 'completed' => TransactionStatus::COMPLETED,
                'failed', 'cancelled', 'canceled', 'rejected' => TransactionStatus::FAILED,
                default => TransactionStatus::PENDING,
            };

            if ((string) $transaction->status->value === (string) $newStatus->value) {
                Log::info('CCPayment withdraw webhook: Already processed, skipping', [
                    'reference' => $orderId,
                    'status' => $newStatus->value,
                ]);
                return;
            }

            $transaction->update([
                'status' => $newStatus,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'ccpayment_status' => $rawStatus,
                    'tx_id' => $data['txId'] ?? null,
                    'completed_at' => now(),
                ]),
            ]);

            // If failed, refund the wallet (only once due to idempotency guard)
            if ($newStatus === TransactionStatus::FAILED) {
                $wallet = Wallet::lockForUpdate()->find($transaction->wallet_id);
                if ($wallet) {
                    $wallet->credit($transaction->amount);
                    Log::info('CCPayment withdraw failed, refunded wallet', [
                        'wallet_id' => $wallet->id,
                        'amount' => $transaction->amount,
                    ]);
                }
            }
        });
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $body, string $sign, string $timestamp): bool
    {
        // SEC C4: FAIL CLOSED. With no configured secret the HMAC key would be ''
        // and an attacker could compute a matching signature themselves — forging
        // a deposit that credits a wallet. Refuse to verify unless a real secret
        // and both signed inputs are present.
        if ($this->appSecret === '' || $sign === '' || $timestamp === '') {
            return false;
        }

        $expectedSign = hash_hmac('sha256', $this->appId . $timestamp . $body, $this->appSecret);
        return hash_equals($expectedSign, $sign);
    }

    /**
     * Verify webhook IP whitelist
     * Returns true if IP is allowed or if no whitelist is configured
     */
    public function verifyWebhookIp(string $ip): bool
    {
        $whitelist = $this->integration?->settings['ip_whitelist'] ?? 
                     config('services.ccpayment.ip_whitelist', '');
        
        // If no whitelist configured or debug mode is on, allow all
        $debugMode = $this->integration?->settings['debug_mode'] ?? 
                     config('services.ccpayment.debug_mode', false);
        
        if (empty($whitelist) || $debugMode === true || $debugMode === 'true') {
            Log::info('CCPayment webhook IP check skipped', ['ip' => $ip, 'reason' => 'no_whitelist_or_debug']);
            return true;
        }
        
        $allowedIps = array_map('trim', explode(',', $whitelist));
        
        // Support CIDR notation (e.g., 192.168.1.0/24)
        foreach ($allowedIps as $allowedIp) {
            if ($this->ipInRange($ip, $allowedIp)) {
                Log::info('CCPayment webhook IP allowed', ['ip' => $ip, 'matched' => $allowedIp]);
                return true;
            }
        }
        
        Log::warning('CCPayment webhook IP rejected', ['ip' => $ip, 'allowed' => $allowedIps]);
        return false;
    }

    /**
     * Check if IP is in range (supports CIDR notation)
     */
    private function ipInRange(string $ip, string $range): bool
    {
        // Exact match
        if ($ip === $range) {
            return true;
        }
        
        // CIDR notation
        if (strpos($range, '/') !== false) {
            list($subnet, $bits) = explode('/', $range);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            return ($ip & $mask) === $subnet;
        }
        
        return false;
    }

    /**
     * Generate test webhook payload for development/testing
     */
    public function generateTestWebhookPayload(string $type, array $overrides = []): array
    {
        $timestamp = (string) (time() * 1000);
        
        $basePayload = match ($type) {
            'deposit' => [
                'recordId' => 'test_rec_' . Str::random(8),
                'referenceId' => $overrides['referenceId'] ?? 'test_ref_' . Str::random(8),
                'status' => $overrides['status'] ?? 'success',
                'amount' => $overrides['amount'] ?? '100.00',
                'txId' => 'test_tx_' . Str::random(8),
                'fromAddress' => 'test_address_' . Str::random(8),
                'coinId' => $overrides['coinId'] ?? 1280,
                'chain' => $overrides['chain'] ?? 'TRC20',
                'confirmations' => 6,
            ],
            'withdraw' => [
                'orderId' => $overrides['orderId'] ?? 'test_order_' . Str::random(8),
                'status' => $overrides['status'] ?? 'success',
                'txId' => 'test_tx_' . Str::random(8),
                'toAddress' => 'test_address_' . Str::random(8),
                'coinId' => $overrides['coinId'] ?? 1280,
                'chain' => $overrides['chain'] ?? 'TRC20',
                'amount' => $overrides['amount'] ?? '50.00',
                'fee' => '1.00',
            ],
            default => [],
        };
        
        // Merge with overrides
        $payload = array_merge($basePayload, $overrides);
        
        // Generate signature
        $body = json_encode($payload);
        $sign = hash_hmac('sha256', $this->appId . $timestamp . $body, $this->appSecret);
        
        return [
            'payload' => $payload,
            'headers' => [
                'Sign' => $sign,
                'Timestamp' => $timestamp,
                'Appid' => $this->appId,
            ],
        ];
    }

    /**
     * Resolve a CCPayment coinId from a coin symbol.
     *
     * CCPayment v2 assigns ONE coinId per coin (e.g. USDT = 1280) — the network
     * is a SEPARATE `chain` parameter, never folded into the coinId. The previous
     * implementation varied the id per chain with CoinMarketCap ids (ERC20 => 1,
     * BEP20 => 1027), which CCPayment rejects with 13000 "unsupported coin". The
     * authoritative ids come from /getCoinList; this merchant currently has only
     * USDT (1280) enabled. The $chain arg is accepted for backward-compat and
     * intentionally ignored.
     */
    public function getCoinId(string $symbol, ?string $chain = null): int
    {
        $coinMap = [
            'USDT' => 1280,
        ];

        return $coinMap[strtoupper($symbol)] ?? 1280; // default USDT (only enabled coin)
    }

    /**
     * Map the app-facing network code (TRC20/ERC20/BEP20/BTC) to CCPayment's
     * chain symbol (TRX/ETH/BSC/BTC). CCPayment rejects the network codes with
     * 13001 "unsupported network for this token" — its identifiers are the
     * chain symbols returned by /getChainList.
     */
    public function ccChain(string $chain): string
    {
        $map = [
            'TRC20' => 'TRX',
            'ERC20' => 'ETH',
            'BEP20' => 'BSC',
            'BTC'   => 'BTC',
        ];

        // Already a CCPayment chain symbol? pass through; else map, else upper-case as-is.
        return $map[strtoupper($chain)] ?? strtoupper($chain);
    }
}
