<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    /**
     * Get aggregate stats for a workspace.
     *
     * @return array<string, mixed>
     */
    public function stats(Workspace $workspace): array
    {
        $projectIds = $workspace->projects()->pluck('id');
        $memberIds = $workspace->members()->pluck('users.id');

        $tasks = Task::whereIn('project_id', $projectIds);

        $totalTasks = (clone $tasks)->count();
        $todoTasks = (clone $tasks)->where('status', 'todo')->count();
        $inProgressTasks = (clone $tasks)->where('status', 'in_progress')->count();
        $doneTasks = (clone $tasks)->where('status', 'done')->count();
        $overdueTasks = (clone $tasks)->overdue()->count();
        $upcomingTasks = (clone $tasks)->upcoming(7)->count();

        $activeProjects = $workspace->projects()->active()->count();
        $archivedProjects = $workspace->projects()->archived()->count();

        $todayStart = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $todayMinutes = (int) TimeEntry::whereIn('user_id', $memberIds)
            ->where('started_at', '>=', $todayStart)
            ->sum('duration_minutes');

        $weekMinutes = (int) TimeEntry::whereIn('user_id', $memberIds)
            ->where('started_at', '>=', $weekStart)
            ->sum('duration_minutes');

        $monthMinutes = (int) TimeEntry::whereIn('user_id', $memberIds)
            ->where('started_at', '>=', $monthStart)
            ->sum('duration_minutes');

        $totalMembers = $memberIds->count();
        $activeToday = $this->activeTodayCount($workspace);

        return [
            'tasks' => [
                'total' => $totalTasks,
                'todo' => $todoTasks,
                'in_progress' => $inProgressTasks,
                'done' => $doneTasks,
                'overdue' => $overdueTasks,
                'upcoming_week' => $upcomingTasks,
            ],
            'projects' => [
                'active' => $activeProjects,
                'archived' => $archivedProjects,
            ],
            'time' => [
                'today_minutes' => $todayMinutes,
                'week_minutes' => $weekMinutes,
                'month_minutes' => $monthMinutes,
            ],
            'members' => [
                'total' => $totalMembers,
                'active_today' => $activeToday,
            ],
        ];
    }

    /**
     * Get recent activity for a workspace.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activity(Workspace $workspace, int $limit = 20): array
    {
        $projectIds = $workspace->projects()->pluck('id');

        /** @var Builder<ActivityLog> $query */
        $query = ActivityLog::with('user:id,name')
            ->where(function (Builder $q) use ($projectIds): void {
                $q->whereIn('subject_id', $projectIds)
                  ->where('subject_type', Project::class);
            })
            ->orWhere(function (Builder $q) use ($projectIds): void {
                $q->whereIn('subject_id', function ($sub) use ($projectIds): void {
                    $sub->select('id')
                        ->from('tasks')
                        ->whereIn('project_id', $projectIds);
                })->where('subject_type', Task::class);
            })
            ->latestFirst()
            ->limit($limit);

        return $query->get()->map(fn (ActivityLog $log): array => [
            'id' => $log->id,
            'type' => $log->event,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
            ] : null,
            'description' => $log->description,
            'properties' => $log->properties,
            'created_at' => $log->created_at->toIso8601String(),
        ])->toArray();
    }

    /**
     * Count members who were active today (have time entries).
     */
    private function activeTodayCount(Workspace $workspace): int
    {
        $memberIds = $workspace->members()->pluck('users.id');

        return TimeEntry::whereIn('user_id', $memberIds)
            ->where('started_at', '>=', Carbon::today())
            ->distinct('user_id')
            ->count('user_id');
    }
}
