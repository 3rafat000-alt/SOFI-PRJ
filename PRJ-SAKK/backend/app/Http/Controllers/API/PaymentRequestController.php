<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\VerifiesTransactionAuth;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Payment requests: a user creates a request for a specific amount; anyone with
 * the link/QR (uuid) can view it and pay it. Payment reuses the P2P transfer
 * engine (instant, same-currency, free).
 */
class PaymentRequestController extends Controller
{
    use VerifiesTransactionAuth;

    public function __construct(private readonly TransferService $transferService) {}

    /** Create a new payment request. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:USD,SYP',
            'note' => 'nullable|string|max:140',
            'merchant_name' => 'nullable|string|max:60',
            'requestee_account' => 'nullable|string|max:40',
            'expires_in_hours' => 'nullable|integer|min:1|max:720',
            'callback_url' => 'nullable|url|max:512',
            'callback_secret' => 'nullable|string|min:16|max:64',
            'success_url' => 'nullable|url|max:512',
            'cancel_url' => 'nullable|url|max:512',
        ], [
            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون 0.01 على الأقل.',
            'currency.required' => 'العملة مطلوبة.',
            'currency.in' => 'العملة يجب أن تكون USD أو SYP.',
            'note.max' => 'الملاحظة طويلة جداً.',
        ]);

        $requester = $request->user();

        // Directed request: resolve the targeted user from their account/tag.
        $requesteeId = null;
        if (!empty($validated['requestee_account'])) {
            $requestee = $this->transferService->resolveRecipient($validated['requestee_account']);
            if (!$requestee) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يُعثر على هذا الحساب.',
                ], 422);
            }
            if ($requestee->id === $requester->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك طلب دفعة من نفسك.',
                ], 422);
            }
            $requesteeId = $requestee->id;
        }

        // Payment links are valid for 24 hours by default.
        $expiresInHours = (int) ($validated['expires_in_hours'] ?? 24);

        $pr = PaymentRequest::create([
            'user_id' => $requester->id,
            'requestee_id' => $requesteeId,
            'currency' => $validated['currency'],
            'amount' => $validated['amount'],
            'note' => $validated['note'] ?? null,
            'merchant_name' => $validated['merchant_name'] ?? null,
            'status' => 'pending',
            'callback_url' => $validated['callback_url'] ?? null,
            'callback_secret' => $validated['callback_secret'] ?? null,
            'success_url' => $validated['success_url'] ?? null,
            'cancel_url' => $validated['cancel_url'] ?? null,
            'expires_at' => now()->addHours($expiresInHours),
        ]);

        // Notify the targeted contact that they have a request to accept/reject.
        if ($requesteeId) {
            $this->notify(
                userId: $requesteeId,
                template: 'payment_request',
                title: 'طلب دفعة جديد',
                body: "طلب منك {$requester->full_name} دفعة بقيمة "
                    . $this->money((float) $pr->amount, $pr->currency)
                    . ($pr->note ? " — {$pr->note}" : ''),
                data: [
                    'type' => 'payment_request',
                    'uuid' => $pr->uuid,
                    'amount' => (float) $pr->amount,
                    'currency' => $pr->currency,
                    'requester_name' => $requester->full_name,
                    'note' => $pr->note,
                ],
            );
        }

        return response()->json([
            'success' => true,
            'message' => $requesteeId ? 'تم إرسال طلب الدفع' : 'تم إنشاء طلب الدفع',
            'data' => $this->present($pr->fresh(), $request),
        ], 201);
    }

    /** Payment requests targeted at the authenticated user (to accept/reject). */
    public function received(Request $request): JsonResponse
    {
        $requests = PaymentRequest::where('requestee_id', $request->user()->id)
            ->where('status', 'pending')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (PaymentRequest $pr) => $this->present($pr, $request));

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /** Accept a directed request — the requestee pays the requester. */
    public function accept(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $request->user();

        if ($paymentRequest->requestee_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        // SEC H1: accepting pays the requester (money out) — require a second factor.
        if (!$this->verifyTransactionFactor($request, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق الأمني — رمز PIN أو البصمة مطلوب لإتمام الدفع.',
                'code' => 'transaction_auth_required',
            ], 422);
        }

        return DB::transaction(function () use ($request, $paymentRequest, $user) {
            // Lock the payment request row to prevent double-spend on concurrent accept
            $locked = PaymentRequest::lockForUpdate()->find($paymentRequest->id);
            if (!$locked) {
                return response()->json(['success' => false, 'message' => 'طلب الدفع غير موجود'], 404);
            }

            if (!$locked->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الطلب لم يعد متاحاً.',
                ], 422);
            }

            try {
                $result = $this->transferService->transfer(
                    sender: $user,
                    recipient: $locked->user,
                    amount: (float) $locked->amount,
                    currency: $locked->currency,
                    note: $locked->note ?? 'قبول طلب دفعة',
                );
            } catch (\RuntimeException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            $locked->markAsPaid($user->id, $result['from_transaction']->id);

            // Best-effort webhook: fire after the DB transaction commits.
            rescue(fn () => $this->fireCallback($locked->fresh()));

        DB::afterCommit(fn () => $this->notify(
            userId: $paymentRequest->user_id,
            template: 'payment_request_accepted',
            title: 'تم قبول طلبك',
            body: "قبِل {$user->full_name} طلبك ودفع "
                . $this->money((float) $paymentRequest->amount, $paymentRequest->currency),
            data: ['type' => 'payment_request_accepted', 'uuid' => $paymentRequest->uuid],
        ));

        return response()->json([
            'success' => true,
            'message' => 'تم الدفع بنجاح',
            'data' => [
                'transaction' => new TransactionResource($result['from_transaction']),
                'payment_request' => $this->present($paymentRequest->fresh(), $request),
            ],
        ]);
    });
}

    /** Reject a directed request (with an optional note). */
    public function reject(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $request->user();

        if ($paymentRequest->requestee_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }
        if (!$paymentRequest->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب لم يعد متاحاً.',
            ], 422);
        }

        $note = trim((string) $request->input('note', ''));
        $paymentRequest->reject($note !== '' ? $note : null);

        $this->notify(
            userId: $paymentRequest->user_id,
            template: 'payment_request_rejected',
            title: 'تم رفض طلبك',
            body: "رفض {$user->full_name} طلب الدفعة" . ($note !== '' ? " — {$note}" : ''),
            data: ['type' => 'payment_request_rejected', 'uuid' => $paymentRequest->uuid],
        );

        return response()->json(['success' => true, 'message' => 'تم رفض الطلب']);
    }

    /** Create an in-app notification (best-effort). */
    private function notify(int $userId, string $template, string $title, string $body, array $data): void
    {
        try {
            UserNotification::create([
                'user_id' => $userId,
                'uuid' => (string) Str::uuid(),
                'template_code' => $template,
                'channel' => 'in_app',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sent_at' => now(),
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            // Notifications are non-critical; never block the main action.
        }
    }

    private function money(float $amount, string $currency): string
    {
        return \App\Support\Money::format($amount, $currency);
    }

    /** List the authenticated user's own payment requests. */
    public function index(Request $request): JsonResponse
    {
        $requests = PaymentRequest::where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (PaymentRequest $pr) => $this->present($pr, $request));

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /** View a single payment request by uuid (to display before paying). */
    public function show(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        if ($paymentRequest->user_id !== $request->user()->id
            && !is_null($paymentRequest->requestee_id)
            && $paymentRequest->requestee_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->present($paymentRequest, $request),
        ]);
    }

    /** Pay a pending payment request. */
    public function pay(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $payer = $request->user();

        // SEC H1: paying a request moves money out — require a second factor.
        if (!$this->verifyTransactionFactor($request, $payer)) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق الأمني — رمز PIN أو البصمة مطلوب لإتمام الدفع.',
                'code' => 'transaction_auth_required',
            ], 422);
        }

        return DB::transaction(function () use ($request, $paymentRequest, $payer) {
            // Lock the payment request row to prevent double-spend on concurrent pay
            $locked = PaymentRequest::lockForUpdate()->find($paymentRequest->id);
            if (!$locked) {
                return response()->json(['success' => false, 'message' => 'طلب الدفع غير موجود'], 404);
            }

            if (!$locked->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'طلب الدفع غير متاح (مدفوع أو ملغى أو منتهٍ).',
                ], 422);
            }

            if ($locked->user_id === $payer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك دفع طلبك الخاص.',
                ], 422);
            }

            if ($locked->requestee_id !== null && $locked->requestee_id !== $payer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الطلب موجّه لمستخدم آخر.',
                ], 403);
            }

            try {
                $result = $this->transferService->transfer(
                    sender: $payer,
                    recipient: $locked->user,
                    amount: (float) $locked->amount,
                    currency: $locked->currency,
                    note: $locked->note ?? 'دفع طلب',
                );
            } catch (\RuntimeException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            $locked->markAsPaid($payer->id, $result['from_transaction']->id);

            // Best-effort webhook: fire after the DB transaction commits.
            DB::afterCommit(fn () => rescue(fn () => $this->fireCallback($locked->fresh())));

            DB::afterCommit(fn () => $this->notify(
                userId: $paymentRequest->user_id,
                template: 'payment_request_paid',
                title: 'تم دفع طلبك',
                body: "دفع {$payer->full_name} طلبك بقيمة "
                    . $this->money((float) $paymentRequest->amount, $paymentRequest->currency),
                data: ['type' => 'payment_request_paid', 'uuid' => $paymentRequest->uuid],
            ));

        return response()->json([
            'success' => true,
            'message' => 'تم الدفع بنجاح',
            'data' => [
                'transaction' => new TransactionResource($result['from_transaction']),
                'payment_request' => $this->present($paymentRequest->fresh(), $request),
            ],
        ]);
    });
}

    /** Cancel a pending payment request (owner only). */
    public function cancel(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        if ($paymentRequest->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }
        if ($paymentRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'لا يمكن إلغاء هذا الطلب'], 422);
        }

        $paymentRequest->cancel();

        return response()->json(['success' => true, 'message' => 'تم إلغاء الطلب']);
    }

    /** Privacy-preserving representation of a payment request. */
    private function present(PaymentRequest $pr, Request $request): array
    {
        $requester = $pr->user;
        $effectiveStatus = $pr->status;
        if ($effectiveStatus === 'pending' && $pr->expires_at !== null && $pr->expires_at->isPast()) {
            $effectiveStatus = 'expired';
        }

        $meId = $request->user()?->id;
        $requestee = $pr->requestee;

        return [
            'uuid' => $pr->uuid,
            'pay_url' => rtrim((string) config('app.pay_url_base', url('/pay')), '/') . '/' . $pr->uuid,
            'amount' => (float) $pr->amount,
            'currency' => $pr->currency,
            'note' => $pr->note,
            'merchant_name' => $pr->merchant_name,
            'response_note' => $pr->response_note,
            'status' => $effectiveStatus,
            'is_directed' => $pr->requestee_id !== null,
            'is_mine' => $requester && $requester->id === $meId,
            'is_for_me' => $pr->requestee_id !== null && $pr->requestee_id === $meId,
            'is_payable' => $pr->isPending() && $requester && $requester->id !== $meId,
            'requester' => $requester ? [
                'name' => $requester->full_name,
                'initials' => mb_strtoupper(mb_substr($requester->first_name ?? '', 0, 1) . mb_substr($requester->last_name ?? '', 0, 1)),
                'account_number' => 'SK' . str_pad((string) $requester->id, 8, '0', STR_PAD_LEFT),
            ] : null,
            'requestee' => $requestee ? [
                'name' => $requestee->full_name,
                'initials' => mb_strtoupper(mb_substr($requestee->first_name ?? '', 0, 1) . mb_substr($requestee->last_name ?? '', 0, 1)),
                'account_number' => 'SK' . str_pad((string) $requestee->id, 8, '0', STR_PAD_LEFT),
            ] : null,
            'expires_at' => $pr->expires_at?->toIso8601String(),
            'paid_at' => $pr->paid_at?->toIso8601String(),
            'responded_at' => $pr->responded_at?->toIso8601String(),
            'created_at' => $pr->created_at?->toIso8601String(),
        ];
    }

    /**
     * Fire a webhook callback to the payment request's callback_url (if set).
     * Best-effort: never throws — payment flow never depends on callback delivery.
     */
    private function fireCallback(PaymentRequest $pr): void
    {
        if ($pr->callback_url === null || $pr->callback_url === '') {
            return;
        }

        $payload = [
            'event' => 'payment_request.paid',
            'uuid' => $pr->uuid,
            'status' => $pr->status,
            'amount' => (float) $pr->amount,
            'currency' => $pr->currency,
            'note' => $pr->note,
            'payer_name' => $pr->payer?->full_name,
            'paid_at' => $pr->paid_at?->toIso8601String(),
        ];

        $body = (string) json_encode($payload);
        $secret = $pr->callback_secret ?? '';
        $signature = $secret !== ''
            ? hash_hmac('sha256', $body, $secret)
            : '';

        try {
            Http::timeout(10)
                ->withBody($body, 'application/json')
                ->withHeaders(array_filter([
                    'Content-Type' => 'application/json',
                    'X-SAKK-Signature' => $signature !== '' ? $signature : null,
                ]))
                ->post($pr->callback_url);
        } catch (\Throwable $e) {
            // Callback delivery is best-effort; never block the payment flow.
            \Illuminate\Support\Facades\Log::warning('PaymentRequest callback failed', [
                'uuid' => $pr->uuid,
                'callback_url' => $pr->callback_url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
