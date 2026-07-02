<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TimerStarted;
use App\Events\TimerStopped;
use App\Exceptions\TimerAlreadyRunningException;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TimeEntryService
{
    /**
     * List time entries with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<TimeEntry>
     */
    public function list(array $filters): LengthAwarePaginator
    {
        /** @var Builder<TimeEntry> $query */
        $query = TimeEntry::with(['task:id,title,project_id', 'task.project:id,name', 'user:id,name'])
            ->latestFirst();

        if (! empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (! empty($filters['task_id'])) {
            $query->byTask($filters['task_id']);
        }

        if (! empty($filters['from']) && ! empty($filters['to'])) {
            $query->byDateRange($filters['from'], $filters['to']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    /**
     * Start a timer for a user on a task.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws TimerAlreadyRunningException
     */
    public function startTimer(array $data, User $user): TimeEntry
    {
        $running = TimeEntry::byUser($user->id)->running()->first();

        if ($running) {
            throw new TimerAlreadyRunningException('You already have a running timer on task: '.$running->task_id);
        }

        return DB::transaction(function () use ($data, $user): TimeEntry {
            $entry = TimeEntry::create([
                'task_id' => $data['task_id'],
                'user_id' => $user->id,
                'started_at' => now(),
                'notes' => $data['note'] ?? $data['notes'] ?? null,
            ]);

            $entry->load(['task:id,title', 'user:id,name']);

            broadcast(new TimerStarted($entry))->toOthers();

            return $entry;
        });
    }

    /**
     * Stop the currently running timer for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function stopTimer(array $data, User $user): TimeEntry
    {
        /** @var TimeEntry|null $entry */
        $entry = TimeEntry::byUser($user->id)->running()->first();

        if (! $entry) {
            throw new TimerAlreadyRunningException('No running timer found.');
        }

        return DB::transaction(function () use ($entry, $data): TimeEntry {
            $endedAt = now();
            $startedAt = Carbon::parse($entry->started_at);
            $duration = (int) $startedAt->diffInMinutes($endedAt);

            $entry->update([
                'ended_at' => $endedAt,
                'duration_minutes' => $duration,
                'notes' => $data['note'] ?? $data['notes'] ?? $entry->notes,
            ]);

            $entry->load(['task:id,title', 'user:id,name']);

            broadcast(new TimerStopped($entry))->toOthers();

            return $entry;
        });
    }

    /**
     * Create a manual time entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function createManual(array $data, User $user): TimeEntry
    {
        $startedAt = Carbon::parse($data['started_at']);
        $endedAt = isset($data['ended_at']) ? Carbon::parse($data['ended_at']) : null;
        $duration = $endedAt ? (int) $startedAt->diffInMinutes($endedAt) : null;

        return TimeEntry::create([
            'task_id' => $data['task_id'],
            'user_id' => $user->id,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $duration,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update a time entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TimeEntry $entry, array $data): TimeEntry
    {
        if (isset($data['started_at'])) {
            $data['started_at'] = Carbon::parse($data['started_at']);
        }

        if (isset($data['ended_at'])) {
            $data['ended_at'] = Carbon::parse($data['ended_at']);
        }

        if (isset($data['started_at']) && isset($data['ended_at'])) {
            $data['duration_minutes'] = (int) Carbon::parse($data['started_at'])->diffInMinutes(Carbon::parse($data['ended_at']));
        } elseif (isset($data['ended_at']) && ! isset($data['started_at'])) {
            $data['duration_minutes'] = (int) Carbon::parse($entry->started_at)->diffInMinutes(Carbon::parse($data['ended_at']));
        }

        $entry->update($data);

        return $entry->load(['task:id,title', 'user:id,name']);
    }

    /**
     * Generate time report.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        /** @var Builder<TimeEntry> $query */
        $query = TimeEntry::with(['task:id,title,project_id', 'task.project:id,name', 'user:id,name'])
            ->whereNotNull('ended_at');

        if (! empty($filters['workspace_id'])) {
            $query->whereHas('task.project', fn (Builder $q) => $q->where('workspace_id', $filters['workspace_id']));
        }

        if (! empty($filters['project_id'])) {
            $query->whereHas('task', fn (Builder $q) => $q->where('project_id', $filters['project_id']));
        }

        if (! empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $query->where('started_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('started_at', '<=', $filters['to']);
        }

        $entries = $query->get();

        $totalMinutes = (int) $entries->sum('duration_minutes');
        $groupBy = $filters['group_by'] ?? 'day';

        $grouped = $entries->groupBy(function (TimeEntry $entry) use ($groupBy) {
            $date = Carbon::parse($entry->started_at);

            return match ($groupBy) {
                'week' => $date->startOfWeek()->format('Y-m-d'),
                'month' => $date->format('Y-m'),
                'user' => $entry->user_id,
                'project' => $entry->task->project_id ?? 'unknown',
                default => $date->format('Y-m-d'),
            };
        });

        $mapped = $grouped->map(function ($group, $key) use ($groupBy, $entries): array {
            $minutes = (int) $group->sum('duration_minutes');
            $base = ['minutes' => $minutes];

            if ($groupBy === 'user') {
                $first = $group->first();
                $base['user_id'] = $key;
                $base['user_name'] = $first?->user?->name ?? 'Unknown';
            } elseif ($groupBy === 'project') {
                $first = $group->first();
                $base['project_id'] = $key;
                $base['project_name'] = $first?->task?->project?->name ?? 'Unknown';
            } else {
                $base['date'] = $key;
            }

            return $base;
        })->values();

        return [
            'summary' => [
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 1),
                'avg_daily_minutes' => $mapped->isNotEmpty() ? (int) round($totalMinutes / $mapped->count()) : 0,
                'period' => [
                    'from' => $filters['from'] ?? null,
                    'to' => $filters['to'] ?? null,
                ],
            ],
            'entries' => $mapped->toArray(),
        ];
    }
}
