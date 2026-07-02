<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks money-movement requests coming from a device that is still pending
 * approval, was rejected, or is within its 48-hour post-approval security hold.
 * Legacy clients that don't send X-Device-Id are not blocked.
 */
class EnsureDeviceCanTransact
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $deviceId = $request->header('X-Device-Id');

        if (!$user || !$deviceId) {
            return $next($request); // no device context — don't break legacy/web
        }

        $device = $user->devices()->where('device_id', $deviceId)->first();
        if (!$device) {
            return $next($request); // unknown device; will register on next launch
        }

        if ($device->isPending()) {
            return $this->deny(
                'هذا الجهاز بانتظار الموافقة. وافق عليه من جهاز موثوق لتتمكّن من إجراء المعاملات.',
                'device_pending',
            );
        }

        if ($device->status === Device::STATUS_REJECTED) {
            return $this->deny(
                'تم رفض هذا الجهاز ولا يمكنه إجراء المعاملات.',
                'device_rejected',
            );
        }

        if ($device->isTransactionLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'لأسباب أمنية لا يمكن إجراء معاملات من هذا الجهاز قبل مرور 48 ساعة على ربطه.',
                'code' => 'device_locked',
                'data' => [
                    'locked_until' => $device->transactions_locked_until?->toIso8601String(),
                ],
            ], 403);
        }

        return $next($request);
    }

    private function deny(string $message, string $code): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], 403);
    }
}
