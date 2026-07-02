<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTimeEntryRequest;
use App\Http\Requests\Api\UpdateTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use App\Services\TimeEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TimeEntryController extends Controller
{
    public function __construct(
        private TimeEntryService $timeEntryService,
    ) {}

    /**
     * List time entries with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['nullable', 'string', 'uuid'],
            'task_id' => ['nullable', 'string', 'uuid'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $entries = $this->timeEntryService->list($request->all());

        return response()->json([
            'data' => TimeEntryResource::collection($entries),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total' => $entries->total(),
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Start a timer for the authenticated user.
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'task_id' => ['required', 'string', 'uuid', 'exists:tasks,id'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        Gate::authorize('startTimer', TimeEntry::class);

        $entry = $this->timeEntryService->startTimer($request->all(), $request->user());

        return response()->json([
            'data' => new TimeEntryResource($entry),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Stop the currently running timer.
     */
    public function stop(Request $request): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        Gate::authorize('stopTimer', TimeEntry::class);

        $entry = $this->timeEntryService->stopTimer($request->all(), $request->user());

        return response()->json([
            'data' => new TimeEntryResource($entry),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a manual time entry.
     */
    public function store(StoreTimeEntryRequest $request): JsonResponse
    {
        Gate::authorize('create', TimeEntry::class);

        $entry = $this->timeEntryService->createManual($request->validated(), $request->user());

        $entry->load(['task:id,title', 'user:id,name']);

        return response()->json([
            'data' => new TimeEntryResource($entry),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Update a time entry.
     */
    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry): JsonResponse
    {
        Gate::authorize('update', $timeEntry);

        $entry = $this->timeEntryService->update($timeEntry, $request->validated());

        return response()->json([
            'data' => new TimeEntryResource($entry),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete a time entry.
     */
    public function destroy(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        Gate::authorize('delete', $timeEntry);

        $timeEntry->delete();

        return response()->json([
            'data' => [
                'message' => 'Time entry deleted.',
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Generate time report.
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'string', 'uuid', 'exists:workspaces,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'group_by' => ['nullable', 'string', 'in:day,week,month,user,project'],
            'user_id' => ['nullable', 'string', 'uuid'],
            'project_id' => ['nullable', 'string', 'uuid'],
        ]);

        $data = $this->timeEntryService->report($request->all());

        return response()->json([
            'data' => $data,
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
