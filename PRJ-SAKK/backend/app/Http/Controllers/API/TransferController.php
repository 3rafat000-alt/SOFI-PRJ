<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\VerifiesTransactionAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transfer\LookupRequest;
use App\Http\Requests\Transfer\TransferRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Services\TransferService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    use VerifiesTransactionAuth;

    public function __construct(
        private readonly TransferService $transferService,
        private readonly AuditLogService $auditLog
    ) {}

    /**
     * Resolve a recipient by SAKK tag / email / phone for the confirm UI.
     * Returns a privacy-preserving card (never exposes the recipient's contact details).
     */
    public function lookup(LookupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $recipient = $this->transferService->resolveRecipient($validated['identifier']);

        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على مستخدم بهذا المعرّف',
            ], 404);
        }

        if ($recipient->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك التحويل إلى نفسك',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transferService->recipientCard($recipient),
        ]);
    }

    /**
     * Send money to another SAKK user (same-currency, instant, no fee).
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $sender = $request->user();

        // SEC H1: P2P transfer moves money out of the wallet, so it requires the
        // same second factor as withdraw (a hijacked session must not drain funds).
        // Fail-closed: valid PIN or verified biometric signature, else reject.
        if (!$this->verifyTransactionFactor($request, $sender)) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق الأمني — رمز PIN أو البصمة مطلوب لإتمام التحويل.',
                'code' => 'transaction_auth_required',
            ], 422);
        }

        $recipient = $this->transferService->resolveRecipient($validated['identifier']);

        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على مستخدم بهذا المعرّف',
            ], 404);
        }

        try {
            $result = $this->transferService->transfer(
                sender: $sender,
                recipient: $recipient,
                amount: (float) $validated['amount'],
                currency: $validated['currency'],
                note: $validated['note'] ?? null,
            );

            // Log the transfer for audit trail
            $this->auditLog->logTransfer(
                $sender,
                $recipient->id,
                $validated['amount'],
                $validated['currency'],
                [
                    'transaction_id' => $result['from_transaction']->id,
                    'reference' => $result['from_transaction']->reference,
                    'recipient_name' => $recipient->full_name,
                    'note' => $validated['note'] ?? null
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم التحويل بنجاح',
                'data' => [
                    'transaction' => new TransactionResource($result['from_transaction']->load(['user', 'wallet'])),
                    'amount' => (float) $result['amount'],
                    'currency' => $result['currency'],
                    'note' => $result['note'],
                    'recipient' => $result['recipient'],
                    'wallet' => new WalletResource($result['sender_wallet']),
                ],
            ]);
        } catch (\RuntimeException $e) {
            $this->auditLog->logFailure(
                'transfer.sent',
                'Transaction',
                0,
                $e->getMessage(),
                [
                    'recipient_id' => $recipient->id,
                    'amount' => $validated['amount'],
                    'currency' => $validated['currency']
                ]
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
