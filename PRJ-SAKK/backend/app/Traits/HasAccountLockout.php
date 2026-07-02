<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HasAccountLockout
{
    protected int $maxAttempts = 5;
    protected int $lockoutMinutes = 15;

    /**
     * Check if the user is currently locked out.
     * Returns remaining lockout minutes, or 0 if not locked.
     */
    protected function checkLockout(\App\Models\User $user): int
    {
        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            return (int) now()->diffInMinutes($user->locked_until) + 1;
        }

        // Reset if lockout expired
        if ($user->locked_until && now()->greaterThanOrEqualTo($user->locked_until)) {
            $user->forceFill([
                'login_attempts' => 0,
                'locked_until' => null,
                'last_failed_login_at' => null,
            ])->save();
        }

        return 0;
    }

    /**
     * Increment failed login attempts. Lock account if max reached.
     */
    protected function incrementLoginAttempts(\App\Models\User $user): void
    {
        $attempts = $user->login_attempts + 1;

        if ($attempts >= $this->maxAttempts) {
            $user->forceFill([
                'login_attempts' => $attempts,
                'locked_until' => now()->addMinutes($this->lockoutMinutes),
                'last_failed_login_at' => now(),
            ])->save();
        } else {
            $user->forceFill([
                'login_attempts' => $attempts,
                'last_failed_login_at' => now(),
            ])->save();
        }
    }

    /**
     * Reset login attempts on successful login.
     */
    protected function resetLoginAttempts(\App\Models\User $user): void
    {
        if ($user->login_attempts > 0 || $user->locked_until !== null) {
            $user->forceFill([
                'login_attempts' => 0,
                'locked_until' => null,
                'last_failed_login_at' => null,
            ])->save();
        }
    }

    /**
     * Get the user by email (used by auth controllers).
     */
    protected function getUserByCredentials(array $credentials): ?\App\Models\User
    {
        return \App\Models\User::where('email', $credentials['email'])->first();
    }

    /**
     * Build a locked-out error response (back redirect with error).
     */
    protected function lockedOutResponse(\Illuminate\Http\Request $request, int $minutes): \Illuminate\Http\RedirectResponse
    {
        return back()->withErrors([
            'email' => "الحساب مقفل مؤقتاً. حاول مرة أخرى بعد {$minutes} دقيقة.",
        ])->onlyInput('email');
    }
}
