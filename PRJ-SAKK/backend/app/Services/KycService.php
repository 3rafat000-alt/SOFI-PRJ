<?php

namespace App\Services;

use App\Enums\KycStatus;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\KycLevel;
use App\Models\KycVerification;
use App\Models\KycDocument;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * KYC service — 3-level system (Unverified → Standard → Verified).
 *
 * Single source of truth: config/kyc.php (mirrored into the kyc_levels table).
 *
 * Two verification tracks:
 *  - email + phone        → AUTOMATIC via OTP (confirmCode). No admin involvement.
 *  - id_document + selfie → MANUAL admin acceptance. Submitted documents are
 *    created PENDING; the user is NOT levelled up until an admin reviews the
 *    images and approves (reviewVerification). A reject downgrades the user to
 *    the highest level still met. Levels are recomputed (up OR down) by
 *    syncUserLevel().
 *
 * Only id_document + selfie (self::REVIEWABLE_TYPES) ever reach the admin queue.
 */
class KycService
{
    public const VERIFIED_LEVEL = 2;

    /** Verification types that require manual admin acceptance (images reviewed). */
    public const REVIEWABLE_TYPES = ['id_document', 'selfie'];

    // ==================== Levels ====================

    /**
     * All active levels, keyed by level number, with dual-currency limits.
     */
    public function getLevels(): array
    {
        $rows = KycLevel::where('is_active', true)->orderBy('level')->get();

        if ($rows->isEmpty()) {
            return $this->configLevels();
        }

        $levels = [];
        foreach ($rows as $row) {
            $levels[$row->level] = [
                'level' => $row->level,
                'key' => $row->key,
                'name' => $row->name,
                'name_ar' => $row->name_ar,
                'description' => $row->description,
                'description_ar' => $row->description_ar,
                'requirements' => $row->requirements ?? [],
                'limits' => $this->normalizeLimits($row->limits),
                'balance_limit' => $this->normalizeBalanceLimit($row->balance_limit),
                'cards_limit' => (int) ($row->cards_limit ?? 0),
                'permissions' => [
                    'can_transfer' => (bool) $row->can_transfer,
                    'can_withdraw' => (bool) $row->can_withdraw,
                    'can_create_card' => (bool) $row->can_create_card,
                ],
            ];
        }

        return $levels;
    }

    /**
     * Fallback level definitions straight from config (keyed by level number).
     */
    protected function configLevels(): array
    {
        $levels = [];
        foreach (config('kyc.levels', []) as $def) {
            $levels[$def['level']] = [
                'level' => $def['level'],
                'key' => $def['key'],
                'name' => $def['name'],
                'name_ar' => $def['name_ar'],
                'description' => $def['description'],
                'description_ar' => $def['description_ar'],
                'requirements' => $def['requirements'],
                'limits' => $this->normalizeLimits($def['limits'] ?? []),
                'balance_limit' => $this->normalizeBalanceLimit($def['balance_limit'] ?? []),
                'cards_limit' => (int) ($def['cards_limit'] ?? 0),
                'permissions' => [
                    'can_transfer' => $def['can_transfer'],
                    'can_withdraw' => $def['can_withdraw'],
                    'can_create_card' => $def['can_create_card'],
                ],
            ];
        }

        return $levels;
    }

    /**
     * Ensure limits always expose USD + SYP with daily/monthly/single keys.
     */
    /**
     * Get the current USD→SYP exchange rate from the ExchangeRateService.
     */
    protected function sypFactor(): float
    {
        try {
            $rateData = app(ExchangeRateService::class)->getRate('USD', 'SYP');
            if ($rateData['success']) {
                return (float) $rateData['rate'];
            }
        } catch (\Throwable $e) {
            Log::warning('KycService: Failed to get exchange rate, using fallback', ['error' => $e->getMessage()]);
        }
        return (float) config('kyc.syp_factor', 13000);
    }

