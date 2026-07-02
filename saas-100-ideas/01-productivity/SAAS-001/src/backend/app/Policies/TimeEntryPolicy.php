<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TimeEntry $timeEntry): bool
    {
        return $user->id === $timeEntry->user_id
            || $user->workspaces()->whereHas('projects.tasks.timeEntries', fn ($q) => $q->where('id', $timeEntry->id))->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TimeEntry $timeEntry): bool
    {
        return $user->id === $timeEntry->user_id;
    }

    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        return $user->id === $timeEntry->user_id;
    }

    public function startTimer(User $user): bool
    {
        return true;
    }

    public function stopTimer(User $user): bool
    {
        return true;
    }
}
