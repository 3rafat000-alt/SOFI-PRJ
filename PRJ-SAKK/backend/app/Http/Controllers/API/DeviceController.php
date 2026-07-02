<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    /**
     * Register / heartbeat the current device. Called by the app on launch.
     * The very first device on an account is auto-approved; every subsequent
     * new device is created as "pending" and the user is notified to approve
     * or reject it. Returns the current device's status & security-hold state.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:255',
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|string|in:ios,android,web',
            'public_key' => 'nullable|string',
        ], [
            'device_id.required' => 'معرف الجهاز مطلوب.',
            'device_name.required' => 'اسم الجهاز مطلوب.',
            'device_type.in' => 'نوع الجهاز غير صالح.',
        ]);

        $user = $request->user();
        $device = $user->devices()->where('device_id', $validated['device_id'])->first();

        if ($device) {
            $device->update([
                'device_name' => $validated['device_name'],
                'device_type' => $validated['device_type'],
                'last_active_at' => now(),
                'last_used_at' => now(),
                'last_ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->transform($device, $validated['device_id']),
            ]);
        }

        // New device. First device on the account is the trusted original.
        $deviceCount = $user->devices()->count();
        $isFirst = $deviceCount === 0;

        if ($deviceCount >= 50) {
            return response()->json([
                'success' => false,
                'message' => 'تم الوصول إلى الحد الأقصى لعدد الأجهزة المسموح بها لهذا الحساب.',
            ], 422);
        }

        $device = $user->devices()->create([
            'device_id' => $validated['device_id'],
            'device_name' => $validated['device_name'],
            'device_type' => $validated['device_type'],
            'public_key' => $validated['public_key'] ?? '',
            'is_trusted' => $isFirst,
            'status' => $isFirst ? Device::STATUS_APPROVED : Device::STATUS_PENDING,
            'approved_at' => $isFirst ? now() : null,
            // Original device has no hold; new devices get a hold only once approved.
            'transactions_locked_until' => null,
            'last_active_at' => now(),
            'last_used_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        if (!$isFirst) {
            $this->notify(
                $user->id,
                'محاولة ربط جهاز جديد',
                "تم طلب ربط جهاز جديد بحسابك: {$device->device_name}. راجع الأجهزة المتصلة للموافقة أو الرفض.",
                $device,
                'device_link_request',
            );
        }

        return response()->json([
            'success' => true,
            'data' => $this->transform($device, $validated['device_id']),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $current = $request->header('X-Device-Id');
        $devices = $request->user()->devices()
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByDesc('last_active_at')
            ->get()
            ->map(fn (Device $d) => $this->transform($d, $current));

        return response()->json([
            'success' => true,
            'data' => $devices,
            'meta' => [
                'pending_count' => $request->user()->devices()
                    ->where('status', Device::STATUS_PENDING)->count(),
            ],
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $device = $request->user()->devices()->findOrFail($id);

        if (!$device->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الموافقة على هذا الجهاز.',
            ], 422);
        }

        $device->approveWithHold();

        $this->notify(
            $request->user()->id,
            'تمت الموافقة على جهاز جديد',
            "تمت الموافقة على {$device->device_name}. لأسباب أمنية لا يمكنه إجراء أي معاملة قبل مرور 48 ساعة.",
            $device,
            'device_approved',
        );

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على الجهاز. سيتمكّن من إجراء المعاملات بعد 48 ساعة.',
            'data' => $this->transform($device, $request->header('X-Device-Id')),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $device = $request->user()->devices()->findOrFail($id);
        $name = $device->device_name;

        $device->update([
            'status' => Device::STATUS_REJECTED,
            'is_trusted' => false,
            'transactions_locked_until' => null,
        ]);

        $this->notify(
            $request->user()->id,
            'تم رفض ربط جهاز',
            "تم رفض ربط الجهاز: {$name}. لن يتمكّن من الوصول إلى حسابك.",
            $device,
            'device_rejected',
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الجهاز.',
        ]);
    }

    public function remove(Request $request, int $id): JsonResponse
    {
        $deleted = $request->user()->devices()->where('id', $id)->delete();

        return response()->json([
            'success' => (bool) $deleted,
            'message' => $deleted ? 'تم حذف الجهاز.' : 'الجهاز غير موجود.',
        ]);
    }

    private function transform(Device $d, ?string $currentDeviceId): array
    {
        return [
            'id' => $d->id,
            'device_id' => $d->device_id,
            'device_name' => $d->device_name,
            'device_type' => $d->device_type,
            'status' => $d->status,
            'is_current' => $currentDeviceId !== null && $d->device_id === $currentDeviceId,
            'is_trusted' => $d->is_trusted,
            'transaction_locked' => $d->isTransactionLocked(),
            'transactions_locked_until' => $d->transactions_locked_until?->toIso8601String(),
            'approved_at' => $d->approved_at?->toIso8601String(),
            'last_active_at' => $d->last_active_at?->toIso8601String(),
            'created_at' => $d->created_at?->toIso8601String(),
        ];
    }

    private function notify(int $userId, string $title, string $body, Device $device, string $code): void
    {
        UserNotification::create([
            'user_id' => $userId,
            'uuid' => (string) Str::uuid(),
            'template_code' => $code,
            'channel' => 'in_app',
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => $code,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
            ],
            'action_url' => '/settings/security',
            'sent_at' => now(),
            'status' => 'sent',
            'is_read' => false,
        ]);
    }
}
