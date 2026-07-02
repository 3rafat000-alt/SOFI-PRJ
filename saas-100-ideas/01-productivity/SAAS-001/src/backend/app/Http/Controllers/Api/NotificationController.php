<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * List notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', 'string'],
            'read' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $notifications = $this->notificationService->list($request->user(), $request->all());

        $unreadCount = $this->notificationService->unreadCount($request->user());

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'total' => $notifications->total(),
                'unread_count' => $unreadCount,
                'per_page' => $notifications->perPage(),
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to access this notification.',
                ],
            ], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'data' => new NotificationResource($notification),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'data' => [
                'message' => 'All notifications marked as read.',
                'count' => $count,
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
