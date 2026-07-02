<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService,
    ) {}

    /**
     * List tasks with advanced filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => ['nullable', 'string', 'uuid'],
            'assignee_id' => ['nullable', 'string', 'uuid'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'search' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ]);

        $tasks = $this->taskService->list($request->all());

        return response()->json([
            'data' => TaskResource::collection($tasks->items()),
            'meta' => [
                'next_cursor' => $tasks->nextCursor()?->encode(),
                'has_more' => $tasks->hasMorePages(),
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new task.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        Gate::authorize('create', [Task::class, $request->project_id]);

        $task = $this->taskService->create($request->validated(), $request->user());

        return response()->json([
            'data' => new TaskResource($task),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Show a specific task.
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        $task->load([
            'assignees:id,name,avatar',
            'creator:id,name',
            'tags:id,name,color',
            'project:id,name',
            'comments.user:id,name,avatar',
            'attachments',
            'timeEntries' => fn ($q) => $q->latestFirst()->limit(50),
        ]);

        return response()->json([
            'data' => new TaskResource($task),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update a task.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $task = $this->taskService->update($task, $request->validated());

        return response()->json([
            'data' => new TaskResource($task),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete (soft) a task.
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $this->taskService->delete($task);

        return response()->json([
            'data' => [
                'message' => 'Task deleted.',
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Reorder tasks (kanban drag-drop).
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => ['required', 'string', 'uuid', 'exists:projects,id'],
            'orders' => ['required', 'array', 'min:1'],
            'orders.*.id' => ['required', 'string', 'uuid', 'exists:tasks,id'],
            'orders.*.status' => ['required', 'string', 'in:todo,in_progress,done,cancelled'],
            'orders.*.position' => ['required', 'integer', 'min:0'],
        ]);

        Gate::authorize('reorder', [Task::class, $request->project_id]);

        $count = $this->taskService->reorder($request->project_id, $request->orders);

        return response()->json([
            'data' => [
                'message' => 'Tasks reordered successfully.',
                'reordered_count' => $count,
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Quick status change for a task.
     */
    public function status(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:todo,in_progress,done,cancelled'],
        ]);

        Gate::authorize('update', $task);

        $task = $this->taskService->changeStatus($task, $request->status);

        return response()->json([
            'data' => new TaskResource($task),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
