<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin control for the technical-support contact channels.
 *
 * Writes the `support` group of `system_settings`; the public API
 * (App\Http\Controllers\API\AppController::support) serves these to the app so
 * the "تواصل معنا / الدعم الفني" screen shows live, admin-managed contacts
 * (email, phone, WhatsApp, Telegram, hours) with no rebuild required.
 */
class SupportController extends Controller
{
    /** key => [type, default] */
    private const FIELDS = [
        'support_enabled'    => ['boolean', true],
        'support_email'      => ['string',  'support@zanjour.com'],
        'support_phone'      => ['string',  ''],
        'support_whatsapp'   => ['string',  ''],
        'support_telegram'   => ['string',  ''],
        'support_hours'      => ['string',  'السبت – الخميس · 9 صباحاً – 5 مساءً'],
        'support_message'    => ['string',  'فريق الدعم الفني جاهز لمساعدتك. تواصل معنا عبر أي قناة بالأسفل.'],
        'support_faq_url'    => ['string',  ''],
    ];

    public function index(): View
    {
        $cfg = [];
        foreach (self::FIELDS as $key => [$type, $default]) {
            $cfg[$key] = SystemSetting::get($key, $default);
        }

        return view('admin.system.support', ['cfg' => $cfg]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'support_enabled'  => 'nullable|boolean',
            'support_email'    => 'nullable|email|max:120',
            'support_phone'    => 'nullable|string|max:40',
            'support_whatsapp' => 'nullable|string|max:40',
            'support_telegram' => 'nullable|string|max:120',
            'support_hours'    => 'nullable|string|max:160',
            'support_message'  => 'nullable|string|max:500',
            'support_faq_url'  => 'nullable|url|max:500',
        ], [], [
            'support_email'    => 'البريد',
            'support_phone'    => 'الهاتف',
            'support_whatsapp' => 'واتساب',
            'support_telegram' => 'تيليجرام',
            'support_hours'    => 'ساعات العمل',
            'support_message'  => 'رسالة الدعم',
            'support_faq_url'  => 'رابط الأسئلة الشائعة',
        ]);

        SystemSetting::set('support_enabled', $request->boolean('support_enabled') ? '1' : '0', 'boolean');
        SystemSetting::set('support_email', $data['support_email'] ?? '', 'string');
        SystemSetting::set('support_phone', $data['support_phone'] ?? '', 'string');
        SystemSetting::set('support_whatsapp', $data['support_whatsapp'] ?? '', 'string');
        SystemSetting::set('support_telegram', $data['support_telegram'] ?? '', 'string');
        SystemSetting::set('support_hours', $data['support_hours'] ?? '', 'string');
        SystemSetting::set('support_message', $data['support_message'] ?? '', 'string');
        SystemSetting::set('support_faq_url', $data['support_faq_url'] ?? '', 'string');

        return back()->with('success', 'تم حفظ بيانات الدعم الفني');
    }
}
