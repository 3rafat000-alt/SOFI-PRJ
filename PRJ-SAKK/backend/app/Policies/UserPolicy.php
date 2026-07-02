<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $admin): bool
    {
        return $admin->is_admin;
    }

    public function view(User $admin, User $user): bool
    {
        return $admin->is_admin;
    }

    public function update(User $admin, User $user): bool
    {
        return $admin->is_admin;
    }

    public function delete(User $admin, User $user): bool
    {
        return $admin->is_admin && ! $user->is_admin;
    }
}
