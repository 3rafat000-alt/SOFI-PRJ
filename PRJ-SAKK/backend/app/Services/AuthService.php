<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'language' => $data['language'] ?? 'ar',
            'timezone' => $data['timezone'] ?? 'Asia/Riyadh',
            // 🔒 PIN removed from registration — was hardcoded to '123456'.
            // Users must now set their own PIN via the setPin endpoint
            // after authentication. A null pin_code means PIN-gated actions
            // (transfers, card operations) will require PIN setup first.
            'pin_code' => null,
        ]);

        // Link to referrer if a referral code was supplied.
        if (!empty($data['referral_code'])) {
            app(\App\Services\ReferralService::class)->attachReferrer($user, $data['referral_code']);
        }

        return $user->fresh();
    }
}
