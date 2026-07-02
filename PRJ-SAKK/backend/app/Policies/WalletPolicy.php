<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function viewAny(User $admin): bool
    {
        return $admin->is_admin;
    }

    public function view(User $admin, Wallet $wallet): bool
    {
        return $admin->is_admin;
    }

    public function freeze(User $admin, Wallet $wallet): bool
    {
        return $admin->is_admin;
    }

    public function unfreeze(User $admin, Wallet $wallet): bool
    {
        return $admin->is_admin;
    }
}
