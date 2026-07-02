<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $user->workspaces()->where('workspace_id', $workspace->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        $role = $this->getRole($user, $workspace);

        return in_array($role, ['owner', 'admin']);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        $role = $this->getRole($user, $workspace);

        return in_array($role, ['owner', 'admin']);
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        $role = $this->getRole($user, $workspace);

        return in_array($role, ['owner', 'admin']);
    }

    public function changeRole(User $user, Workspace $workspace, User $targetUser): bool
    {
        $role = $this->getRole($user, $workspace);

        if ($role === 'owner') {
            return true;
        }

        if ($role === 'admin' && $workspace->owner_id !== $targetUser->id) {
            return true;
        }

        return false;
    }

    private function getRole(User $user, Workspace $workspace): ?string
    {
        $member = $user->workspaces()->where('workspace_id', $workspace->id)->first();

        return $member?->pivot?->role;
    }
}
