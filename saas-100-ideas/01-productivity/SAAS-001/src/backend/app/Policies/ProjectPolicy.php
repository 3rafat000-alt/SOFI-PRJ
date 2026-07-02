<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user, string $workspaceId): bool
    {
        return $user->workspaces()->where('workspace_id', $workspaceId)->exists();
    }

    public function view(User $user, Project $project): bool
    {
        return $user->workspaces()->where('workspace_id', $project->workspace_id)->exists();
    }

    public function create(User $user, string $workspaceId): bool
    {
        return $user->workspaces()->where('workspace_id', $workspaceId)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        $role = $this->getRole($user, $project);

        return in_array($role, ['owner', 'admin']);
    }

    public function delete(User $user, Project $project): bool
    {
        $role = $this->getRole($user, $project);

        return in_array($role, ['owner', 'admin']);
    }

    private function getRole(User $user, Project $project): ?string
    {
        $member = $user->workspaces()->where('workspace_id', $project->workspace_id)->first();

        return $member?->pivot?->role;
    }
}
