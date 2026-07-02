<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InviteMemberRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function __construct(
        private InvitationService $invitationService,
    ) {}

    /**
     * List all workspaces for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $workspaces = $request->user()->workspaces()
            ->withCount(['members', 'projects'])
            ->get();

        return response()->json([
            'data' => WorkspaceResource::collection($workspaces),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new workspace.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['nullable', 'string', 'max:100'],
        ]);

        $slug = Str::slug($validated['name']).'-'.Str::random(6);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'owner_id' => $request->user()->id,
            'max_members' => config('tasksync.workspace.max_members_free', 3),
            'plan' => config('tasksync.workspace.default_plan', 'free'),
        ]);

        $workspace->members()->attach($request->user()->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $workspace->loadCount(['members', 'projects']);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Show a specific workspace.
     */
    public function show(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('view', $workspace);

        $workspace->loadCount(['members', 'projects']);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update a workspace.
     */
    public function update(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('update', $workspace);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $workspace->name) {
            $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(6);
        }

        $workspace->update($validated);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete (soft) a workspace.
     */
    public function destroy(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('delete', $workspace);

        $workspace->delete();

        return response()->json([
            'data' => [
                'message' => 'Workspace deleted successfully.',
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * List members of a workspace.
     */
    public function members(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('view', $workspace);

        $members = $workspace->members()
            ->withCount(['assignedTasks as task_count' => function ($q) use ($workspace): void {
                $q->whereHas('project', fn ($p) => $p->where('workspace_id', $workspace->id));
            }])
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'avatar_url' => $member->avatar ? url('storage/'.$member->avatar) : null,
                'role' => $member->pivot->role,
                'joined_at' => $member->pivot->joined_at->toIso8601String(),
                'task_count' => (int) ($member->task_count ?? 0),
            ]);

        return response()->json([
            'data' => $members,
            'meta' => [
                'total' => $members->count(),
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Invite a member to the workspace.
     */
    public function invite(InviteMemberRequest $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('invite', $workspace);

        $result = $this->invitationService->invite($workspace, $request->validated());

        return response()->json([
            'data' => $result,
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], $result['invitation'] ? 201 : 200);
    }
}
