<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * List tags for a workspace.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'string', 'uuid', 'exists:workspaces,id'],
        ]);

        $tags = Tag::byWorkspace($request->workspace_id)
            ->withCount('tasks')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $tags->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'workspace_id' => $tag->workspace_id,
                'name' => $tag->name,
                'color' => $tag->color,
                'task_count' => (int) $tag->tasks_count,
            ]),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a tag.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string', 'uuid', 'exists:workspaces,id'],
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ]);

        $tag = Tag::create([
            'workspace_id' => $validated['workspace_id'],
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366F1',
        ]);

        return response()->json([
            'data' => [
                'id' => $tag->id,
                'workspace_id' => $tag->workspace_id,
                'name' => $tag->name,
                'color' => $tag->color,
                'task_count' => 0,
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Delete a tag.
     */
    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        Gate::authorize('update', $tag);

        $tag->tasks()->detach();
        $tag->delete();

        return response()->json([
            'data' => [
                'message' => 'Tag deleted.',
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
