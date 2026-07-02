<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\ServiceConfig;
use App\Services\AdminOtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IntegrationController extends Controller
{
    public const CATEGORIES = [
        'payment' => ['label' => 'بوابات الدفع', 'icon' => 'payments', 'color' => '#059669', 'desc' => 'CCPayment والعملات الرقمية'],
        'messaging' => ['label' => 'الرسائل', 'icon' => 'chat', 'color' => '#2563eb', 'desc' => 'SMS والبريد الإلكتروني'],
        'notifications' => ['label' => 'الإشعارات', 'icon' => 'notifications', 'color' => '#d97706', 'desc' => 'FCM والإشعارات الفورية'],
        'location' => ['label' => 'الموقع', 'icon' => 'map', 'color' => '#6366f1', 'desc' => 'Google Maps وخدمات الموقع'],
        'cards' => ['label' => 'البطاقات', 'icon' => 'credit_card', 'color' => '#7c3aed', 'desc' => 'إصدار البطاقات'],
    ];

    public const SERVICE_ICONS = [
        'sms' => 'sms', 'mail' => 'mail', 'firebase_otp' => 'verified_user',
        'recaptcha' => 'security', 'whatsapp' => 'chat', 'telegram' => 'telegram',
    ];

    public const SERVICE_FIELD_LABELS = [
        'sms' => ['twilio_sid' => 'Twilio SID', 'twilio_token' => 'Twilio Auth Token', 'twilio_from' => 'رقم المُرسِل'],
        'mail' => ['mail_host' => 'MAIL HOST', 'mail_port' => 'PORT', 'mail_username' => 'USERNAME', 'mail_password' => 'PASSWORD', 'mail_from_address' => 'البريد المُرسِل'],
        'firebase_otp' => ['firebase_api_key' => 'API Key', 'firebase_project_id' => 'Project ID', 'firebase_app_id' => 'App ID'],
        'recaptcha' => ['site_key' => 'Site Key', 'secret_key' => 'Secret Key'],
        'whatsapp' => ['base_url' => 'عنوان البوابة (Base URL)', 'session_id' => 'معرّف الجلسة (Session ID)', 'api_key' => 'مفتاح API', 'default_country' => 'رمز الدولة الافتراضي'],
        'telegram' => ['bot_username' => 'اسم البوت (بدون @)', 'bot_token' => 'رمز البوت (Bot Token)'],
    ];

    /** عرض لوحة التكاملات */
    public function overview()
    {
        $integrations = Integration::orderBy('category')->orderBy('name')
            ->get()
            ->reject(fn($i) => $i->name === 'Virtual Cards');
        $services = ServiceConfig::orderBy('group')->orderBy('name')->get();
        return view('admin.integrations.overview', [
            'integrations' => $integrations,
            'services' => $services,
            'categories' => self::CATEGORIES,
        ]);
    }

    /** حفظ الإعدادات الأساسية — تتطلب OTP عند تغيير بيانات الاعتماد */
    public function update(Request $request, Integration $integration): JsonResponse
    {
        // Step 2: OTP verification — skip field validation
        if ($request->has('pending_token') && $request->has('otp_code')) {
            return $this->applyPendingUpdate($request, $integration);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'is_active' => 'boolean',
            'environment' => 'nullable|string|in:production,sandbox,development',
            'credentials' => 'nullable|array',
        ]);

        // Step 2: OTP verification — apply pending changes
        if ($request->has('pending_token') && $request->has('otp_code')) {
            return $this->applyPendingUpdate($request, $integration);
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
                'integration_id' => $integration->id,
                'data' => $validated,
            ]);
            $otp->send(auth()->user()->email);

            return response()->json([
                'success' => true,
                'requires_otp' => true,
                'pending_token' => $token,
                'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
            ]);
        }

        // No credential changes → save directly
        $integration->update([
            'name' => $validated['name'],
            'name_ar' => $validated['name_ar'],
            'is_active' => $request->boolean('is_active'),
            'environment' => $validated['environment'] ?? $integration->environment,
        ]);

        $integration->log('success', 'config_update', 'تم تحديث إعدادات التكامل');

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات بنجاح',
        ]);
    }

    private function applyPendingUpdate(Request $request, Integration $integration): JsonResponse
    {
        $otp = app(AdminOtpService::class);

        if (!$otp->verify($request->otp_code)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
            ], 422);
        }

        $pending = $otp->getPending($request->pending_token);
        if (!$pending || ($pending['integration_id'] ?? null) !== $integration->id) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الطلب، يرجى المحاولة مرة أخرى',
            ], 422);
        }

        $otp->clearPending($request->pending_token);
        $data = $pending['data'];

        // Merge: keep existing creds for blank fields
        $existing = $integration->credentials;
        $credentials = $data['credentials'] ?? $existing;
        if (is_array($credentials) && is_array($existing)) {
            foreach ($existing as $k => $v) {
                if (array_key_exists($k, $credentials) && ($credentials[$k] === '' || $credentials[$k] === null) && !empty($v)) {
                    $credentials[$k] = $v;
                }
            }
        }

        $integration->update([
            'name' => $data['name'] ?? $integration->name,
            'name_ar' => $data['name_ar'] ?? $integration->name_ar,
            'is_active' => isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) : $integration->is_active,
            'environment' => $data['environment'] ?? $integration->environment,
            'credentials' => $credentials,
        ]);

        $integration->log('success', 'config_update_secure', 'تم تحديث بيانات الاعتماد بعد التحقق');

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات بنجاح بعد التحقق',
        ]);
    }

    /** اختبار الاتصال */
    public function test(Integration $integration): JsonResponse
    {
        $ok = match($integration->key) {
            'ccpayment' => !empty($integration->getCredential('app_id'))
                && !empty($integration->getCredential('app_secret')),
            'messaging' => !empty($integration->getCredential('twilio_sid')),
            'email' => !empty($integration->getCredential('mail_host')),
            'notifications' => !empty($integration->getCredential('fcm_service_account')),
            'google_maps' => !empty($integration->getCredential('api_key')),
            default => false,
        };

        $level = $ok ? 'success' : 'error';
        $msg = $ok ? '✅ اتصال ناجح — الخدمة جاهزة' : '❌ فشل الاتصال — تأكد من بيانات الاعتماد';
        $integration->log($level, 'test', $msg);

        $integration->update(['last_synced_at' => now()]);

        return response()->json([
            'success' => $ok,
            'message' => $msg,
            'last_synced_at' => $integration->fresh()->last_synced_at,
        ]);
    }

    /** تفعيل/تعطيل */
    public function toggle(Integration $integration): JsonResponse
    {
        $integration->update(['is_active' => !$integration->is_active]);
        $s = $integration->is_active ? 'تفعيل' : 'تعطيل';
        $integration->log('success', 'toggle', "تم {$s} التكامل");

        return response()->json([
            'success' => true,
            'message' => "تم {$s} التكامل بنجاح",
            'is_active' => $integration->is_active,
        ]);
    }
}
