<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Traits\HasAccountLockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use HasAccountLockout;

    /** TTL (seconds) for a pending 2FA grant before it must be re-issued via password login. */
    private const TWO_FACTOR_PENDING_TTL_SECONDS = 300;

    public function __construct(
        private readonly TwoFactorService $twoFactorService
    ) {
    }

    public function showLogin()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        // Check account lockout before attempting auth. We still ENFORCE the
        // lockout (block the attempt) but never reveal it via a distinct
        // message pre-auth — that would let a guesser confirm the email
        // exists just by comparing this string against the generic one
        // below. Same generic message either way; only the blocking behavior
        // differs.
        $user = $this->getUserByCredentials($credentials);
        if ($user && $this->checkLockout($user) > 0) {
            return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة.']);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check account is active
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'هذا الحساب غير نشط. تواصل مع الدعم الفني.']);
            }

            if (!$user->is_admin) {
                Auth::logout();
                return back()->withErrors(['email' => 'ليس لديك صلاحية الوصول للإدارة.']);
            }

            // 2FA gate: if enabled, do not finalize the session yet. Stash the
            // pending identity in session, drop the just-established auth state,
            // and hand off to the challenge screen. Admins without 2FA enabled
            // fall through unchanged below — nobody is locked out by this.
            if ($user->two_factor_enabled) {
                $pendingId = $user->id;
                Auth::logout();
                $request->session()->put('2fa.pending_id', $pendingId);
                $request->session()->put('2fa.remember', $remember);
                $request->session()->put('2fa.pending_at', now()->timestamp);
                // Regenerate the session id now so a pre-login (possibly
                // fixed/attacker-supplied) session id cannot ride along into
                // the pending-2FA state.
                $request->session()->regenerate();

                return redirect()->route('admin.login.2fa');
            }

            $this->resetLoginAttempts($user);
            $request->session()->regenerate();
            $this->logActivity('admin.login.success', $user, $request);

            return redirect()->intended(route('admin.dashboard'));
        }

        // Track failed attempt
        if ($user) {
            $this->incrementLoginAttempts($user);
            $this->logActivity('admin.login.failed', $user, $request);
        }

        // Same generic message whether the email exists or not — do not leak
        // account existence via a different string on this branch.
        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة.']);
    }

    /**
     * Show the 2FA challenge screen for a login pending a second factor.
     */
    public function showTwoFactor(Request $request)
    {
        if (!$request->session()->has('2fa.pending_id')) {
            return redirect()->route('admin.login');
        }

        if ($this->isPendingTwoFactorExpired($request)) {
            $this->forgetPendingTwoFactor($request);
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'انتهت صلاحية الجلسة. الرجاء تسجيل الدخول مرة أخرى.']);
        }

        return view('admin.auth.two-factor');
    }

    /**
     * Verify the submitted 2FA code and finalize the pending login.
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $pendingId = $request->session()->get('2fa.pending_id');

        if (!$pendingId) {
            return redirect()->route('admin.login');
        }

        if ($this->isPendingTwoFactorExpired($request)) {
            $this->forgetPendingTwoFactor($request);
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'انتهت صلاحية الجلسة. الرجاء تسجيل الدخول مرة أخرى.']);
        }

        $request->validate([
            'code' => 'required|string|max:20',
        ], [
            'code.required' => 'رمز التحقق مطلوب.',
        ]);

        $user = User::find($pendingId);

        if (!$user || !$user->is_admin || !$user->is_active) {
            $this->forgetPendingTwoFactor($request);
            return redirect()->route('admin.login')
                ->withErrors(['code' => 'بيانات الدخول غير صحيحة.']);
        }

        if (($remaining = $this->checkLockout($user)) > 0) {
            return $this->lockedOutResponse($request, $remaining);
        }

        if (!$this->twoFactorService->verifyCode($user, $request->string('code'))) {
            $this->incrementLoginAttempts($user);
            $this->logActivity('admin.2fa.failed', $user, $request);

            return back()->withErrors(['code' => 'رمز التحقق غير صحيح.']);
        }

        $remember = (bool) $request->session()->get('2fa.remember', false);
        $this->forgetPendingTwoFactor($request);

        Auth::loginUsingId($user->id, $remember);
        $request->session()->regenerate();
        $this->resetLoginAttempts($user);
        $this->logActivity('admin.2fa.success', $user, $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $this->logActivity('admin.logout', $user, $request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Whether the current pending-2FA grant has aged past its TTL.
     */
    private function isPendingTwoFactorExpired(Request $request): bool
    {
        $pendingAt = $request->session()->get('2fa.pending_at');

        if (!$pendingAt) {
            // No timestamp stashed (e.g. legacy session) — treat as expired.
            return true;
        }

        return now()->timestamp - (int) $pendingAt > self::TWO_FACTOR_PENDING_TTL_SECONDS;
    }

    /**
     * Clear all pending-2FA session state.
     */
    private function forgetPendingTwoFactor(Request $request): void
    {
        $request->session()->forget(['2fa.pending_id', '2fa.remember', '2fa.pending_at']);
    }

    /**
     * Record an admin-auth audit entry. Best-effort: logging never blocks login.
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
            // never let audit-logging failure break the auth flow
        }
    }
}
