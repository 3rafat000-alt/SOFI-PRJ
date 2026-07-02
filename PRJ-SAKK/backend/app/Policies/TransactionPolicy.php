<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transaction;

class TransactionPolicy
{
    public function viewAny(User $admin): bool
    {
        return $admin->is_admin;
    }

    public function view(User $admin, Transaction $transaction): bool
    {
        return $admin->is_admin;
    }

    public function reverse(User $admin, Transaction $transaction): bool
    {
        return $admin->is_admin;
    }
}
