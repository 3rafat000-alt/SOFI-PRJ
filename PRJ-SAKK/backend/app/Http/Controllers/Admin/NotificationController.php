<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * List notifications (AJAX for dropdown).
     */
    public function index(Request $request): JsonResponse
    {
        $alerts = AdminAlert::forAdmin()
            ->latest()
            ->limit(20)
            ->get();

        $unreadCount = AdminAlert::forAdmin()
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $alerts->map(fn ($a) => [
                    'id'        => $a->id,
                    'title'     => $a->title,
                    'message'   => $a->message,
                    'type'      => $a->type,
                    'link'      => $a->link,
                    'read_at'   => $a->read_at,
                    'created_at'=> $a->created_at->diffForHumans(),
                ]),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(AdminAlert $alert): JsonResponse
    {
        if ($alert->admin_id && $alert->admin_id !== auth()->id()) {
            abort(403);
        }

        $alert->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الإشعار كمقروء',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        AdminAlert::forAdmin()
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الكل كمقروء',
        ]);
    }

    /**
     * Dismiss / delete a notification.
     */
    public function dismiss(AdminAlert $alert): JsonResponse
    {
        if ($alert->admin_id && $alert->admin_id !== auth()->id()) {
            abort(403);
        }

        $alert->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإشعار',
        ]);
    }
}
