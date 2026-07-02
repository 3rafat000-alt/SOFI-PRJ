<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\ConvertRequest;
use App\Http\Requests\Wallet\DepositRequest;
use App\Http\Requests\Wallet\WithdrawRequest;
use App\Http\Resources\WalletResource;
use App\Http\Resources\TransactionResource;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Services\TransactionService;
use App\Services\PinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly TransactionService $transactionService,
        private readonly PinService $pinService
    ) {}

    /**
     * Get all wallets for authenticated user
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->walletService->ensureUserWallets($request->user());

        $wallets = $request->user()->wallets()->get();

        return WalletResource::collection($wallets);
    }

    /**
     * Get wallet details
     */
    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        // Ensure wallet belongs to user
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new WalletResource($wallet),
        ]);
    }

    /**
     * Create a new wallet
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|in:USD,SYP',
        ], [
            'currency.required' => 'العملة مطلوبة.',
            'currency.in' => 'العملة يجب أن تكون USD أو SYP.',
        ]);

        $user = $request->user();

        // Check if wallet already exists
        if ($user->wallets()->where('currency', $request->currency)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة موجودة مسبقاً لهذه العملة',
            ], 422);
        }

        $wallet = $this->walletService->createWallet($user, $request->currency);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المحفظة بنجاح',
            'data' => [
                'wallet' => new WalletResource($wallet),
            ],
        ], 201);
    }

    /**
     * Get wallet balance
     */
    public function balance(Request $request, Wallet $wallet): JsonResponse
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => $wallet->currency,
                'balance' => (float) $wallet->balance,
                'available_balance' => (float) $wallet->available_balance,
                'pending_balance' => (float) $wallet->pending_balance,
                'formatted_balance' => $wallet->formatted_balance,
            ],
        ]);
    }

    /**
     * Get wallet transactions
     */
    public function transactions(Request $request, Wallet $wallet): AnonymousResourceCollection
    {
        if ($wallet->user_id !== $request->user()->id) {
            abort(404, 'المحفظة غير موجودة');
        }

        $transactions = $wallet->transactions()
            ->visibleToUser()
            ->latest()
            ->paginate($request->per_page ?? 20);

        return TransactionResource::collection($transactions);
    }

    /**
     * Deposit to wallet (simulate - in production, integrate with payment gateway)
     *
     * SEC C1: simulated credit with NO payment provider — must never run in
     * production (it would let any user mint balance). Real deposits flow only
     * through the signed CCPayment webhook. Hard-guarded to local/test below;
     * the route itself is also registered for local/test only.
     */
    public function deposit(DepositRequest $request, Wallet $wallet): JsonResponse
    {
        abort_unless(app()->environment('local', 'testing'), 404);

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        $transaction = $this->walletService->deposit(
            $wallet,
            $request->amount,
            'إيداع في المحفظة',
            ['source' => 'api']
        );

        // A qualifying first deposit may unlock the referral reward for the
        // person who invited this user (verified + deposited >= $100).
        try {
            app(\App\Services\ReferralService::class)->maybeGrant($request->user());
        } catch (\Throwable $e) {
            // Non-critical — never block the deposit.
        }

        return response()->json([
            'success' => true,
            'message' => 'تم الإيداع بنجاح',
            'data' => [
                'transaction' => new TransactionResource($transaction),
                'new_balance' => $wallet->fresh()->balance,
            ],
        ]);
    }

    /**
     * Withdraw from wallet
     */
    public function withdraw(WithdrawRequest $request, Wallet $wallet): JsonResponse
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        // KYC gate: withdrawals require a verified level whose policy grants can_withdraw.
        // Fails closed — an unknown/misconfigured level yields no permission.
        if (! (app(\App\Services\KycService::class)->permissionsForUser($request->user())['can_withdraw'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'السحب يتطلب توثيق الهوية (KYC).',
                'code' => 'kyc_required',
            ], 403);
        }

        // Validate PIN — user must have set one first
        if (!$request->user()->pin_code) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى تعيين رمز PIN أولاً من الإعدادات',
                'code' => 'pin_not_set',
            ], 422);
        }
        if (!$this->pinService->verify($request->user(), $request->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز PIN غير صحيح',
            ], 422);
        }

        // Balance check moved inside WalletService::withdraw() which acquires
        // lockForUpdate. The controller-level canSpend() check was removed
        // to prevent TOCTOU — the authoritative check is inside the lock.

        $transaction = $this->walletService->withdraw(
            $wallet,
            $request->amount,
            'سحب من المحفظة',
            ['destination' => $request->destination ?? 'bank']
        );

        // Alert admins that a withdrawal is awaiting processing (non-critical).
        rescue(fn () => \App\Services\AdminNotificationService::withdrawalRequested(
            $request->user(),
            (float) $request->amount,
            $wallet->currency ?? 'USD',
        ));

        return response()->json([
            'success' => true,
            'message' => 'تم بدء عملية السحب',
            'data' => [
                'transaction' => new TransactionResource($transaction),
                'new_balance' => $wallet->fresh()->balance,
            ],
        ]);
    }

    /**
     * Get wallet statistics
     */
    public function stats(Request $request, Wallet $wallet): JsonResponse
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        $stats = $this->walletService->getStats($wallet);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Convert currency between wallets
     */
    public function convert(ConvertRequest $request): JsonResponse
    {
        $user = $request->user();
        $direction = $request->from_currency === 'USD' ? 'usd_to_syp' : 'syp_to_usd';

        $fromWallet = $user->wallets()->where('currency', $request->from_currency)->first();
        $toWallet = $user->wallets()->where('currency', $request->to_currency)->first();

        if (!$fromWallet || !$toWallet) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        // Balance check moved inside WalletService::convert() which acquires
        // lockForUpdate on both wallets. The controller-level canSpend() check
        // was removed to prevent TOCTOU.

        try {
            $transaction = $this->walletService->convert(
                $fromWallet,
                $toWallet,
                (float) $request->amount,
                $direction
            );

            return response()->json([
                'success' => true,
                'message' => 'تم التحويل بنجاح',
                'data' => [
                    'transaction' => new TransactionResource($transaction),
                    'from_wallet' => new WalletResource($fromWallet->fresh()),
                    'to_wallet' => new WalletResource($toWallet->fresh()),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get deposit address for wallet (not yet implemented)
     */
    public function depositAddress(Wallet $wallet): JsonResponse
    {
        return response()->json([
            'message' => 'Deposit address feature coming soon',
            'code' => 'not_implemented',
        ], 501);
    }

    /**
     * Delete wallet (must have zero balance)
     */
    public function destroy(Request $request, Wallet $wallet): JsonResponse
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        if (floatval($wallet->balance) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المحفظة والرصيد فيها ' . number_format(floatval($wallet->balance), 2),
            ], 422);
        }

        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المحفظة بنجاح',
        ]);
    }
}
