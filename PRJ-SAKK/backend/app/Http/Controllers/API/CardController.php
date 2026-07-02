<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Services\CardService;
use App\Services\StripeIssuingService;
use App\Enums\CardBrand;
use App\Enums\CardStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CardController extends Controller
{
    public function __construct(
        private readonly CardService $cardService,
        private readonly StripeIssuingService $stripeService
    ) {}

    /**
     * Get all cards for authenticated user
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $cards = $request->user()->cards()
            ->with('wallet')
            ->latest()
            ->get();

        return CardResource::collection($cards);
    }

    /**
     * Create a new virtual card.
     *
     * Issues a REAL card via Stripe Issuing (StripeIssuingService). The card
     * purchase fee is charged first (same fee logic as the legacy local-card
     * path, factored into CardService::chargePurchaseFee so both paths share
     * one audit trail), then Stripe issuance is attempted; if Stripe fails,
     * the fee is refunded — we never charge for a card that was never issued.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'brand' => 'required|in:visa,mastercard',
            'nickname' => 'nullable|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'spending_limit' => 'nullable|numeric|min:100|max:50000',
        ], [
            'wallet_id.required' => 'المحفظة مطلوبة.',
            'wallet_id.exists' => 'المحفظة غير موجودة.',
            'brand.required' => 'نوع البطاقة مطلوب.',
            'brand.in' => 'نوع البطاقة يجب أن يكون visa أو mastercard.',
            'nickname.max' => 'الاسم المستعار يجب أن لا يتجاوز 50 حرف.',
            'color.regex' => 'لون البطاقة يجب أن يكون بصيغة HEX (#RRGGBB).',
            'spending_limit.min' => 'حد الإنفاق يجب أن يكون 100 على الأقل.',
            'spending_limit.max' => 'حد الإنفاق يجب أن لا يتجاوز 50,000.',
        ]);

        $user = $request->user();

        // Verify wallet ownership
        $wallet = Wallet::find($request->wallet_id);
        if (!$wallet || $wallet->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        // Feature must actually be usable — Stripe issuance requires configured
        // credentials. No fallback to a fake local card: fail cleanly instead.
        if (!$this->stripeService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'ميزة البطاقات غير مُفعّلة حالياً، يرجى المحاولة لاحقاً',
            ], 422);
        }

        // Check card limit against KYC level (single source of truth: KycService)
        $kycService = app(\App\Services\KycService::class);
        $cardsLimit = $kycService->cardsLimitForUser($user);
        if ($user->activeCards()->count() >= $cardsLimit) {
            return response()->json([
                'success' => false,
                'message' => "تم الوصول للحد الأقصى للبطاقات ({$cardsLimit} بطاقات). وثّق حسابك لرفع الحد.",
            ], 422);
        }

        $brand = $request->brand ?? 'visa';

        // Charge the card purchase fee first (locked, its own DB transaction).
        $feeResult = $this->cardService->chargePurchaseFee($user, $wallet, $brand, 'virtual');
        if (!$feeResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $feeResult['error'] ?? 'فشل إنشاء البطاقة',
                'data' => $feeResult,
            ], 422);
        }

        // Fee charged — now attempt the real Stripe issuance. If it fails,
        // refund the fee: never charge for a card that wasn't issued.
        $issueResult = $this->stripeService->issueVirtualCard($user, $wallet);

        if (!$issueResult['success']) {
            $this->cardService->refundPurchaseFee(
                $user,
                $wallet,
                (float) $feeResult['fee'],
                $feeResult['transaction_id'] ?? null,
            );

            return response()->json([
                'success' => false,
                'message' => $issueResult['error'] ?? 'فشل إصدار البطاقة عبر Stripe',
            ], 422);
        }

        $card = VirtualCard::with('wallet')->find($issueResult['card']['id']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء البطاقة الافتراضية بنجاح',
            'data' => new CardResource($card),
        ], 201);
    }

    /**
     * Get card details
     */
    public function show(Request $request, VirtualCard $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CardResource($card->load('wallet')),
        ]);
    }

    /**
     * Get card details (PCI-DSS safe — masked PAN only, no CVV).
     *
     * Full PAN and CVV are never exposed to the client. Only the masked
     * card number, last 4 digits, and expiry are returned. The full
     * number is available server-side only for payment processing via
     * the provider (Stripe) over TLS.
     */
    public function details(Request $request, VirtualCard $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'card_number_masked' => $card->card_number_masked,
                'last4' => substr((string) $card->card_number, -4),
                'bin' => $card->bin,
                'expiry_month' => $card->expiry_month,
                'expiry_year' => $card->expiry_year,
                'cardholder_name' => $card->cardholder_name,
                'balance' => (float) $card->balance,
                'brand' => $card->brand->value ?? null,
            ],
        ]);
    }

    /**
     * Update card settings
     */
    public function update(Request $request, VirtualCard $card): JsonResponse
    {
        $request->validate([
            'nickname' => 'nullable|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'spending_limit' => 'nullable|numeric|min:100|max:50000',
            'daily_limit' => 'nullable|numeric|min:100|max:10000',
            'monthly_limit' => 'nullable|numeric|min:1000|max:100000',
            'online_enabled' => 'nullable|boolean',
            'international_enabled' => 'nullable|boolean',
            'contactless_enabled' => 'nullable|boolean',
        ], [
            'nickname.max' => 'الاسم المستعار يجب أن لا يتجاوز 50 حرف.',
            'color.regex' => 'لون البطاقة يجب أن يكون بصيغة HEX.',
            'spending_limit.min' => 'حد الإنفاق يجب أن يكون 100 على الأقل.',
            'spending_limit.max' => 'حد الإنفاق يجب أن لا يتجاوز 50,000.',
            'daily_limit.min' => 'الحد اليومي يجب أن يكون 100 على الأقل.',
            'daily_limit.max' => 'الحد اليومي يجب أن لا يتجاوز 10,000.',
            'monthly_limit.min' => 'الحد الشهري يجب أن يكون 1,000 على الأقل.',
            'monthly_limit.max' => 'الحد الشهري يجب أن لا يتجاوز 100,000.',
        ]);

        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        $card->update($request->only([
            'nickname',
            'color',
            'spending_limit',
            'daily_limit',
            'monthly_limit',
            'online_enabled',
            'international_enabled',
            'contactless_enabled',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البطاقة بنجاح',
            'data' => new CardResource($card->fresh()),
        ]);
    }

    /**
     * Load money to card from wallet
     */
    public function load(Request $request, VirtualCard $card): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
        ], [
            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون 1 على الأقل.',
            'amount.max' => 'المبلغ يجب أن لا يتجاوز 10,000.',
        ]);

        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        if ($card->status !== CardStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير نشطة',
            ], 422);
        }

        $result = $this->cardService->loadCard($card, $card->wallet, (float) $request->amount);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? $result['message'] ?? 'فشلت العملية',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم شحن البطاقة بنجاح',
            'data' => [
                'card_balance' => $card->fresh()->balance,
                'wallet_balance' => $card->wallet->fresh()->balance,
                'transaction' => new TransactionResource(Transaction::find($result['transaction_id'])),
            ],
        ]);
    }

    /**
     * Unload money from card to wallet
     */
    public function unload(Request $request, VirtualCard $card): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ], [
            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون 1 على الأقل.',
        ]);

        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        // Balance check moved to CardService::unloadCard (inside lockForUpdate).
        // The controller-level check was removed because it's a TOCTOU race:
        // the authoritative check is inside the DB transaction with pessimistic lock.

        $result = $this->cardService->unloadCard($card, $card->wallet, (float) $request->amount);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تفريغ البطاقة بنجاح',
            'data' => [
                'card_balance' => $card->fresh()->balance,
                'wallet_balance' => $card->wallet->fresh()->balance,
                'transaction' => new TransactionResource(Transaction::find($result['transaction_id'])),
            ],
        ]);
    }

    /**
     * Freeze card
     */
    public function freeze(Request $request, VirtualCard $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        $card->freeze($request->reason ?? 'User requested');

        return response()->json([
            'success' => true,
            'message' => 'تم تجميد البطاقة بنجاح',
            'data' => new CardResource($card->fresh()),
        ]);
    }

    /**
     * Unfreeze card
     */
    public function unfreeze(Request $request, VirtualCard $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        if (!$card->unfreeze()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء تجميد البطاقة',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء تجميد البطاقة بنجاح',
            'data' => new CardResource($card->fresh()),
        ]);
    }

    /**
     * Cancel card permanently
     */
    public function cancel(Request $request, VirtualCard $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        // Transfer remaining balance to wallet (inside lockForUpdate via service)
        if ($card->balance > 0) {
            $result = $this->cardService->unloadCard($card, $card->wallet, (float) $card->balance);
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'فشل تفريغ رصيد البطاقة',
                ], 422);
            }
        }

        // CardService::cancelCard handles the actual cancellation (with locking).
        // But this controller calls $card->cancel() directly. Delegate to service.
        $cancelResult = $this->cardService->cancelCard($card, $card->wallet);
        if (!$cancelResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $cancelResult['error'] ?? 'فشل إلغاء البطاقة',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء البطاقة بنجاح',
        ]);
    }

    /**
     * Get card transactions
     */
    public function transactions(Request $request, VirtualCard $card): AnonymousResourceCollection
    {
        if ($card->user_id !== $request->user()->id) {
            abort(404, 'البطاقة غير موجودة');
        }

        $transactions = $card->transactions()
            ->latest()
            ->paginate($request->per_page ?? 20);

        return TransactionResource::collection($transactions);
    }

    /**
     * Issue a Stripe virtual card
     */
    public function issueStripeCard(Request $request): JsonResponse
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
        ], [
            'wallet_id.required' => 'المحفظة مطلوبة.',
            'wallet_id.exists' => 'المحفظة غير موجودة.',
        ]);

        $user = $request->user();

        // Verify wallet ownership
        $wallet = Wallet::find($request->wallet_id);
        if (!$wallet || $wallet->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'المحفظة غير موجودة',
            ], 404);
        }

        // Issue Stripe card
        $result = $this->stripeService->issueVirtualCard($user, $wallet);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'فشل إصدار البطاقة',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إصدار بطاقة Stripe بنجاح',
            'data' => $result['card'],
        ], 201);
    }

    /**
     * Get Stripe card details (sensitive - PAN, CVV)
     */
    public function stripeCardDetails(Request $request, VirtualCard $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'البطاقة غير موجودة',
            ], 404);
        }

        if ($card->provider !== 'stripe') {
            return response()->json([
                'success' => false,
                'message' => 'هذه البطاقة ليست من Stripe',
            ], 422);
        }

        $result = $this->stripeService->getCardDetails($card);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'فشل جلب تفاصيل البطاقة',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result['card'],
        ]);
    }
}
