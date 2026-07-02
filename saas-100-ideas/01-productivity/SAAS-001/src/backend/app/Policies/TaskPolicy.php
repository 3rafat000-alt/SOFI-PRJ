<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, string $projectId): bool
    {
        $project = \App\Models\Project::find($projectId);

        return $project && $user->workspaces()->where('workspace_id', $project->workspace_id)->exists();
    }

    public function view(User $user, Task $task): bool
    {
        return $user->workspaces()->where('workspace_id', $task->project->workspace_id)->exists();
    }

    public function create(User $user, string $projectId): bool
    {
        $project = \App\Models\Project::find($projectId);

        if (! $project) {
            return false;
        }

        $role = $this->getRoleInWorkspace($user, $project->workspace_id);

        return in_array($role, ['owner', 'admin', 'member']);
    }

    public function update(User $user, Task $task): bool
    {
        $role = $this->getRoleInWorkspace($user, $task->project->workspace_id);

        return in_array($role, ['owner', 'admin', 'member']);
    }

    public function delete(User $user, Task $task): bool
    {
        $role = $this->getRoleInWorkspace($user, $task->project->workspace_id);

        return in_array($role, ['owner', 'admin']);
    }

    public function reorder(User $user, string $projectId): bool
    {
        $project = \App\Models\Project::find($projectId);

        if (! $project) {
            return false;
        }

        $role = $this->getRoleInWorkspace($user, $project->workspace_id);

        return in_array($role, ['owner', 'admin', 'member']);
    }

    private function getRoleInWorkspace(User $user, string $workspaceId): ?string
    {
        $member = $user->workspaces()->where('workspace_id', $workspaceId)->first();

        return $member?->pivot?->role;
    }
}
