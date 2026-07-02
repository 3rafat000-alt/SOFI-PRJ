<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\CommentAdded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    /**
     * List comments for a task.
     */
    public function index(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('viewAny', [Comment::class, $task->id]);

        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = min((int) ($request->per_page ?? 50), 100);

        $comments = $task->comments()
            ->with('user:id,name,avatar')
            ->latestFirst()
            ->paginate($perPage);

        $userId = $request->user()->id;

        $data = $comments->map(fn (Comment $comment) => [
            'id' => $comment->id,
            'task_id' => $comment->task_id,
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'avatar_url' => $comment->user->avatar ? url('storage/'.$comment->user->avatar) : null,
            ],
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toIso8601String(),
            'updated_at' => $comment->updated_at?->toIso8601String(),
            'can_delete' => $userId === $comment->user_id,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a comment on a task.
     */
    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('create', [Comment::class, $task->id]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        $comment->load('user:id,name,avatar');

        broadcast(new CommentAdded($comment))->toOthers();

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'task_id' => $comment->task_id,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'avatar_url' => $comment->user->avatar ? url('storage/'.$comment->user->avatar) : null,
                ],
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toIso8601String(),
                'updated_at' => null,
                'can_delete' => true,
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Delete a comment.
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'data' => [
                'message' => 'Comment deleted.',
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
