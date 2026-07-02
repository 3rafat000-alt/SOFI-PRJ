<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PinService
{
    /**
     * Verify PIN against user's stored PIN code
     */
    public function verify(User $user, string $pin): bool
    {
        if (!$user->pin_code) {
            return false;
        }

        return Hash::check($pin, $user->pin_code);
    }

    /**
     * Hash PIN for storage
     */
    public function hash(string $pin): string
    {
        return Hash::make($pin);
    }
}
