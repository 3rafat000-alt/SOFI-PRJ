<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    /**
     * Upload an attachment to a task.
     */
    public function upload(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpg,jpeg,png,gif,svg,pdf,doc,docx,xls,xlsx,zip',
            ],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        $path = $file->store('attachments', 'public');

        if (! $path) {
            return response()->json([
                'error' => [
                    'code' => 'UPLOAD_FAILED',
                    'message' => 'Failed to upload file.',
                ],
            ], 500);
        }

        $attachment = Attachment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'filename' => $request->name ?? $originalName,
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
        ]);

        return response()->json([
            'data' => [
                'id' => $attachment->id,
                'task_id' => $attachment->task_id,
                'user_id' => $attachment->user_id,
                'name' => $attachment->filename,
                'size' => $attachment->size,
                'mime_type' => $attachment->mime_type,
                'url' => url('storage/'.$attachment->path),
                'thumbnail_url' => str_starts_with($mimeType, 'image/')
                    ? url('storage/'.$attachment->path)
                    : null,
                'created_at' => $attachment->created_at?->toIso8601String(),
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Download an attachment.
     */
    public function download(Request $request, Attachment $attachment): JsonResponse
    {
        Gate::authorize('view', $attachment->task);

        if (! Storage::disk('public')->exists($attachment->path)) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'File not found.',
                ],
            ], 404);
        }

        $url = Storage::disk('public')->temporaryUrl(
            $attachment->path,
            now()->addMinutes(15)
        );

        return response()->json([
            'data' => [
                'url' => $url,
                'filename' => $attachment->filename,
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Request $request, Attachment $attachment): JsonResponse
    {
        $user = $request->user();
        $isUploader = $user->id === $attachment->user_id;
        $isAdmin = Gate::allows('update', $attachment->task);

        if (! $isUploader && ! $isAdmin) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to delete this attachment.',
                ],
            ], 403);
        }

        if (Storage::disk('public')->exists($attachment->path)) {
            Storage::disk('public')->delete($attachment->path);
        }

        $attachment->delete();

        return response()->json([
            'data' => [
                'message' => 'Attachment deleted.',
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
