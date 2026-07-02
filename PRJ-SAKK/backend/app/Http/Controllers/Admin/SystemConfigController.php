<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use App\Models\NotificationTemplate;
use App\Models\ServiceConfig;
use App\Services\AdminOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SystemConfigController extends Controller
{
    /** Tables that are SAFE to clean. Financial/identity tables are never listed. */
    private const CLEANABLE = [
        'password_reset_tokens' => 'رموز إعادة تعيين كلمة المرور المنتهية',
        'sessions' => 'الجلسات القديمة (> 30 يوم)',
        'audit_logs' => 'سجلات التدقيق القديمة (> 90 يوم)',
        'integration_logs' => 'سجلات التكاملات القديمة (> 90 يوم)',
        'user_notifications' => 'الإشعارات المقروءة القديمة (> 60 يوم)',
    ];

    /** Tables that must NEVER be touched by the cleaner (hard guard). */
    private const PROTECTED_TABLES = [
        'transactions', 'wallets', 'users', 'gold_transactions', 'gold_wallets',
        'savings_goals', 'savings_transactions', 'virtual_cards', 'agents', 'merchants',
    ];

    // ════════════════════ Section 1: Services (managed via Integrations page) ════════════════════

    public function updateService(Request $request, ServiceConfig $service): JsonResponse|RedirectResponse
    {
        $request->validate([
            'credentials' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Step 2: OTP verification — apply pending changes
        if ($request->has('pending_token') && $request->has('otp_code')) {
            return $this->applyServicePendingUpdate($request, $service);
        }

        // Step 1: detect credential changes → require OTP
        $submittedCreds = array_filter(
            $request->input('credentials', []),
            fn($v) => $v !== null && $v !== ''
        );

        if (!empty($submittedCreds)) {
            $otp = app(AdminOtpService::class);
            $token = $otp->generateToken();
            $otp->storePending($token, [
                'service_id' => $service->id,
                'credentials' => $request->input('credentials', []),
                'settings' => $request->input('settings', $service->settings),
                'is_active' => $request->boolean('is_active'),
            ]);
            $otp->send(auth()->user()->email);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'requires_otp' => true,
                    'pending_token' => $token,
                    'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
                ]);
            }
            return back()->with('info', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني');
        }

        // No credential changes → save directly
        $service->update([
            'settings' => $request->input('settings', $service->settings),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم حفظ إعدادات {$service->name_ar}",
            ]);
        }
        return back()->with('success', "تم حفظ إعدادات {$service->name_ar}");
    }

    public function testService(Request $request, ServiceConfig $service): JsonResponse|RedirectResponse
    {
        $required = match ($service->key) {
            'sms' => ['twilio_sid', 'twilio_token'],
            'mail' => ['mail_host', 'mail_username', 'mail_password'],
            'firebase_otp' => ['firebase_api_key', 'firebase_project_id'],
            'recaptcha' => ['site_key', 'secret_key'],
            default => [],
        };

        $ok = collect($required)->every(fn ($k) => !empty($service->getCredential($k)));

        $service->update(['last_tested_at' => now(), 'last_test_ok' => $ok]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $ok,
                'message' => $ok
                    ? "✅ إعدادات {$service->name_ar} مكتملة"
                    : "❌ بيانات اعتماد ناقصة لـ {$service->name_ar}",
            ]);
        }
        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? "✅ إعدادات {$service->name_ar} مكتملة" : "❌ بيانات اعتماد ناقصة لـ {$service->name_ar}"
        );
    }

    private function applyServicePendingUpdate(Request $request, ServiceConfig $service): JsonResponse|RedirectResponse
    {
        $otp = app(AdminOtpService::class);

        if (!$otp->verify($request->otp_code)) {
            $msg = 'رمز التحقق غير صحيح أو منتهي الصلاحية';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $pending = $otp->getPending($request->pending_token);
        if (!$pending || ($pending['service_id'] ?? null) !== $service->id) {
            $msg = 'انتهت صلاحية الطلب، يرجى المحاولة مرة أخرى';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $otp->clearPending($request->pending_token);

        $creds = array_merge($service->credentials ?? [], array_filter(
            $pending['credentials'] ?? [],
            fn ($v) => $v !== null && $v !== ''
        ));

        $service->update([
            'credentials' => $creds,
            'settings' => $pending['settings'] ?? $service->settings,
            'is_active' => $pending['is_active'] ?? $service->is_active,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم حفظ إعدادات {$service->name_ar} بعد التحقق",
            ]);
        }
        return back()->with('success', "تم حفظ إعدادات {$service->name_ar} بعد التحقق");
    }

    // ──────────────── WhatsApp (OpenWA) link panel — live QR / status ────────────────
    //
    // The OTP delivery channel is a self-hosted OpenWA gateway. Linking a WhatsApp
    // number is a QR-scan flow, so the admin card talks to the gateway live (these
    // endpoints proxy it). Config is the runtime truth in config/services.whatsapp.

    /** OpenWA statuses that mean the number is linked and ready to send. */
    private const WA_CONNECTED = ['authenticated', 'connected', 'ready', 'working', 'online'];

    /** Build an OpenWA HTTP client from the runtime config (base_url + api_key). */
    private function waClient()
    {
        $c = config('services.whatsapp');
        return [
            'http' => Http::withHeaders(['X-API-Key' => (string) ($c['api_key'] ?? '')])
                ->timeout((int) ($c['timeout'] ?? 15))
                ->acceptJson(),
            'base' => rtrim((string) ($c['base_url'] ?? ''), '/'),
            'session' => (string) ($c['session_id'] ?? ''),
        ];
    }

    /** Live connection status of the WhatsApp OTP gateway (JSON for the admin card). */
    public function whatsappStatus(): JsonResponse
    {
        $c = $this->waClient();
        if ($c['base'] === '' || $c['session'] === '') {
            return response()->json(['reachable' => false, 'connected' => false, 'status' => 'unconfigured']);
        }

        try {
            $res = $c['http']->get("{$c['base']}/api/sessions/{$c['session']}");
            if (!$res->successful()) {
                return response()->json(['reachable' => true, 'connected' => false, 'status' => 'no_session']);
            }
            $status = (string) ($res->json('status') ?? '');
            return response()->json([
                'reachable' => true,
                'connected' => in_array($status, self::WA_CONNECTED, true),
                'status' => $status,
                'phone' => $res->json('phone'),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['reachable' => false, 'connected' => false, 'status' => 'offline']);
        }
    }

    /** Start the session and return a QR (data URL) to scan, or the connected number. */
    public function whatsappLink(): JsonResponse
    {
        $c = $this->waClient();
        if ($c['base'] === '' || $c['session'] === '') {
            return response()->json(['ok' => false, 'status' => 'unconfigured', 'message' => 'بوابة الواتساب غير مُهيّأة في إعدادات الخادم'], 422);
        }

        try {
            // Idempotent: starting an already-running session is a safe no-op upstream.
            $c['http']->post("{$c['base']}/api/sessions/{$c['session']}/start");

            $res = $c['http']->get("{$c['base']}/api/sessions/{$c['session']}");
            $status = (string) ($res->json('status') ?? '');

            if (in_array($status, self::WA_CONNECTED, true)) {
                return response()->json(['ok' => true, 'connected' => true, 'status' => $status, 'phone' => $res->json('phone')]);
            }

            $qr = $c['http']->get("{$c['base']}/api/sessions/{$c['session']}/qr")->json('qrCode');
            return response()->json(['ok' => true, 'connected' => false, 'status' => $status, 'qr' => $qr]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'status' => 'offline', 'message' => 'تعذّر الوصول لبوابة الواتساب'], 502);
        }
    }

    // ──────────────── Telegram OTP channel — bot status / webhook ────────────────
    //
    // Telegram is the second OTP channel (account-linked). Unlike WhatsApp there
    // is no QR: the admin only registers the bot webhook once, then users link
    // their own chats from the app. Runtime truth is config/services.telegram.

    /** Live status of the Telegram bot + its webhook registration (JSON card). */
    public function telegramStatus(): JsonResponse
    {
        $tg = app(\App\Services\TelegramService::class);
        if (!$tg->configured()) {
            return response()->json(['reachable' => false, 'configured' => false, 'status' => 'unconfigured']);
        }

        $me = $tg->getMe();
        if (!$me) {
            return response()->json(['reachable' => false, 'configured' => true, 'status' => 'offline']);
        }

        $info = $tg->getWebhookInfo();
        return response()->json([
            'reachable' => true,
            'configured' => true,
            'username' => $me['username'] ?? null,
            'webhook_set' => !empty($info['url']),
            'webhook_url' => $info['url'] ?? null,
            'pending' => $info['pending_update_count'] ?? 0,
            'status' => !empty($info['url']) ? 'connected' : 'no_webhook',
        ]);
    }

    /** Register the bot webhook at this app's public URL. */
    public function telegramSetWebhook(): JsonResponse
    {
        $tg = app(\App\Services\TelegramService::class);
        if (!$tg->configured()) {
            return response()->json(['ok' => false, 'message' => 'لم يتم ضبط رمز بوت تلجرام على الخادم'], 422);
        }

        $url = rtrim((string) config('app.url'), '/') . '/api/v1/telegram/webhook';
        $res = $tg->setWebhook($url);

        if ($res['ok'] ?? false) {
            return response()->json(['ok' => true, 'webhook_url' => $url]);
        }
        return response()->json(['ok' => false, 'message' => $res['description'] ?? 'فشل ضبط الويبهوك'], 502);
    }

    // ════════════════════ Section 2: Notification Channels ════════════════════

    public function channels(): View
    {
        $channels = NotificationChannel::orderBy('event_key')->orderBy('recipient')->get()
            ->groupBy('event_key');
        return view('admin.system.channels', compact('channels'));
    }

    public function updateChannels(Request $request): RedirectResponse
    {
        $rows = $request->input('channels', []); // [id => [via_email=>1,...]]
        foreach ($rows as $id => $flags) {
            $channel = NotificationChannel::find($id);
            if (!$channel) {
                continue;
            }
            $channel->update([
                'via_email' => (bool) ($flags['via_email'] ?? false),
                'via_sms' => (bool) ($flags['via_sms'] ?? false),
                'via_push' => (bool) ($flags['via_push'] ?? false),
                'via_in_app' => (bool) ($flags['via_in_app'] ?? false),
                'is_active' => (bool) ($flags['is_active'] ?? false),
            ]);
        }
        return back()->with('success', 'تم حفظ قنوات الإشعارات');
    }

    // ════════════════════ Section 3: Notification Messages ════════════════════

    public function messages(): View
    {
        $templates = NotificationTemplate::orderBy('code')->get();
        return view('admin.system.messages', compact('templates'));
    }

    public function updateMessage(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'subject_ar' => 'nullable|string|max:255',
            'body_ar' => 'required|string|max:2000',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $template->update([
            'subject_ar' => $validated['subject_ar'] ?? null,
            'body_ar' => $validated['body_ar'],
            'subject' => $validated['subject'] ?? $template->subject,
            'body' => $validated['body'] ?? $template->body_ar ?? $validated['body_ar'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', "تم حفظ قالب: {$template->name}");
    }

    // ════════════════════ Section 4: Maintenance & SEO ════════════════════

    public function maintenance(): View
    {
        $stats = [];
        foreach (self::CLEANABLE as $table => $label) {
            $stats[$table] = [
                'label' => $label,
                'count' => Schema::hasTable($table) ? DB::table($table)->count() : 0,
                'exists' => Schema::hasTable($table),
            ];
        }
        return view('admin.system.maintenance', compact('stats'));
    }

    public function cleanDatabase(Request $request): RedirectResponse
    {
        $targets = $request->input('tables', []);
        $deleted = [];

        foreach ($targets as $table) {
            // Hard guards: only whitelisted, never protected.
            if (!array_key_exists($table, self::CLEANABLE)) {
                continue;
            }
            if (in_array($table, self::PROTECTED_TABLES, true) || !Schema::hasTable($table)) {
                continue;
            }

            $deleted[$table] = match ($table) {
                'password_reset_tokens' => DB::table($table)
                    ->where('created_at', '<', now()->subHours(2))->delete(),
                'sessions' => DB::table($table)
                    ->where('last_activity', '<', now()->subDays(30)->timestamp)->delete(),
                'audit_logs' => DB::table($table)
                    ->where('created_at', '<', now()->subDays(90))->delete(),
                'integration_logs' => DB::table($table)
                    ->where('created_at', '<', now()->subDays(90))->delete(),
                'user_notifications' => DB::table($table)
                    ->where('is_read', true)->where('created_at', '<', now()->subDays(60))->delete(),
                default => 0,
            };
        }

        $total = array_sum($deleted);
        return back()->with('success', "تم تنظيف $total سجل بأمان (لم تُمسّ السجلات المالية).");
    }
}