    protected function normalizeLimits(?array $limits): array
    {
        $factor = $this->sypFactor();
        $usd = $limits['USD'] ?? ['daily' => 0, 'monthly' => 0, 'single' => 0];
        $syp = $limits['SYP'] ?? [
            'daily' => ($usd['daily'] ?? 0) * $factor,
            'monthly' => ($usd['monthly'] ?? 0) * $factor,
            'single' => ($usd['single'] ?? 0) * $factor,
        ];

        return [
            'USD' => [
                'daily' => (float) ($usd['daily'] ?? 0),
                'monthly' => (float) ($usd['monthly'] ?? 0),
                'single' => (float) ($usd['single'] ?? 0),
            ],
            'SYP' => [
                'daily' => (float) ($syp['daily'] ?? 0),
                'monthly' => (float) ($syp['monthly'] ?? 0),
                'single' => (float) ($syp['single'] ?? 0),
            ],
        ];
    }

    /**
     * Ensure balance_limit always exposes USD + SYP.
     */
    protected function normalizeBalanceLimit(?array $balanceLimit): array
    {
        $factor = $this->sypFactor();
        $usd = $balanceLimit['USD'] ?? 0;
        $syp = $balanceLimit['SYP'] ?? ($usd * $factor);

        return [
            'USD' => (float) $usd,
            'SYP' => (float) $syp,
        ];
    }

    /**
     * The limits map for a given user (based on their current level).
     */
    public function limitsForUser(User $user): array
    {
        $levels = $this->getLevels();
        $level = $user->kyc_level ?? 0;

        return ($levels[$level] ?? $levels[0] ?? ['limits' => $this->normalizeLimits([])])['limits'];
    }

    public function balanceLimitForUser(User $user): array
    {
        $levels = $this->getLevels();
        $level = $user->kyc_level ?? 0;

        return ($levels[$level] ?? $levels[0] ?? ['balance_limit' => $this->normalizeBalanceLimit([])])['balance_limit'] ?? $this->normalizeBalanceLimit([]);
    }

    public function cardsLimitForUser(User $user): int
    {
        $levels = $this->getLevels();
        $level = $user->kyc_level ?? 0;

        return ($levels[$level] ?? $levels[0] ?? ['cards_limit' => 0])['cards_limit'] ?? 0;
    }

    public function permissionsForUser(User $user): array
    {
        $levels = $this->getLevels();
        $level = $user->kyc_level ?? 0;

        return ($levels[$level] ?? $levels[0] ?? ['permissions' => []])['permissions'] ?? [];
    }

