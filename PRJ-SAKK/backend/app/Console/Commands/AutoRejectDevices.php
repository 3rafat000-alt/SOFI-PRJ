<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class AutoRejectDevices extends Command
{
    protected $signature = 'devices:auto-reject';
    protected $description = 'Auto-reject devices pending approval for more than 72 hours';

    public function handle(): int
    {
        $cutoffTime = now()->subHours(72);

        $rejectedCount = 0;

        // Find all pending devices created > 72h ago
        $devicesToReject = Device::where('status', Device::STATUS_PENDING)
            ->where('created_at', '<', $cutoffTime)
            ->get();

        $notificationService = app(NotificationService::class);

        foreach ($devicesToReject as $device) {
            $device->update(['status' => Device::STATUS_REJECTED]);
            $rejectedCount++;

            // Notify user of rejection
            try {
                $notificationService->deviceRejected($device->user, $device->device_name);
            } catch (\Throwable $e) {
                $this->error("Failed to notify user {$device->user_id}: " . $e->getMessage());
            }
        }

        $this->info("Auto-rejected $rejectedCount device(s).");

        return self::SUCCESS;
    }
}
