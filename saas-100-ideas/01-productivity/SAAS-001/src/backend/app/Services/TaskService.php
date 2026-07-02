<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskMoved;
use App\Events\TaskUpdated;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * List tasks with advanced filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return CursorPaginator<Task>
     */
    public function list(array $filters): CursorPaginator
    {
        /** @var Builder<Task> $query */
        $query = Task::with([
            'assignees:id,name,avatar',
            'creator:id,name',
            'tags:id,name,color',
            'project:id,name',
        ])->ordered();

        if (! empty($filters['project_id'])) {
            $query->byProject($filters['project_id']);
        }

        if (! empty($filters['assignee_id'])) {
            $query->forUser($filters['assignee_id']);
        }

        if (! empty($filters['status'])) {
            $statuses = explode(',', $filters['status']);
            $query->whereIn('status', $statuses);
        }

        if (! empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['tags'])) {
            $tagIds = explode(',', $filters['tags']);
            $query->whereHas('tags', fn (Builder $q) => $q->whereIn('tags.id', $tagIds));
        }

        $limit = min((int) ($filters['limit'] ?? 15), 100);

        return $query->cursorPaginate($limit, ['*'], 'cursor');
    }

    /**
     * Create a new task.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Task
    {
        return DB::transaction(function () use ($data, $user): Task {
            $project = Project::findOrFail($data['project_id']);

            $maxPosition = Task::byProject($project->id)
                ->where('status', $data['status'] ?? 'todo')
                ->max('position');

            $task = Task::create([
                'project_id' => $project->id,
                'creator_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'todo',
                'position' => ($maxPosition ?? -1) + 1,
                'due_date' => $data['due_date'] ?? null,
                'estimated_minutes' => $data['estimated_minutes'] ?? null,
            ]);

            if (! empty($data['assignee_ids'])) {
                $task->assignees()->sync($data['assignee_ids']);
            }

            if (! empty($data['tag_ids'])) {
                $task->tags()->sync($data['tag_ids']);
            }

            $task->load(['assignees:id,name,avatar', 'creator:id,name', 'tags:id,name,color', 'project:id,name']);

            broadcast(new TaskCreated($task))->toOthers();

            return $task;
        });
    }

    /**
     * Update an existing task.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data): Task {
            $changed = [];

            foreach (['title', 'description', 'priority', 'status', 'due_date', 'estimated_minutes'] as $field) {
                if (array_key_exists($field, $data) && $task->{$field} !== $data[$field]) {
                    $changed[] = $field;
                }
            }

            $task->update($data);

            if (array_key_exists('assignee_ids', $data)) {
                $oldAssignees = $task->assignees()->pluck('users.id')->toArray();
                $task->assignees()->sync($data['assignee_ids']);
                if ($oldAssignees !== $data['assignee_ids']) {
                    $changed[] = 'assignees';
                }
            }

            if (array_key_exists('tag_ids', $data)) {
                $task->tags()->sync($data['tag_ids']);
                $changed[] = 'tags';
            }

            $task->load(['assignees:id,name,avatar', 'creator:id,name', 'tags:id,name,color', 'project:id,name']);

            broadcast(new TaskUpdated($task, $changed))->toOthers();

            return $task;
        });
    }

    /**
     * Delete (soft) a task.
     */
    public function delete(Task $task): void
    {
        $taskId = $task->id;
        $projectId = $task->project_id;

        $task->delete();

        broadcast(new TaskDeleted($taskId, $projectId))->toOthers();
    }

    /**
     * Reorder tasks (kanban drag-drop).
     *
     * @param  array<int, array{id: string, status: string, position: int}>  $orders
     */
    public function reorder(string $projectId, array $orders): int
    {
        return DB::transaction(function () use ($projectId, $orders): int {
            $count = 0;
            foreach ($orders as $order) {
                $task = Task::byProject($projectId)->find($order['id']);
                if (! $task) {
                    continue;
                }

                $oldStatus = $task->status;
                $oldPosition = $task->position;

                $task->update([
                    'status' => $order['status'],
                    'position' => $order['position'],
                ]);

                if ($oldStatus !== $order['status']) {
                    broadcast(new TaskMoved(
                        $task,
                        $oldStatus,
                        $order['status'],
                        $oldPosition,
                        $order['position'],
                    ))->toOthers();
                }

                $count++;
            }

            return $count;
        });
    }

    /**
     * Quick status change for a task.
     */
    public function changeStatus(Task $task, string $status): Task
    {
        $task->update(['status' => $status]);
        $task->load(['assignees:id,name,avatar', 'creator:id,name', 'tags:id,name,color', 'project:id,name']);

        broadcast(new TaskUpdated($task, ['status']))->toOthers();

        return $task;
    }
}
