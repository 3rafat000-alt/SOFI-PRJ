<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Admin self-service 2FA enrollment (web).
 *
 * Consumes the frozen TwoFactorService — never touches the guarded
 * two_factor_* columns directly.
 */
class TwoFactorProfileController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService
    ) {
    }

    /**
     * Show the current 2FA enrollment state for the signed-in admin.
     *
     * While a secret has been generated but not yet confirmed, the pending
     * secret + QR url are surfaced from session so the admin can scan/enter
     * it. Once confirmed, the secret is never exposed again.
     */
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $payload = [
            'enabled' => (bool) $user->two_factor_enabled,
            'pending' => null,
            'recovery_codes' => null,
        ];

        if (!$user->two_factor_enabled && $request->session()->has('2fa.setup.secret')) {
            $payload['pending'] = [
                'secret' => $request->session()->get('2fa.setup.secret'),
                'qr_code_url' => $request->session()->get('2fa.setup.qr_code_url'),
            ];
        }

        // One-time reveal of recovery codes right after confirm/regenerate.
        if ($request->session()->has('2fa.reveal.recovery_codes')) {
            $payload['recovery_codes'] = $request->session()->pull('2fa.reveal.recovery_codes');
        }

        return view('admin.profile.two-factor.index', $payload);
    }

    /**
     * Generate a new (unconfirmed) TOTP secret + recovery codes and stash
     * them in session pending confirm(). Requires the admin's current
     * password before a secret can be minted — standard re-auth-before-
     * sensitive-change practice.
     */
    public function enable(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return redirect()->route('admin.profile.2fa.show');
        }

        $validated = $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'كلمة المرور الحالية مطلوبة.',
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            $this->logActivity('admin.2fa.enroll.password_failed', $user, $request);

            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        $result = $this->twoFactorService->enable($user);

        $request->session()->put('2fa.setup.secret', $result['secret']);
        $request->session()->put('2fa.setup.qr_code_url', $result['qr_code_url']);
        // Recovery codes are only revealed to the admin once confirm() succeeds.
        $request->session()->put('2fa.setup.recovery_codes', $result['recovery_codes']);

        $this->logActivity('admin.2fa.enroll.started', $user, $request);

        return redirect()->route('admin.profile.2fa.show');
    }

    /**
     * Confirm the pending secret with a live TOTP code, activating 2FA.
     */
    public function confirm(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$request->session()->has('2fa.setup.secret')) {
            return redirect()->route('admin.profile.2fa.show');
        }

        $validated = $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'رمز التحقق مطلوب.',
            'code.digits' => 'رمز التحقق يجب أن يتكون من 6 أرقام.',
        ]);

        if (!$this->twoFactorService->confirm($user, $validated['code'])) {
            $this->logActivity('admin.2fa.confirm.failed', $user, $request);

            return back()->withErrors(['code' => 'رمز التحقق غير صحيح.']);
        }

        $recoveryCodes = $request->session()->pull('2fa.setup.recovery_codes', []);
        $request->session()->forget(['2fa.setup.secret', '2fa.setup.qr_code_url']);
        $request->session()->flash('2fa.reveal.recovery_codes', $recoveryCodes);

        $this->logActivity('admin.2fa.enrolled', $user, $request);

        return redirect()->route('admin.profile.2fa.show')
            ->with('success', 'تم تفعيل التحقق بخطوتين بنجاح.');
    }

    /**
     * Disable 2FA. Requires BOTH the current password and a valid
     * TOTP/recovery code as re-confirmation before disabling — a single
     * factor (just the code, or just the password) is not enough to turn
     * off the account's second factor.
     */
    public function disable(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|max:20',
        ], [
            'password.required' => 'كلمة المرور الحالية مطلوبة.',
            'code.required' => 'رمز التحقق مطلوب لإيقاف التحقق بخطوتين.',
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            $this->logActivity('admin.2fa.disable.password_failed', $user, $request);

            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        if (!$this->twoFactorService->verifyCode($user, $validated['code'])) {
            $this->logActivity('admin.2fa.disable.failed', $user, $request);

            return back()->withErrors(['code' => 'رمز التحقق غير صحيح.']);
        }

        $this->twoFactorService->disable($user);
        $request->session()->forget([
            '2fa.setup.secret',
            '2fa.setup.qr_code_url',
            '2fa.setup.recovery_codes',
        ]);

        $this->logActivity('admin.2fa.disabled', $user, $request);

        return redirect()->route('admin.profile.2fa.show')
            ->with('success', 'تم إيقاف التحقق بخطوتين.');
    }

    /**
     * Regenerate recovery codes for an already-enabled 2FA setup. Requires
     * the current password as well as a valid code — recovery codes are
     * the last-resort account-recovery path, so re-auth is mandatory here too.
     */
    public function recovery(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return redirect()->route('admin.profile.2fa.show');
        }

        $validated = $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|max:20',
        ], [
            'password.required' => 'كلمة المرور الحالية مطلوبة.',
            'code.required' => 'رمز التحقق مطلوب.',
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            $this->logActivity('admin.2fa.recovery_regenerated.password_failed', $user, $request);

            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        if (!$this->twoFactorService->verifyCode($user, $validated['code'])) {
            $this->logActivity('admin.2fa.recovery_regenerated.failed', $user, $request);

            return back()->withErrors(['code' => 'رمز التحقق غير صحيح.']);
        }

        $codes = $this->twoFactorService->regenerateRecoveryCodes($user);
        $request->session()->flash('2fa.reveal.recovery_codes', $codes);

        $this->logActivity('admin.2fa.recovery_regenerated', $user, $request);

        return redirect()->route('admin.profile.2fa.show')
            ->with('success', 'تم توليد رموز استرداد جديدة.');
    }

    /**
     * Record a 2FA-enrollment audit entry. Best-effort: logging never
     * blocks the enrollment flow.
     */
    private function logActivity(string $action, ?User $user, Request $request): void
    {
        try {
            ActivityLog::create([
                'user_id' => $user?->id,
                'admin_id' => $user?->id,
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (\Throwable) {
            // never let audit-logging failure break the enrollment flow
        }
    }
}