    /**
     * Enforce the user's KYC-level financial caps for an outbound money op.
     *
     * Shared by TransferService and WalletService::withdraw so the same caps hold
     * on BOTH channels — the cap is on identity, not on the channel. Cumulative
     * daily/monthly are summed across ALL outbound money (transfer_out + withdrawal)
     * so a user cannot split a large outflow across the two channels to evade the
     * cap. MUST be called INSIDE the wallet's locked transaction, before the debit,
     * so concurrent requests can't each pass the check and then all debit.
     *
     * @param string $context 'transfer' | 'withdrawal' — selects the permission gate + message.
     * @throws \RuntimeException when a permission or limit would be exceeded.
     */
    public function assertWithinKycLimits(User $user, float $amount, string $currency, string $context = 'transfer'): void
    {
        $permissions = $this->permissionsForUser($user);
        $isWithdrawal = $context === 'withdrawal';
        $permKey = $isWithdrawal ? 'can_withdraw' : 'can_transfer';
        if (!($permissions[$permKey] ?? false)) {
            throw new \RuntimeException(
                $isWithdrawal
                    ? 'السحب غير متاح لمستوى حسابك الحالي'
                    : 'التحويل غير متاح لمستوى حسابك الحالي'
            );
        }

        $limit = $this->limitsForUser($user)[$currency] ?? null;
        if (!$limit) {
            throw new \RuntimeException('لا توجد حدود معرّفة لهذه العملة');
        }

        $single = (float) ($limit['single'] ?? 0);
        $daily = (float) ($limit['daily'] ?? 0);
        $monthly = (float) ($limit['monthly'] ?? 0);

        $hint = $user->isVerified() ? '' : ' وثّق حسابك لرفع الحدود.';

        if ($amount > $single) {
            throw new \RuntimeException(
                'المبلغ يتجاوز حد المعاملة الواحدة (' . \App\Support\Money::format($single, $currency) . ').' . $hint
            );
        }

        // Cumulative outbound = money leaving the user this period on any channel.
        $outbound = [\App\Enums\TransactionType::TRANSFER_OUT, \App\Enums\TransactionType::WITHDRAWAL];

        $dailySpent = abs((float) \App\Models\Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->whereIn('type', $outbound)
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('amount'));
        if ($dailySpent + $amount > $daily) {
            throw new \RuntimeException(
                'تجاوزت الحد اليومي (' . \App\Support\Money::format($daily, $currency) . ').' . $hint
            );
        }

        $monthlySpent = abs((float) \App\Models\Transaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->whereIn('type', $outbound)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount'));
        if ($monthlySpent + $amount > $monthly) {
            throw new \RuntimeException(
                'تجاوزت الحد الشهري (' . \App\Support\Money::format($monthly, $currency) . ').' . $hint
            );
        }
    }

    // ==================== Status ====================

    /**
     * Full KYC status payload for the mobile app.
     */
    public function getUserKycStatus(User $user): array
    {
        $levels = $this->getLevels();
        $currentLevel = $user->kyc_level ?? 0;
        $currentData = $levels[$currentLevel] ?? $levels[0];
        $nextData = $levels[$currentLevel + 1] ?? null;

        $verifications = [];
        foreach (config('kyc.verification_types', []) as $type) {
            $verifications[$type] = $this->verificationState($user, $type);
        }

        return [
            'current_level' => $currentLevel,
            'is_verified' => $currentLevel >= self::VERIFIED_LEVEL,
            'is_standard' => $currentLevel >= 1,
            'status' => $user->kyc_status instanceof KycStatus ? $user->kyc_status->value : (string) $user->kyc_status,
            'status_label_ar' => $user->kyc_status instanceof KycStatus ? $user->kyc_status->labelAr() : 'غير موثّق',
            'level_name' => $currentData['name'],
            'level_name_ar' => $currentData['name_ar'],
            'limits' => $currentData['limits'],
            'balance_limit' => $currentData['balance_limit'],
            'cards_limit' => $currentData['cards_limit'],
            'permissions' => $currentData['permissions'],
            'verifications' => $verifications,
            'next_level' => $nextData ? [
                'level' => $nextData['level'],
                'name' => $nextData['name'],
                'name_ar' => $nextData['name_ar'],
                'requirements' => $nextData['requirements'],
                'limits' => $nextData['limits'],
                'balance_limit' => $nextData['balance_limit'],
                'cards_limit' => $nextData['cards_limit'],
            ] : null,
            'missing_requirements' => $this->missingRequirements($user, $currentLevel),
        ];
    }

    /**
     * Per-type state, including the admin-review flag.
     */
    protected function verificationState(User $user, string $type): array
    {
        if ($type === 'email') {
            return [
                'status' => $user->email_verified_at ? 'approved' : 'not_started',
                'pending_review' => false,
                'rejection_reason' => null,
            ];
        }

        if ($type === 'phone') {
            return [
                'status' => $user->phone_verified_at ? 'approved' : 'not_started',
                'pending_review' => false,
                'rejection_reason' => null,
            ];
        }

        $latest = KycVerification::where('user_id', $user->id)
            ->where('verification_type', $type)
            ->latest('id')
            ->first();

        return [
            'status' => $latest?->status ?? 'not_started',
            // Submitted and awaiting manual admin acceptance.
            'pending_review' => $latest && $latest->status === VerificationStatus::PENDING->value,
            'submitted_at' => $latest?->created_at,
            'reviewed_at' => $latest?->reviewed_at,
            'rejection_reason' => $latest?->rejection_reason,
        ];
    }

    /**
     * Requirements still needed to reach the NEXT level.
     */
    public function missingRequirements(User $user, ?int $currentLevel = null): array
    {
        $levels = $this->getLevels();
        $currentLevel ??= $user->kyc_level ?? 0;
        $nextLevel = $levels[$currentLevel + 1] ?? null;

        if (!$nextLevel) {
            return [];
        }

        $missing = [];
        foreach (($nextLevel['requirements'] ?? []) as $req) {
            if (!$this->requirementMet($user, $req)) {
                $missing[] = $req;
            }
        }

        return $missing;
    }

    protected function requirementMet(User $user, string $req): bool
    {
        return match ($req) {
            'email' => $user->email_verified_at !== null,
            'phone' => $user->phone_verified_at !== null,
            default => KycVerification::where('user_id', $user->id)
                ->where('verification_type', $req)
                ->where('status', VerificationStatus::APPROVED->value)
                ->exists(),
        };
    }

    // ==================== Email ====================

    public function sendEmailVerification(User $user): array
    {
        if ($user->email_verified_at) {
            return ['success' => false, 'error' => 'البريد الإلكتروني موثّق مسبقاً'];
        }

        $code = $this->generateCode();
        $ttl = (int) config('kyc.email_code_ttl_minutes', 15);

        $this->createVerificationRecord([
            'user_id' => $user->id,
            'level' => self::VERIFIED_LEVEL,
            'verification_type' => 'email',
            'status' => VerificationStatus::PENDING->value,
            'extracted_data' => ['code' => $code, 'expires_at' => now()->addMinutes($ttl)->timestamp],
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ]);

        try {
            Mail::to($user->email)->send(new \App\Mail\VerificationCodeMail($code));
            Log::info('KYC email code sent', ['user_id' => $user->id, 'email' => $this->maskEmail($user->email)]);
        } catch (\Throwable $e) {
            // Mail transport failed (bad SMTP creds, host unreachable, auth rejected).
            // Do NOT report success — the user would wait for a code that never arrives.
            Log::error('KYC email code failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'تعذّر إرسال رمز التحقق حالياً، يرجى المحاولة لاحقاً',
            ];
        }

        return [
            'success' => true,
            'message' => 'تم إرسال رمز التحقق إلى ' . $this->maskEmail($user->email),
            // SEC M5: never expose the OTP over the API in production. Only unit
            // tests (not APP_DEBUG) get the code back — APP_DEBUG left on in prod
            // would otherwise hand any caller a full OTP bypass.
            'code' => app()->runningUnitTests() ? $code : null,
        ];
    }

    public function verifyEmailCode(User $user, string $code): array
    {
        $result = $this->confirmCode($user, 'email', $code);
        if (!$result['success']) {
            return $result;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->syncUserLevel($user);

        return [
            'success' => true,
            'message' => 'تم التحقق من البريد الإلكتروني بنجاح',
            'kyc_level' => $user->fresh()->kyc_level,
        ];
    }

    // ==================== Phone ====================

    public function sendPhoneVerification(User $user): array
    {
        if (!$user->phone) {
            return ['success' => false, 'error' => 'لا يوجد رقم هاتف مسجّل'];
        }
        if ($user->phone_verified_at) {
            return ['success' => false, 'error' => 'رقم الهاتف موثّق مسبقاً'];
        }

        $code = $this->generateCode();
        $ttl = (int) config('kyc.phone_code_ttl_minutes', 10);

        $this->createVerificationRecord([
            'user_id' => $user->id,
            'level' => self::VERIFIED_LEVEL,
            'verification_type' => 'phone',
            'status' => VerificationStatus::PENDING->value,
            'extracted_data' => ['code' => $code, 'expires_at' => now()->addMinutes($ttl)->timestamp],
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ]);

        // Deliver the code. Prefer Telegram when the user has linked it, else
        // fall back to the WhatsApp (OpenWA) gateway. Both are safe no-ops when
        // disabled/misconfigured — the code is still issued and, in debug,
        // returned in the response, so verification is never blocked.
        $message = "صكك (SAKK)\nرمز التحقق الخاص بك: {$code}\nصالح لمدة {$ttl} دقائق. لا تشارك هذا الرمز مع أحد.";
        [$channel, $sent] = $this->deliverPhoneOtp($user, $code, $message);

        Log::info('KYC phone code issued', [
            'user_id' => $user->id,
            'phone' => $this->maskPhone($user->phone),
            'channel' => $channel,
            'sent' => $sent,
        ]);

        // SEC: if every delivery channel failed, do not tell the caller the code
        // was sent — the user would be stuck waiting for an OTP that never
        // arrives with no way to know it failed. Unit tests run with no
        // delivery channels configured (always $sent===false) and assert on
        // the returned 'code', so this check is skipped under the test runner
        // — mirrors the existing SEC M5 debug-code convention above.
        if ($sent === false && !app()->runningUnitTests()) {
            return [
                'success' => false,
                'message' => 'تعذّر إرسال رمز التحقق، حاول لاحقاً',
            ];
        }

        return [
            'success' => true,
            'message' => 'تم إرسال رمز التحقق إلى ' . $this->maskPhone($user->phone),
            // SEC M5: never expose the OTP over the API in production. Only unit
            // tests (not APP_DEBUG) get the code back — APP_DEBUG left on in prod
            // would otherwise hand any caller a full OTP bypass.
            'code' => app()->runningUnitTests() ? $code : null,
        ];
    }

    /**
     * Deliver a phone OTP down the channel priority chain (first success wins):
     *
     *   1. Telegram bot   — FREE, only if the user already linked a chat.
     *   2. Telegram Gateway — auto-detects whether the number has Telegram
     *                         (checkSendAbility) and delivers our code; PAID,
     *                         no linking needed. This is the "detect the app on
     *                         the user's phone and send" path.
     *   3. WhatsApp       — OpenWA gateway.
     *   4. SMS            — provider stub ("soon").
     *
     * Every channel is a safe no-op when disabled, so the chain degrades
     * gracefully. Returns the channel that delivered (or 'none') + success.
     *
     * @return array{0:string,1:bool} [channel, sent]
     */
    protected function deliverPhoneOtp(User $user, string $code, string $message): array
    {
        // 1) Telegram bot — free, for users who linked their chat.
        $bot = app(\App\Services\TelegramService::class);
        if ($user->telegram_chat_id && $bot->enabled()
            && $bot->sendMessage((string) $user->telegram_chat_id, $message)) {
            return ['telegram_bot', true];
        }

        // 2) Telegram Gateway — auto-detect presence by phone, then send our code.
        $gateway = app(\App\Services\TelegramGatewayService::class);
        if ($gateway->enabled()) {
            $requestId = $gateway->checkSendAbility($user->phone);
            if ($requestId !== null && $gateway->sendCode($user->phone, $code, $requestId)) {
                return ['telegram_gateway', true];
            }
        }

        // 3) WhatsApp.
        if (app(\App\Services\WhatsAppService::class)->sendText($user->phone, $message)) {
            return ['whatsapp', true];
        }

        // 4) SMS (soon).
        if (app(\App\Services\SmsService::class)->sendText($user->phone, $message)) {
            return ['sms', true];
        }

        return ['none', false];
    }

    public function verifyPhoneCode(User $user, string $code): array
    {
        $result = $this->confirmCode($user, 'phone', $code);
        if (!$result['success']) {
            return $result;
        }

        $user->forceFill(['phone_verified_at' => now()])->save();
        $this->syncUserLevel($user);

        // Newly trusted phone → release any salary held for it until activation.
        // Non-critical: never let a payroll hiccup fail phone verification.
        rescue(fn () => app(\App\Services\PayrollService::class)->releaseHeldFor($user));

        return [
            'success' => true,
            'message' => 'تم التحقق من رقم الهاتف بنجاح',
            'kyc_level' => $user->fresh()->kyc_level,
        ];
    }

    /**
     * Shared OTP confirmation for email/phone.
     */
    protected function confirmCode(User $user, string $type, string $code): array
    {
        $verification = KycVerification::where('user_id', $user->id)
            ->where('verification_type', $type)
            ->where('status', VerificationStatus::PENDING->value)
            ->latest('id')
            ->first();

        if (!$verification) {
            return ['success' => false, 'error' => 'لا يوجد طلب تحقق معلّق'];
        }

        $storedCode = $verification->extracted_data['code'] ?? null;
        $expiresAt = $verification->extracted_data['expires_at'] ?? 0;

        if (now()->timestamp > (int) $expiresAt) {
            return ['success' => false, 'error' => 'انتهت صلاحية رمز التحقق'];
        }

        // SEC H8: per-code attempt cap. A 6-digit code is only ~10^6 wide; without
        // a hard cap (and even with route throttling) it is brute-forceable. After
        // N wrong guesses we BURN the code (mark the request rejected) so a new one
        // must be requested — combined with the throttle:otp on the verify route.
        if (!hash_equals((string) $storedCode, (string) $code)) {
            $maxAttempts = (int) config('kyc.max_otp_attempts', 5);
            $data = $verification->extracted_data;
            $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;

            if ($data['attempts'] >= $maxAttempts) {
                $verification->forceFill([
                    'extracted_data' => $data,
                    'status' => VerificationStatus::REJECTED->value,
                    'rejection_reason' => 'too_many_otp_attempts',
                ])->save();

                return ['success' => false, 'error' => 'تجاوزت عدد المحاولات المسموح بها. اطلب رمزاً جديداً.'];
            }

            $verification->forceFill(['extracted_data' => $data])->save();

            return ['success' => false, 'error' => 'رمز التحقق غير صحيح'];
        }

        // OTP-confirmed steps need no human review → mark reviewed immediately.
        // status/reviewed_at are guarded (SEC-003) — forceFill or they are dropped.
        $verification->forceFill([
            'status' => VerificationStatus::APPROVED->value,
            'reviewed_at' => now(),
        ])->save();

        return ['success' => true];
    }

    // ==================== Documents (auto-approve + review flag) ====================

    public function submitIdDocument(User $user, string $documentType, $frontImage, $backImage = null): array
    {
        $frontPath = $frontImage->store("kyc/{$user->id}/id", 'private');
        $backPath = $backImage ? $backImage->store("kyc/{$user->id}/id", 'private') : null;

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => $documentType,
            'file_path' => $frontPath,
            'file_name' => $frontImage->getClientOriginalName() ?: basename($frontPath),
            'file_type' => $frontImage->getClientMimeType() ?: 'image/jpeg',
            'file_size' => (int) $frontImage->getSize(),
            'status' => VerificationStatus::PENDING->value, // 🔒 Now PENDING until admin reviews
            'extracted_data' => ['back_path' => $backPath],
        ]);

        $this->recordVerification($user, 'id_document', $frontPath, $documentType, ['back_path' => $backPath]);
        $this->syncUserLevel($user);
        $this->notifyDocumentReceived($user, 'id_document');

        return [
            'success' => true,
            'message' => 'تم استلام وثيقة الهوية. ستخضع للمراجعة.',
            'kyc_level' => $user->fresh()->kyc_level,
        ];
    }

