<?php

namespace App\Http\Controllers\API;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\CCPaymentService;
use App\Services\WalletService;
use App\Support\LedgerHaltGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CCPaymentController extends Controller
{
    private CCPaymentService $ccpayment;

    public function __construct(CCPaymentService $ccpayment)
    {
        $this->ccpayment = $ccpayment;
    }

    /**
     * Get CCPayment configuration
     */
    public function getConfig(): JsonResponse
    {
        $isActive = $this->ccpayment->isActive();
        
        return response()->json([
            'success' => true,
            'data' => [
                'is_active' => $isActive,
                'supported_coins' => ['USDT'],
                'supported_chains' => [
                    'USDT' => ['TRC20', 'ERC20', 'BEP20'],
                ],
                'message' => $isActive ? 'CCPayment نشط' : 'CCPayment غير مُكوّن',
            ],
        ]);
    }

    /**
     * Get supported coins list
     */
    public function getSupportedCoins(): JsonResponse
    {
        try {
            $assets = $this->ccpayment->getAssetList();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'coins' => $assets['assets'] ?? [],
                    'count' => count($assets['assets'] ?? []),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment get coins error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل جلب قائمة العملات',
            ], 500);
        }
    }

    /**
     * Create a deposit address for user's wallet
     */
    public function createDepositAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|integer|exists:wallets,id',
            'chain' => 'required|string|in:TRC20,ERC20,BEP20',
            'currency' => 'required|string|in:USDT',
        ], [
            'wallet_id.required' => 'معرّف المحفظة مطلوب',
            'wallet_id.exists' => 'المحفظة غير موجودة',
            'chain.required' => 'الشبكة مطلوبة',
            'chain.in' => 'الشبكة غير مدعومة',
            'currency.required' => 'العملة مطلوبة',
            'currency.in' => 'العملة المدعومة حالياً هي USDT فقط',
        ]);

        $wallet = Wallet::where('id', $validated['wallet_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة أو لا تخصك',
            ], 403);
        }

        try {
            $result = $this->ccpayment->createWalletDeposit(
                $wallet,
                $validated['chain'],
                $validated['currency']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء عنوان الإيداع بنجاح',
                'data' => [
                    'address' => $result['address'],
                    'memo' => $result['memo'],
                    'reference_id' => $result['reference_id'],
                    'chain' => $validated['chain'],
                    'currency' => $validated['currency'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment create deposit address error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل إنشاء عنوان الإيداع: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get deposit status
     */
    public function getDepositStatus(Request $request, string $reference): JsonResponse
    {
        try {
            // Deposit rows are keyed by recordId in `reference`; the address ref the
            // mobile holds lives in metadata. Match either so a status lookup by the
            // address ref still resolves to the most recent deposit on that address.
            $transaction = \App\Models\Transaction::where('user_id', $request->user()->id)
                ->where(function ($q) use ($reference) {
                    $q->where('reference', $reference)
                        ->orWhere('metadata->ccpayment_reference_id', $reference);
                })
                ->latest()
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'المعاملة غير موجودة',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reference' => $reference,
                    'status' => $transaction->status->value,
                    'status_label' => $transaction->status->label(),
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                    'metadata' => $transaction->metadata,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment deposit status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل جلب حالة الإيداع',
            ], 500);
        }
    }

    /**
     * Get deposit history
     */
    public function getDepositHistory(Request $request): JsonResponse
    {
        $transactions = \App\Models\Transaction::where('user_id', $request->user()->id)
            ->where('category', \App\Enums\TransactionCategory::CRYPTO)
            ->where('type', \App\Enums\TransactionType::DEPOSIT)
            ->visibleToUser()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Process crypto withdrawal
     */
    public function withdraw(Request $request): JsonResponse
    {
        if (LedgerHaltGuard::isHalted()) {
            return response()->json([
                'success' => false,
                'message' => 'الخدمة متوقفة مؤقتاً لأسباب تتعلق بسلامة الأرصدة. يرجى المحاولة لاحقاً أو التواصل مع الدعم.',
                'code' => 'disbursement_halted',
            ], 503);
        }

        $validated = $request->validate([
            'wallet_id' => 'required|integer|exists:wallets,id',
            'address' => 'required|string|min:10',
            'amount' => 'required|string|regex:/^\d+(\.\d+)?$/',
            'chain' => 'required|string|in:TRC20,ERC20,BEP20',
            'currency' => 'required|string|in:USDT',
            'memo' => 'nullable|string|max:100',
        ], [
            'wallet_id.required' => 'معرّف المحفظة مطلوب',
            'wallet_id.exists' => 'المحفظة غير موجودة',
            'address.required' => 'عنوان المحفظة مطلوب',
            'address.min' => 'عنوان المحفظة قصير جداً',
            'amount.required' => 'المبلغ مطلوب',
            'amount.regex' => 'صيغة المبلغ غير صحيحة',
            'chain.required' => 'الشبكة مطلوبة',
            'chain.in' => 'الشبكة غير مدعومة',
            'currency.required' => 'العملة مطلوبة',
            'currency.in' => 'العملة المدعومة حالياً هي USDT فقط',
        ]);

        $wallet = Wallet::where('id', $validated['wallet_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة أو لا تخصك',
            ], 403);
        }

        // KYC gate: crypto withdrawals require a verified level granting can_withdraw (fail-closed).
        if (! (app(\App\Services\KycService::class)->permissionsForUser($request->user())['can_withdraw'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'السحب يتطلب توثيق الهوية (KYC).',
                'code' => 'kyc_required',
            ], 403);
        }

        // ---- Phase A: short locked transaction — debit + reserve the tx row.
        // NO external HTTP happens under this lock (SEC W-SEV-1: a hung/slow
        // gateway call must never hold the wallet row lock).
        $orderId = 'sarva_wd_' . Str::random(12);

        $reserved = DB::transaction(function () use ($wallet, $validated, $orderId) {
            $locked = Wallet::lockForUpdate()->find($wallet->id);
            if (!$locked) {
                return ['error' => response()->json([
                    'success' => false,
                    'message' => 'المحفظة غير موجودة',
                ], 404)];
            }

            // Check balance inside the lock
            if ((float) $locked->balance < floatval($validated['amount'])) {
                return ['error' => response()->json([
                    'success' => false,
                    'message' => 'الرصيد غير كافٍ',
                ], 400)];
            }

            // KYC velocity cap — same identity-based caps as transfer + wallet
            // withdraw (single + cumulative daily/monthly across both channels),
            // enforced INSIDE the lock, BEFORE the optimistic debit. 422 (not 500)
            // so the mobile client shows the specific Arabic limit message.
            if ($locked->user_id) {
                try {
                    app(\App\Services\KycService::class)->assertWithinKycLimits(
                        $locked->user, floatval($validated['amount']), $locked->currency, 'withdrawal'
                    );
                } catch (\RuntimeException $e) {
                    return ['error' => response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ], 422)];
                }
            }

            if (!$locked->debit(floatval($validated['amount']))) {
                return ['error' => response()->json([
                    'success' => false,
                    'message' => 'تعذر خصم الرصيد',
                ], 400)];
            }

            // Reserved state: funds are debited but the gateway has not been
            // called yet (gateway_dispatched=false). We reuse PENDING rather
            // than adding a new enum case — TransactionStatus has no DB
            // check-constraint, but PENDING already means "not yet final"
            // everywhere else in the codebase (deposits, other withdrawals),
            // so a distinct case isn't needed; the metadata flag disambiguates.
            $tx = Transaction::create([
                'user_id' => $locked->user_id,
                'wallet_id' => $locked->id,
                'type' => TransactionType::WITHDRAWAL,
                'category' => TransactionCategory::CRYPTO,
                'status' => TransactionStatus::PENDING,
                'amount' => $validated['amount'],
                'title' => 'سحب كريبتو',
                'currency' => $validated['currency'],
                'reference' => $orderId,
                'description' => 'سحب CCPayment - ' . $validated['chain'],
                'metadata' => [
                    'to_address' => $validated['address'],
                    'chain' => $validated['chain'],
                    'gateway_dispatched' => false,
                ],
            ]);

            return ['tx' => $tx];
        });

        if (isset($reserved['error'])) {
            return $reserved['error'];
        }

        /** @var Transaction $tx */
        $tx = $reserved['tx'];

        // ---- Phase B: call the gateway OUTSIDE any wallet lock.
        try {
            $result = $this->ccpayment->dispatchWithdrawToGateway(
                $orderId,
                $validated['address'],
                $validated['amount'],
                $validated['chain'],
                $validated['currency'],
                $validated['memo'] ?? null
            );

            // Metadata-only update — no wallet lock needed, single row by PK.
            $tx->update([
                'metadata' => array_merge($tx->metadata ?? [], [
                    'ccpayment_record_id' => $result['record_id'],
                    'fee' => $result['fee'],
                    'gateway_dispatched' => true,
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال طلب السحب بنجاح',
                'data' => [
                    'record_id' => $result['record_id'],
                    'order_id' => $result['order_id'],
                    'fee' => $result['fee'],
                    'amount' => $validated['amount'],
                    'currency' => $validated['currency'],
                    'chain' => $validated['chain'],
                    'to_address' => $validated['address'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment withdraw error: ' . $e->getMessage());

            // The debit must never be lost without either a completed
            // withdrawal or a refund: gateway call failed, so refund the
            // wallet under a NEW short independent lock (same pattern as
            // CCPaymentService::handleWithdrawWebhook's failure path) and
            // mark the reserved tx FAILED.
            DB::transaction(function () use ($tx, $e) {
                $freshTx = Transaction::lockForUpdate()->find($tx->id);
                if (!$freshTx || $freshTx->status === TransactionStatus::FAILED) {
                    return; // already handled (e.g. by a racing webhook)
                }

                $lockedWallet = Wallet::lockForUpdate()->find($freshTx->wallet_id);
                if ($lockedWallet) {
                    $lockedWallet->credit((float) $freshTx->amount);
                }

                $freshTx->update([
                    'status' => TransactionStatus::FAILED,
                    'metadata' => array_merge($freshTx->metadata ?? [], [
                        'gateway_dispatched' => false,
                        'refunded' => true,
                        'failure_reason' => $e->getMessage(),
                    ]),
                ]);
            });

            return response()->json([
                'success' => false,
                'message' => 'فشل السحب: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get withdrawal fee
     */
    public function getWithdrawFee(Request $request): JsonResponse
    {
        // Resolve the CCPayment coinId + chain symbol server-side from the app's
        // currency + network code. We do NOT trust a client-supplied coin_id: the
        // old contract let the app pass CoinMarketCap ids (ERC20 => 1) and raw
        // network codes (TRC20), which CCPayment rejects with 13000 "unsupported
        // coin" / 13001 "unsupported network". `currency` is the new field;
        // `coin_id` is still accepted for older builds but ignored in favour of it.
        $validated = $request->validate([
            'currency' => 'nullable|string|min:1',
            'coin_id' => 'nullable|integer|min:1',
            'chain' => 'required|string|min:1',
        ], [
            'chain.required' => 'الشبكة مطلوبة',
        ]);

        $currency = $validated['currency'] ?? 'USDT';
        $coinId = $this->ccpayment->getCoinId($currency, $validated['chain']);
        $ccChain = $this->ccpayment->ccChain($validated['chain']);

        try {
            $fee = $this->ccpayment->getWithdrawFee($coinId, $ccChain);

            return response()->json([
                'success' => true,
                'data' => [
                    'fee' => $fee,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment fee error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل جلب الرسوم',
            ], 500);
        }
    }

    /**
     * Get withdrawal status
     */
    public function getWithdrawStatus(Request $request, string $reference): JsonResponse
    {
        try {
            $transaction = \App\Models\Transaction::where('reference', $reference)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'المعاملة غير موجودة',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reference' => $reference,
                    'status' => $transaction->status->value,
                    'status_label' => $transaction->status->label(),
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                    'metadata' => $transaction->metadata,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment withdraw status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل جلب حالة السحب',
            ], 500);
        }
    }

    /**
     * Get withdrawal history
     */
    public function getWithdrawHistory(Request $request): JsonResponse
    {
        $transactions = \App\Models\Transaction::where('user_id', $request->user()->id)
            ->where('category', \App\Enums\TransactionCategory::CRYPTO)
            ->where('type', \App\Enums\TransactionType::WITHDRAWAL)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get merchant assets
     */
    public function getAssets(): JsonResponse
    {
        try {
            $assets = $this->ccpayment->getAssetList();

            return response()->json([
                'success' => true,
                'data' => [
                    'assets' => $assets['assets'] ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment assets error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل جلب الأصول',
            ], 500);
        }
    }

    /**
     * Get single asset details
     */
    public function getAssetDetail(Request $request, int $coinId): JsonResponse
    {
        try {
            $asset = $this->ccpayment->getAsset($coinId);

            return response()->json([
                'success' => true,
                'data' => [
                    'asset' => $asset,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CCPayment asset detail error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل جلب تفاصيل الأصل',
            ], 500);
        }
    }
}
