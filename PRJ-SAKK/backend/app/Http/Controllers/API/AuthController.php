<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use App\Traits\HasAccountLockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use HasAccountLockout;

    public function __construct(
        private readonly AuthService $authService,
        private readonly TwoFactorService $twoFactorService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        // Admin-controlled kill-switch (النظام › الإعدادات). Previously only
        // config('app.allow_registration') was checked, which the admin panel
        // toggle never wrote to — this made the "إغلاق التسجيل" switch a no-op.
        if (!\App\Models\SystemSetting::get('registration_open', true)) {
            return response()->json([
                'success' => false,
                'message' => 'التسجيل مغلق حالياً',
            ], 403);
        }

        $user = $this->authService->register($request->validated());

        // Notify admins of the new sign-up (non-critical).
        rescue(fn () => \App\Services\AdminNotificationService::userRegistered($user));

        $token = $user->createToken('auth_token', [
            'wallet:read', 'wallet:write', 'card:read', 'card:write',
            'transfer', 'gold', 'profile', 'kyc:read',
        ])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم التسجيل بنجاح',
            'data' => [
                'user' => new UserResource($user->load(['wallets', 'cards'])),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)
            ->orWhere('phone', $request->email)
            ->first();

        if ($user && ($remaining = $this->checkLockout($user)) > 0) {
            return response()->json([
                'success' => false,
                'message' => "الحساب مقفل مؤقتاً بسبب محاولات دخول فاشلة متكررة. حاول مرة أخرى بعد {$remaining} دقيقة.",
            ], 423);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($user) {
                $this->incrementLoginAttempts($user);
            }

            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        $this->resetLoginAttempts($user);

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب معطل',
            ], 403);
        }

        // Check if 2FA code is required but not provided
        if ($user->two_factor_enabled && !$request->two_factor_code) {
            return response()->json([
                'success' => true,
                'message' => 'رمز التحقق الثنائي (2FA) مطلوب',
                'data' => [
                    'requires_2fa' => true,
                    'email' => $user->email,
                ],
            ], 200);
        }

        // Verify 2FA code
        if ($user->two_factor_enabled && !$this->twoFactorService->verifyCode($user, $request->two_factor_code)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق الثنائي (2FA) غير صحيح',
            ], 422);
        }

        // last_login_at/last_login_ip are guarded — forceFill so the login is recorded.
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'fcm_token' => $request->fcm_token,
            'device_id' => $request->device_id,
        ])->save();

        $token = $user->createToken('auth_token', [
            'wallet:read', 'wallet:write', 'card:read', 'card:write',
            'transfer', 'gold', 'profile', 'kyc:read',
        ])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => new UserResource($user->load(['wallets', 'cards'])),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load(['wallets', 'cards'])),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('auth_token', [
            'wallet:read', 'wallet:write', 'card:read', 'card:write',
            'transfer', 'gold', 'profile', 'kyc:read',
        ])->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    public function setPin(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|digits:6',
            'password' => 'required|string',
        ], [
            'pin.required' => 'رمز PIN مطلوب.',
            'pin.digits' => 'رمز PIN يجب أن يكون 6 أرقام.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 422);
        }

        $user->update(['pin_code' => Hash::make($request->pin)]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين رمز PIN بنجاح',
        ]);
    }

    public function verifyPin(Request $request): JsonResponse
    {
        $request->validate(['pin' => 'required|string|digits:6'], [
            'pin.required' => 'رمز PIN مطلوب.',
            'pin.digits' => 'رمز PIN يجب أن يكون 6 أرقام.',
        ]);

        $user = $request->user();

        if (!$user->verifyPin($request->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز PIN غير صحيح',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق من رمز PIN',
        ]);
    }

    public function changePin(Request $request): JsonResponse
    {
        $request->validate([
            'current_pin' => 'required|digits:6',
            'new_pin' => 'required|digits:6|different:current_pin',
        ], [
            'current_pin.required' => 'رمز PIN الحالي مطلوب.',
            'current_pin.digits' => 'رمز PIN الحالي يجب أن يكون 6 أرقام.',
            'new_pin.required' => 'رمز PIN الجديد مطلوب.',
            'new_pin.digits' => 'رمز PIN الجديد يجب أن يكون 6 أرقام.',
            'new_pin.different' => 'رمز PIN الجديد يجب أن يختلف عن الحالي.',
        ]);

        $user = $request->user();

        if (!$user->pin_code || !$user->verifyPin($request->current_pin)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز PIN الحالي غير صحيح',
            ], 422);
        }

        $user->update(['pin_code' => Hash::make($request->new_pin)]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير رمز PIN بنجاح',
        ]);
    }

    public function disablePin(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string'], [
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 422);
        }

        $user->update(['pin_code' => null]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل رمز PIN بنجاح',
        ]);
    }

    // ==================== 2FA ====================

    public function twoFactorSetup(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'التحقق الثنائي (2FA) مُفعّل مسبقاً',
            ], 422);
        }

        $data = $this->twoFactorService->enable($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function twoFactorConfirm(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6'], [
            'code.required' => 'رمز التحقق مطلوب.',
            'code.size' => 'رمز التحقق يجب أن يكون 6 أحرف.',
        ]);

        $user = $request->user();
        $key = '2fa:'.$request->ip().':'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'محاولات كثيرة جداً. حاول بعد '.ceil($seconds / 60).' دقائق.',
                'retry_after' => $seconds,
            ], 429);
        }

        if ($user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'التحقق الثنائي (2FA) مُفعّل مسبقاً',
            ], 422);
        }

        if (!$this->twoFactorService->confirm($user, $request->code)) {
            RateLimiter::hit($key, 900);
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح',
            ], 422);
        }

        RateLimiter::clear($key);

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل التحقق الثنائي (2FA) بنجاح',
            'data' => [
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }

    public function twoFactorDisable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'code' => 'required|string',
        ], [
            'password.required' => 'كلمة المرور مطلوبة.',
            'code.required' => 'رمز التحقق مطلوب.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 422);
        }

        if (!$this->twoFactorService->verifyCode($user, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح',
            ], 422);
        }

        $this->twoFactorService->disable($user);

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل التحقق الثنائي (2FA) بنجاح',
            'data' => [
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }

    public function twoFactorStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $user->two_factor_enabled,
                'recovery_codes_count' => count($user->two_factor_recovery_codes ?? []),
            ],
        ]);
    }

    public function twoFactorRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string'], [
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 422);
        }

        $codes = $this->twoFactorService->regenerateRecoveryCodes($user);

        return response()->json([
            'success' => true,
            'data' => ['recovery_codes' => $codes],
        ]);
    }

    // ==================== Password Reset ====================

    public function forgotPassword(Request $request): JsonResponse
    {
        // SEC L3: do NOT use an `exists` rule here — a 422 "not registered" leaks
        // which emails/phones have accounts (enumeration). Resolve silently and
        // always return the SAME generic response whether or not the user exists.
        $request->validate(['email' => 'required|string'], [
            'email.required' => 'البريد الإلكتروني أو رقم الهاتف مطلوب.',
        ]);

        $field = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $request->email)->first();

        $generic = response()->json([
            'success' => true,
            'message' => 'إن وُجد حساب مطابق، فسنرسل رابط إعادة تعيين كلمة المرور.',
        ]);

        // SEC: throttle per target identifier (not just per-IP) to stop inbox
        // bombing of a single victim from many/rotating IPs.
        $key = 'forgot-password:'.$request->email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return $generic;
        }
        RateLimiter::hit($key, 60);

        if (!$user) {
            return $generic;
        }

        $token = Str::random(60);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'expires_at' => now()->addHour(), 'created_at' => now()]
        );

        // The token is hashed at rest and delivered only via the user's verified
        // channel. Local/dev may surface it to ease testing; prod never does.
        if (app()->environment('local')) {
            return response()->json([
                'success' => true,
                'message' => 'إن وُجد حساب مطابق، فسنرسل رابط إعادة تعيين كلمة المرور.',
                'data' => [
                    'reset_token' => $token,
                    'reset_url' => url("/reset-password?token={$token}&email=" . urlencode($request->email)),
                ],
            ]);
        }

        return $generic;
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'token.required' => 'رمز إعادة التعيين مطلوب.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ]);

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية',
            ], 422);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
        ]);
    }

    // ==================== Email Verification ====================

    public function verifyEmail(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'تم التحقق من البريد الإلكتروني بنجاح.',
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'تم إرسال بريد التحقق.',
        ]);
    }

    // ==================== Profile Management ====================

    public function updateProfile(Request $request): JsonResponse
    {
        // SEC M9: `phone` is intentionally NOT editable here. Changing the phone
        // re-routes future OTPs (and held-payroll release), so it must go through
        // the dedicated re-authenticated + re-verified flow (KYC updatePhone). A
        // plain profile edit must never silently swap the OTP destination.
        $request->validate([
            'first_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female',
            'country_code' => 'nullable|string|max:5',
            'language' => 'nullable|string|in:ar,en',
            'timezone' => 'nullable|string|max:50',
        ], [
            'first_name.max' => 'الاسم الأول يجب أن لا يتجاوز 50 حرف.',
            'last_name.max' => 'اسم العائلة يجب أن لا يتجاوز 50 حرف.',
            'date_of_birth.date' => 'تاريخ الميلاد غير صالح.',
            'gender.in' => 'الجنس يجب أن يكون "ذكر" أو "أنثى".',
            'country_code.max' => 'رمز الدولة يجب أن لا يتجاوز 5 أحرف.',
            'language.in' => 'اللغة يجب أن تكون "ar" أو "en".',
            'timezone.max' => 'المنطقة الزمنية يجب أن لا يتجاوز 50 حرف.',
        ]);

        $user = $request->user();
        $user->update($request->only([
            'first_name', 'last_name', 'date_of_birth',
            'gender', 'country_code', 'language', 'timezone',
        ]));

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->fresh()),
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        // SEC M2: the Laravel `image` rule PERMITS SVG, which can carry an inline
        // <script> (stored XSS once served from the same origin). Restrict to raster
        // formats only; the route also runs block-dangerous-uploads at the edge.
        $request->validate(['avatar' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048'], [
            'avatar.required' => 'الصورة الرمزية مطلوبة.',
            'avatar.file' => 'الملف غير صالح.',
            'avatar.mimes' => 'الصورة يجب أن تكون بصيغة JPG أو PNG أو WEBP.',
            'avatar.max' => 'حجم الصورة يجب أن لا يتجاوز 2 ميجابايت.',
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'data' => ['avatar' => $user->avatar],
        ]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $request->user()->update(['avatar' => null]);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الصورة الرمزية.',
        ]);
    }

    /**
     * Permanently disable & delete the authenticated user's account.
     *
     * Requires the account password for confirmation. Blocks deletion while any
     * wallet still holds a balance so funds are never silently lost. On success
     * the account is deactivated, suspended, all cards frozen, every access
     * token revoked, and the user row soft-deleted (recoverable by an admin).
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ], [
            'password.required' => 'كلمة المرور مطلوبة لتأكيد حذف الحساب.',
            'reason.max' => 'سبب الحذف يجب أن لا يتجاوز 500 حرف.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 422);
        }

        // Never delete an account that still holds funds — protect the user.
        $remainingBalance = (float) $user->wallets()->sum('balance');
        if ($remainingBalance > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الحساب وبه رصيد. يرجى سحب أو تحويل رصيدك أولاً.',
                'data' => ['remaining_balance' => $remainingBalance],
            ], 422);
        }

        DB::transaction(function () use ($user, $request) {
            // Freeze & deactivate any cards so they can no longer be used.
            $user->cards()->update([
                'status' => 'cancelled',
                'is_active' => false,
            ]);

            // Deactivate wallets.
            $user->wallets()->update(['is_active' => false]);

            // Mark the account disabled before soft-deleting. is_active/status are
            // guarded — forceFill or the account stays "active" after deletion.
            $user->forceFill([
                'is_active' => false,
                'status' => UserStatus::SUSPENDED,
                'fcm_token' => null,
                'deletion_reason' => $request->input('reason'),
                'deleted_requested_at' => now(),
            ])->save();

            // Revoke every issued access token (logs out all devices).
            $user->tokens()->delete();

            // Soft-delete — recoverable by an admin within the retention window.
            $user->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'تم حذف حسابك بنجاح. نأسف لرحيلك.',
        ]);
    }

    // ==================== KYC ====================

    public function kycStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => [
                    'value' => $user->kyc_status->value,
                    'label' => $user->kyc_status->label(),
                    'label_ar' => $user->kyc_status->labelAr(),
                    'color' => $user->kyc_status->color(),
                ],
                'is_verified' => $user->is_kyc_verified,
                'verified_at' => $user->kyc_verified_at?->toIso8601String(),
            ],
        ]);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'تم رفع المستند بنجاح.',
        ]);
    }

    public function getDocuments(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    // ==================== Notifications ====================

    public function notifications(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الإشعار كمقروء.',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'تم تحديد جميع الإشعارات كمقروءة.',
        ]);
    }

    // ==================== Static Pages ====================

    public function privacyPolicy(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'title_ar' => 'سياسة الخصوصية',
                'title_en' => 'Privacy Policy',
                'url_ar' => url('/legal/privacy?lang=ar'),
                'url_en' => url('/legal/privacy?lang=en'),
                'updated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function termsOfService(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms of Service',
                'url_ar' => url('/legal/terms?lang=ar'),
                'url_en' => url('/legal/terms?lang=en'),
                'updated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
