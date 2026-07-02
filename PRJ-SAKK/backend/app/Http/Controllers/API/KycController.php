<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\VerifiesTransactionAuth;
use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\KycService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KycController extends Controller
{
    use VerifiesTransactionAuth;

    public function __construct(protected KycService $kycService) {}

    /**
     * KYC level definitions (2-level system, dual-currency limits).
     */
    public function getLevels(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => array_values($this->kycService->getLevels()),
        ]);
    }

    /**
     * The authenticated user's full KYC status.
     */
    public function getStatus(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->kycService->getUserKycStatus($request->user()),
        ]);
    }

    /**
     * History of the user's verification submissions.
     */
    public function getSubmissionStatus(Request $request): JsonResponse
    {
        $verifications = KycVerification::where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'type' => $v->verification_type,
                'status' => $v->status,
                'pending_review' => $v->status === 'pending',
                'rejection_reason' => $v->rejection_reason,
                'submitted_at' => $v->created_at,
                'reviewed_at' => $v->reviewed_at,
            ]);

        return response()->json(['success' => true, 'data' => $verifications]);
    }

    // ==================== Email ====================

    public function sendEmailCode(Request $request): JsonResponse
    {
        $result = $this->kycService->sendEmailVerification($request->user());

        $extra = [];
        if (app()->environment('local', 'testing')) {
            $extra['code'] = $result['code'] ?? null;
        }

        return $this->respond($result, $extra);
    }

    public function verifyEmailCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        $result = $this->kycService->verifyEmailCode($request->user(), $request->code);

        return $this->respond($result, ['kyc_level' => $result['kyc_level'] ?? null]);
    }

    // ==================== Phone ====================

    public function sendPhoneCode(Request $request): JsonResponse
    {
        $result = $this->kycService->sendPhoneVerification($request->user());

        return $this->respond($result, ['code' => $result['code'] ?? null]);
    }

    public function verifyPhoneCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        $result = $this->kycService->verifyPhoneCode($request->user(), $request->code);

        return $this->respond($result, ['kyc_level' => $result['kyc_level'] ?? null]);
    }

    public function updatePhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:8|unique:users,phone,' . $request->user()->id,
        ]);

        $user = $request->user();

        // SEC M9: CHANGING an already-verified phone re-routes future OTPs (and
        // held-payroll release), so it requires a second factor — a hijacked
        // session must not pivot the OTP destination. First-time phone entry
        // (no verified phone yet, e.g. onboarding) is NOT gated: the user may not
        // have a PIN, and there is nothing to re-route yet.
        if ($user->phone_verified_at !== null) {
            $request->validate(['pin' => 'required_without:biometric_token|string']);

            if (!$this->verifyTransactionFactor($request, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق الأمني — رمز PIN أو البصمة مطلوب لتغيير رقم الهاتف.',
                    'code' => 'transaction_auth_required',
                ], 422);
            }
        }

        $user->forceFill([
            'phone' => $request->phone,
            'phone_verified_at' => null,
        ])->save();

        // Phone is a verified-level requirement → recompute (may downgrade).
        $this->kycService->syncUserLevel($user);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث رقم الهاتف. يرجى التحقق من الرقم الجديد.',
        ]);
    }

    // ==================== Documents ====================

    public function submitIdDocument(Request $request): JsonResponse
    {
        $types = implode(',', config('kyc.id_document_types', ['national_id', 'passport', 'drivers_license']));

        $request->validate([
            'document_type' => "required|in:{$types}",
            'front_image' => 'required|image|max:5120',
            'back_image' => 'nullable|image|max:5120',
        ]);

        $result = $this->kycService->submitIdDocument(
            user: $request->user(),
            documentType: $request->document_type,
            frontImage: $request->file('front_image'),
            backImage: $request->file('back_image'),
        );

        if ($result['success'] ?? false) {
            rescue(fn () => \App\Services\AdminNotificationService::pendingKyc($request->user(), 'مستند الهوية'));
        }

        return $this->respond($result, ['kyc_level' => $result['kyc_level'] ?? null]);
    }

    public function submitSelfie(Request $request): JsonResponse
    {
        $request->validate(['selfie' => 'required|image|max:5120']);

        $result = $this->kycService->submitSelfie(
            user: $request->user(),
            selfieImage: $request->file('selfie'),
        );

        if ($result['success'] ?? false) {
            rescue(fn () => \App\Services\AdminNotificationService::pendingKyc($request->user(), 'الصورة الشخصية'));
        }

        return $this->respond($result, ['kyc_level' => $result['kyc_level'] ?? null]);
    }

    public function submitAddressProof(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|in:utility_bill,bank_statement,government_letter,rental_agreement',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $result = $this->kycService->submitAddressProof(
            user: $request->user(),
            document: $request->file('document'),
            documentType: $request->document_type,
        );

        if ($result['success'] ?? false) {
            rescue(fn () => \App\Services\AdminNotificationService::pendingKyc($request->user(), 'إثبات العنوان'));
        }

        return $this->respond($result, ['kyc_level' => $result['kyc_level'] ?? null]);
    }

    /**
     * Consistent JSON envelope for service results.
     */
    protected function respond(array $result, array $extra = []): JsonResponse
    {
        if (!($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'فشل الطلب',
            ], 400);
        }

        return response()->json(array_merge([
            'success' => true,
            'message' => $result['message'] ?? 'تم بنجاح',
        ], array_filter($extra, fn ($v) => $v !== null)));
    }
}