    public function submitSelfie(User $user, $selfieImage): array
    {
        $path = $selfieImage->store("kyc/{$user->id}/selfie", 'private');

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'selfie',
            'file_path' => $path,
            'file_name' => $selfieImage->getClientOriginalName() ?: basename($path),
            'file_type' => $selfieImage->getClientMimeType() ?: 'image/jpeg',
            'file_size' => (int) $selfieImage->getSize(),
            'status' => VerificationStatus::PENDING->value, // 🔒 PENDING until admin review
        ]);

        $this->recordVerification($user, 'selfie', $path, null);
        $this->syncUserLevel($user);
        $this->notifyDocumentReceived($user, 'selfie');

        return [
            'success' => true,
            'message' => 'تم استلام الصورة الشخصية. ستخضع للمراجعة.',
            'kyc_level' => $user->fresh()->kyc_level,
        ];
    }

    /**
     * Optional address proof (not required for any level, kept for completeness).
     */
    public function submitAddressProof(User $user, $document, string $documentType): array
    {
        $path = $document->store("kyc/{$user->id}/address", 'private');

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'proof_of_address',
            'file_path' => $path,
            'file_name' => $document->getClientOriginalName() ?: basename($path),
            'file_type' => $document->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => (int) $document->getSize(),
            'status' => VerificationStatus::PENDING->value, // 🔒 PENDING until admin review
            'extracted_data' => ['address_document_type' => $documentType],
        ]);

        $this->recordVerification($user, 'address_proof', $path, $documentType);
        $this->notifyDocumentReceived($user, 'address_proof');

        return [
            'success' => true,
            'message' => 'تم استلام إثبات العنوان. سيخضع للمراجعة.',
            'kyc_level' => $user->fresh()->kyc_level,
        ];
    }

    /**
     * Create or update a verification row, bypassing $fillable for guarded
     * fields (status, reviewed_at, reviewed_by, rejection_reason — SEC-003).
     * These are internal state fields that must NOT come from user input.
     */
    protected function createVerificationRecord(array $data): KycVerification
    {
        $unique = array_intersect_key($data, array_flip(['user_id', 'verification_type']));
        /** @var KycVerification $verification */
        $verification = KycVerification::firstOrNew($unique);
        $verification->forceFill($data);
        $verification->save();

        return $verification;
    }

    protected function recordVerification(User $user, string $type, string $path, ?string $documentType, array $extra = []): void
    {
        // Supersede any previous row for a clean latest state. Identity documents
        // (id_document, selfie) require MANUAL admin acceptance: the row is PENDING
        // and the user is NOT levelled up until an admin reviews the images and
        // approves via reviewVerification() (requirementMet checks APPROVED). This
        // is the "عملية قبول التوثيق". Email/phone are auto-verified via OTP and
        // never pass through here.
        $this->createVerificationRecord([
            'user_id' => $user->id,
            'level' => self::VERIFIED_LEVEL,
            'verification_type' => $type,
            'status' => VerificationStatus::PENDING->value,
            'document_path' => $path,
            'document_type' => $documentType,
            'extracted_data' => array_merge(['submitted_at' => now()->toIso8601String()], $extra),
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ]);
    }

    // ==================== Level sync (up OR down) ====================

    /**
     * Recompute the user's exact level from met requirements and persist
     * level + status + mirrored limits. Handles upgrades AND downgrades.
     */
    public function syncUserLevel(User $user): int
    {
        $levels = $this->getLevels();
        $oldLevel = $user->kyc_level ?? 0;

        // Highest level whose requirements are all met.
        $newLevel = 0;
        foreach ($levels as $level) {
            $allMet = true;
            foreach (($level['requirements'] ?? []) as $req) {
                if (!$this->requirementMet($user, $req)) {
                    $allMet = false;
                    break;
                }
            }
            if ($allMet && $level['level'] > $newLevel) {
                $newLevel = $level['level'];
            }
        }

        // Was any required document explicitly rejected by an admin?
        $hasRejection = KycVerification::where('user_id', $user->id)
            ->whereIn('verification_type', config('kyc.verification_types', []))
            ->where('status', VerificationStatus::REJECTED->value)
            ->exists();

        $status = match (true) {
            $newLevel >= self::VERIFIED_LEVEL => KycStatus::VERIFIED,
            $hasRejection => KycStatus::REJECTED,
            default => KycStatus::PENDING,
        };

        $usd = ($levels[$newLevel] ?? $levels[0])['limits']['USD'];

        $user->forceFill([
            'kyc_level' => $newLevel,
            'kyc_status' => $status->value,
            'kyc_verified_at' => $newLevel >= self::VERIFIED_LEVEL ? ($user->kyc_verified_at ?? now()) : null,
            'daily_limit' => $usd['daily'],
            'monthly_limit' => $usd['monthly'],
        ])->save();

        if ($newLevel > $oldLevel) {
            $this->notifyLevelChange($user, $oldLevel, $newLevel);
        }

        return $newLevel;
    }

    // ==================== Admin review ====================

    /**
     * Admin reviews a flagged verification: approve (clear flag) or reject (downgrade).
     */
    public function reviewVerification(KycVerification $verification, User $admin, string $decision, ?string $reason = null): array
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return ['success' => false, 'error' => 'قرار غير صالح'];
        }

        $targetStatus = $decision === 'approved' ? VerificationStatus::APPROVED->value : VerificationStatus::REJECTED->value;

        $result = DB::transaction(function () use ($verification, $admin, $decision, $reason, $targetStatus) {
            /** @var KycVerification $locked */
            $locked = KycVerification::whereKey($verification->getKey())->lockForUpdate()->firstOrFail();

            // Idempotency: only short-circuit a genuine double-review (same decision
            // replayed on an already-resolved row). A different decision than the
            // current status (e.g. admin later reverses approved -> rejected) is a
            // legitimate re-review and must still be applied.
            if ($locked->status === $targetStatus) {
                return [
                    'short_circuit' => true,
                    'user' => $locked->user,
                    'verification' => $locked,
                ];
            }

            $locked->forceFill([
                'status' => $targetStatus,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $decision === 'rejected' ? $reason : null,
            ])->save();

            $user = $locked->user;

            // Keep the matching KycDocument in sync.
            if (in_array($locked->verification_type, ['id_document', 'selfie', 'address_proof'], true)) {
                KycDocument::where('user_id', $user->id)
                    ->where('file_path', $locked->document_path)
                    ->update([
                        'status' => $targetStatus,
                        'verified_by' => $admin->id,
                        'verified_at' => now(),
                        'rejection_reason' => $decision === 'rejected' ? $reason : null,
                    ]);
            }

            return [
                'short_circuit' => false,
                'user' => $user,
                'verification' => $locked,
            ];
        });

        $user = $result['user'];

        if ($result['short_circuit']) {
            return [
                'success' => true,
                'message' => $decision === 'approved' ? 'تمت الموافقة' : 'تم الرفض',
                'kyc_level' => $user->fresh()->kyc_level,
            ];
        }

        $this->syncUserLevel($user);

        if ($decision === 'rejected') {
            $this->createNotification(
                $user,
                'kyc_rejected',
                'تم رفض مستند التحقق',
                'عذراً، تم رفض أحد مستنداتك: ' . ($reason ?? 'يرجى إعادة الرفع'),
                ['verification_type' => $verification->verification_type, 'reason' => $reason]
            );
        }

        return [
            'success' => true,
            'message' => $decision === 'approved' ? 'تمت الموافقة' : 'تم الرفض',
            'kyc_level' => $user->fresh()->kyc_level,
        ];
    }

    // ==================== Helpers ====================

    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        $masked = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 4)) . substr($name, -2);

        return $masked . '@' . $domain;
    }

    protected function maskPhone(string $phone): string
    {
        return substr($phone, 0, 4) . '****' . substr($phone, -3);
    }

    protected function createNotification(User $user, string $code, string $title, string $body, ?array $data = null): void
    {
        UserNotification::create([
            'user_id' => $user->id,
            'uuid' => Str::uuid(),
            'template_code' => $code,
            'channel' => 'in_app',
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sent_at' => now(),
            'status' => 'sent',
        ]);
    }

    protected function notifyDocumentReceived(User $user, string $type): void
    {
        $labels = [
            'id_document' => 'وثيقة الهوية',
            'selfie' => 'الصورة الشخصية',
            'address_proof' => 'إثبات العنوان',
        ];
        $label = $labels[$type] ?? $type;

        $this->createNotification(
            $user,
            'kyc_document_received',
            'تم استلام مستندك',
            "تم استلام {$label} وستتم مراجعته قريباً.",
            ['verification_type' => $type]
        );
    }

    protected function notifyLevelChange(User $user, int $oldLevel, int $newLevel): void
    {
        $levelNames = [
            0 => 'غير موثّق',
            1 => 'موثّق أساسي',
            2 => 'موثّق كامل',
        ];
        $name = $levelNames[$newLevel] ?? 'مستوى جديد';

        $this->createNotification(
            $user,
            'kyc_level_up',
            'ترقية مستوى التوثيق',
            "تهانينا! وصلت إلى مستوى: {$name}. استمتع بالحدود والميزات الجديدة.",
            ['old_level' => $oldLevel, 'new_level' => $newLevel]
        );
    }
}
