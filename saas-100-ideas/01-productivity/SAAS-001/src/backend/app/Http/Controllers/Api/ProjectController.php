<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProjectRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * List projects for a workspace.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'string', 'uuid', 'exists:workspaces,id'],
            'status' => ['nullable', 'string', 'in:active,archived,all'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        Gate::authorize('viewAny', [Project::class, $request->workspace_id]);

        $query = Project::byWorkspace($request->workspace_id)
            ->withCount('tasks');

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        $perPage = min((int) ($request->per_page ?? 15), 100);
        $projects = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new project.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', [Project::class, $request->workspace_id]);

        $project = Project::create([
            'workspace_id' => $request->workspace_id,
            'creator_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? '#6366F1',
            'status' => 'active',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        $project->loadCount('tasks');

        return response()->json([
            'data' => new ProjectResource($project),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Show a specific project.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $project->loadCount('tasks');

        return response()->json([
            'data' => new ProjectResource($project),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update a project.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        return response()->json([
            'data' => new ProjectResource($project),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete (soft) a project.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return response()->json([
            'data' => [
                'message' => 'Project deleted successfully.',
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * List tasks in a project.
     */
    public function tasks(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $request->validate([
            'status' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'string', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $project->tasks()
            ->with(['assignees:id,name,avatar', 'creator:id,name', 'tags:id,name,color'])
            ->ordered();

        if ($request->status) {
            $query->byStatus($request->status);
        }

        if ($request->assignee_id) {
            $query->forUser($request->assignee_id);
        }

        $perPage = min((int) ($request->per_page ?? 50), 100);
        $tasks = $query->paginate($perPage);

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
