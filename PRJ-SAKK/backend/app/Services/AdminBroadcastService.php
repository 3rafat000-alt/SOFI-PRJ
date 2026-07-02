<?php

namespace App\Services;

use App\Models\AdminNotification;

/**
 * Sends an admin broadcast (push campaign) to its target audience via FCM and
 * records the sent/failed tallies on the AdminNotification row.
 *
 * Shared by the admin web panel (PushNotificationController), the admin API
 * (AdminController), and the scheduled-dispatch command.
 */
class AdminBroadcastService
{
    public function __construct(private readonly FCMService $fcm)
    {
    }

    /**
     * Deliver the notification now. Returns ['sent' => int, 'failed' => int].
     */
    public function dispatch(AdminNotification $notification): array
    {
        $users = $notification->getTargetUsers()->get();

        $tokens = [];
        $failed = 0;
        foreach ($users as $user) {
            if ($user->fcm_token) {
                $tokens[] = $user->fcm_token;
            } else {
                $failed++;
            }
        }

        $sent = 0;
        if (!empty($tokens)) {
            $sent = $this->fcm->sendToMultiple(
                $tokens,
                $notification->title,
                $notification->body,
                ['type' => 'admin_notification', 'notification_id' => (string) $notification->id],
            );
            $failed += count($tokens) - $sent;
        }

        $notification->update([
            'status' => 'sent',
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sent_at' => now(),
        ]);

        return ['sent' => $sent, 'failed' => $failed];
    }
}
