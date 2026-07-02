<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminNotification;
use App\Models\User;
use App\Services\AdminBroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin panel: compose and broadcast push notifications / marketing campaigns,
 * with audience targeting, optional scheduling, and a send history.
 */
class PushNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $history = AdminNotification::with('admin')->latest()->paginate(15);

        return view('admin.notifications.index', [
            'history' => $history,
            'audiences' => $this->audienceCounts(),
        ]);
    }

    public function send(Request $request, AdminBroadcastService $broadcast): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:all,kyc_verified,active,inactive,specific'],
            'user_ids' => ['required_if:type,specific', 'nullable', 'string'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ], [], [
            'title' => 'العنوان',
            'body' => 'النص',
            'type' => 'الجمهور',
            'user_ids' => 'المستخدمون',
            'scheduled_at' => 'وقت الجدولة',
        ]);

        $userIds = null;
        if ($validated['type'] === 'specific') {
            $userIds = collect(explode(',', (string) ($validated['user_ids'] ?? '')))
                ->map(fn ($s) => (int) trim($s))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($userIds)) {
                return back()->withInput()->with('error', 'أدخل مُعرّفات مستخدمين صحيحة (مفصولة بفواصل).');
            }
        }

        $scheduled = !empty($validated['scheduled_at']);

        $notification = AdminNotification::create([
            'admin_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'type' => $validated['type'],
            'user_ids' => $userIds,
            'scheduled_at' => $scheduled ? $validated['scheduled_at'] : null,
            'status' => $scheduled ? 'scheduled' : 'pending',
        ]);

        ActivityLog::log(
            'notification.broadcast',
            null,
            $notification,
            null,
            ['type' => $notification->type, 'scheduled' => $scheduled],
            "Push broadcast «{$notification->title}» → {$notification->type}",
        );

        if ($scheduled) {
            return back()->with('success', "تمت جدولة «{$notification->title}» للإرسال في "
                . $notification->scheduled_at->format('Y-m-d · H:i') . '.');
        }

        $result = $broadcast->dispatch($notification);

        return back()->with('success', "أُرسل «{$notification->title}» إلى {$result['sent']} جهاز"
            . ($result['failed'] ? " (تعذّر الوصول لـ {$result['failed']})" : '') . '.');
    }

    /**
     * Reachable-user counts per audience (only users with a registered token).
     */
    private function audienceCounts(): array
    {
        return [
            'all' => User::where('is_active', true)->whereNotNull('fcm_token')->count(),
            'kyc_verified' => User::where('kyc_status', 'verified')->whereNotNull('fcm_token')->count(),
            'active' => User::where('is_active', true)->whereNotNull('fcm_token')->count(),
            'inactive' => User::where('is_active', false)->whereNotNull('fcm_token')->count(),
            'reachable' => User::whereNotNull('fcm_token')->count(),
            'total' => User::count(),
        ];
    }
}
