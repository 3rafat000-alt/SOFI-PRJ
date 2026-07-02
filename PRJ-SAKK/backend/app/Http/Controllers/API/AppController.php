<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public app-metadata endpoint consumed by the mobile clients at boot.
 *
 * The admin panel writes the force-update policy into `system_settings`
 * (group `app_update`); the app reads it here and decides whether to show
 * the blocking "Update Required" screen. The server also computes
 * `update_required` for the caller's build so the gate logic lives in one
 * place — the app just needs to honour the flag.
 */
class AppController extends Controller
{
    /**
     * GET /api/v1/app/version
     *
     * Optional query params (so the server can decide for the caller):
     *   - build    int     the installed build number (versionCode)
     *   - platform string  android | ios  (reserved; android-only for now)
     */
    public function version(Request $request): JsonResponse
    {
        $enabled    = (bool) (SystemSetting::get('app_update_enabled', true));
        $forceAll   = (bool) (SystemSetting::get('app_force_update', false));
        $minBuild   = (int) (SystemSetting::get('app_min_build', 0));
        $minVersion = (string) (SystemSetting::get('app_min_version', '1.0.0'));
        $latestVer  = (string) (SystemSetting::get('app_latest_version', $minVersion));
        $latestBld  = (int) (SystemSetting::get('app_latest_build', $minBuild));
        $downloadUrl = (string) (SystemSetting::get('app_download_url', ''));
        $title      = (string) (SystemSetting::get('app_update_title', 'تحديث مطلوب'));
        $message    = (string) (SystemSetting::get(
            'app_update_message',
            'يتوفّر إصدار جديد من تطبيق صكّ. يرجى التحديث للمتابعة.'
        ));

        // Decide for the caller when it tells us its build. Fail-open: an
        // unknown/zero installed build is treated as up-to-date (never brick a
        // client we cannot reason about).
        $installedBuild = (int) $request->query('build', 0);
        $updateRequired = false;
        if ($enabled && $installedBuild > 0) {
            $updateRequired = $forceAll || ($installedBuild < $minBuild);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'enabled'         => $enabled,
                'min_version'     => $minVersion,
                'min_build'       => $minBuild,
                'latest_version'  => $latestVer,
                'latest_build'    => $latestBld,
                'force_update'    => $forceAll,
                'update_required' => $updateRequired,
                'download_url'    => $downloadUrl,
                'title'           => $title,
                'message'         => $message,
            ],
        ]);
    }

    /**
     * GET /api/v1/app/support
     *
     * Public technical-support contacts the app renders in its "تواصل معنا"
     * screen. Managed live from the admin panel (group `support`). Empty
     * channels are omitted so the client only shows what is actually set.
     */
    public function support(): JsonResponse
    {
        $enabled = (bool) SystemSetting::get('support_enabled', true);

        $channels = array_filter([
            'email'    => (string) SystemSetting::get('support_email', ''),
            'phone'    => (string) SystemSetting::get('support_phone', ''),
            'whatsapp' => (string) SystemSetting::get('support_whatsapp', ''),
            'telegram' => (string) SystemSetting::get('support_telegram', ''),
            'faq_url'  => (string) SystemSetting::get('support_faq_url', ''),
        ], fn ($v) => $v !== '');

        return response()->json([
            'success' => true,
            'data' => [
                'enabled'  => $enabled,
                'hours'    => (string) SystemSetting::get('support_hours', ''),
                'message'  => (string) SystemSetting::get('support_message', ''),
                'channels' => $channels,
            ],
        ]);
    }
}
