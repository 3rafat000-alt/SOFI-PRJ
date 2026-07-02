<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * List notifications for a user with optional filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Notification>
     */
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $query = Notification::byUser($user->id)->latestFirst();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['read'])) {
            $read = filter_var($filters['read'], FILTER_VALIDATE_BOOLEAN);
            $read ? $query->read() : $query->unread();
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);

        return $query->paginate($perPage);
    }

    /**
     * Create and dispatch a notification.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification
    {
        return Notification::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'data' => $data['data'] ?? [],
            'read_at' => null,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->markAsRead();

        return $notification;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::byUser($user->id)->unread()->update(['read_at' => now()]);
    }

    /**
     * Get unread count for a user.
     */
    public function unreadCount(User $user): int
    {
        return Notification::byUser($user->id)->unread()->count();
    }
}
