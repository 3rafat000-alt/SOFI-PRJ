<?php

namespace App\Console\Commands;

use App\Models\AdminNotification;
use App\Services\AdminBroadcastService;
use Illuminate\Console\Command;

/**
 * Sends admin broadcasts whose scheduled time has arrived. Run every minute
 * by the scheduler (see routes/console.php).
 */
class DispatchScheduledNotifications extends Command
{
    protected $signature = 'notifications:dispatch-scheduled';

    protected $description = 'Dispatch scheduled admin push notifications that are now due';

    public function handle(AdminBroadcastService $broadcast): int
    {
        $due = AdminNotification::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($due as $notification) {
            $result = $broadcast->dispatch($notification);
            $this->info("#{$notification->id} «{$notification->title}» → sent {$result['sent']}, failed {$result['failed']}");
        }

        return self::SUCCESS;
    }
}
