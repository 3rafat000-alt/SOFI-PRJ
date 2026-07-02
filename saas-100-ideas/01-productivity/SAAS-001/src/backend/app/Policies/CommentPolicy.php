<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user, string $taskId): bool
    {
        $task = \App\Models\Task::find($taskId);

        if (! $task) {
            return false;
        }

        return $user->workspaces()->where('workspace_id', $task->project->workspace_id)->exists();
    }

    public function create(User $user, string $taskId): bool
    {
        $task = \App\Models\Task::find($taskId);

        if (! $task) {
            return false;
        }

        $role = $this->getRoleInWorkspace($user, $task->project->workspace_id);

        return in_array($role, ['owner', 'admin', 'member']);
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->id === $comment->user_id) {
            return true;
        }

        $role = $this->getRoleInWorkspace($user, $comment->task->project->workspace_id);

        return in_array($role, ['owner', 'admin']);
    }

    private function getRoleInWorkspace(User $user, string $workspaceId): ?string
    {
        $member = $user->workspaces()->where('workspace_id', $workspaceId)->first();

        return $member?->pivot?->role;
    }
}
