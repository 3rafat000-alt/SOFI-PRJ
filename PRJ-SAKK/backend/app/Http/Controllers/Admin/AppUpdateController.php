<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin control for the mobile force-update policy.
 *
 * Writes the `app_update` group of `system_settings`; the public API
 * (App\Http\Controllers\API\AppController::version) serves these to the app,
 * which blocks users on a build below `app_min_build` until they download the
 * version published at `app_download_url`.
 */
class AppUpdateController extends Controller
{
    /** key => [type, default] */
    private const FIELDS = [
        'app_update_enabled' => ['boolean', true],
        'app_force_update'   => ['boolean', false],
        'app_min_build'      => ['integer', 1],
        'app_min_version'    => ['string',  '1.0.0'],
        'app_latest_build'   => ['integer', 1],
        'app_latest_version' => ['string',  '1.0.0'],
        'app_download_url'   => ['string',  ''],
        'app_update_title'   => ['string',  'تحديث مطلوب'],
        'app_update_message' => ['string',  'يتوفّر إصدار جديد من تطبيق صكّ. يرجى التحديث للمتابعة.'],
    ];

    public function index(): View
    {
        $cfg = [];
        foreach (self::FIELDS as $key => [$type, $default]) {
            $cfg[$key] = SystemSetting::get($key, $default);
        }

        return view('admin.system.app-update', ['cfg' => $cfg]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_update_enabled' => 'nullable|boolean',
            'app_force_update'   => 'nullable|boolean',
            'app_min_build'      => 'required|integer|min:1',
            'app_min_version'    => 'required|string|max:20',
            'app_latest_build'   => 'required|integer|min:1',
            'app_latest_version' => 'required|string|max:20',
            'app_download_url'   => 'required|url|max:500',
            'app_update_title'   => 'required|string|max:120',
            'app_update_message' => 'required|string|max:500',
        ], [], [
            'app_min_build'      => 'أدنى رقم بناء',
            'app_min_version'    => 'أدنى إصدار',
            'app_latest_build'   => 'رقم بناء أحدث إصدار',
            'app_latest_version' => 'أحدث إصدار',
            'app_download_url'   => 'رابط التحميل',
            'app_update_title'   => 'عنوان التحديث',
            'app_update_message' => 'رسالة التحديث',
        ]);

        // latest must never be older than the enforced minimum
        if ((int) $data['app_latest_build'] < (int) $data['app_min_build']) {
            return back()
                ->withInput()
                ->with('error', 'رقم بناء أحدث إصدار لا يمكن أن يكون أقل من أدنى رقم بناء مطلوب.');
        }

        SystemSetting::set('app_update_enabled', $request->boolean('app_update_enabled') ? '1' : '0', 'boolean');
        SystemSetting::set('app_force_update', $request->boolean('app_force_update') ? '1' : '0', 'boolean');
        SystemSetting::set('app_min_build', (string) $data['app_min_build'], 'integer');
        SystemSetting::set('app_min_version', $data['app_min_version'], 'string');
        SystemSetting::set('app_latest_build', (string) $data['app_latest_build'], 'integer');
        SystemSetting::set('app_latest_version', $data['app_latest_version'], 'string');
        SystemSetting::set('app_download_url', $data['app_download_url'], 'string');
        SystemSetting::set('app_update_title', $data['app_update_title'], 'string');
        SystemSetting::set('app_update_message', $data['app_update_message'], 'string');

        return back()->with('success', 'تم حفظ سياسة تحديث التطبيق.');
    }
}
