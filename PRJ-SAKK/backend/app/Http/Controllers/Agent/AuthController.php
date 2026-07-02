<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Traits\HasAccountLockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use HasAccountLockout;

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('agent.dashboard');
        }

        return view('portal.login', [
            'brand' => 'بوابة الوكلاء',
            'action' => route('agent.login.submit'),
            'intro' => 'أدر خدمات الإيداع والسحب النقدي عبر منصّة صكّ — ملفك، عمولاتك، ومستنداتك.',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        // Check account lockout before attempting auth
        $user = $this->getUserByCredentials($credentials);
        if ($user && ($remaining = $this->checkLockout($user)) > 0) {
            return $this->lockedOutResponse($request, $remaining);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'هذا الحساب غير نشط. تواصل مع الدعم الفني.']);
            }

            $this->resetLoginAttempts($user);
            $request->session()->regenerate();

            return redirect()->intended(route('agent.dashboard'));
        }

        if ($user) {
            $this->incrementLoginAttempts($user);
        }

        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agent.login');
    }
}
