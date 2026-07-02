<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function public(): JsonResponse
    {
        $settings = Setting::where('is_public', true)->get()->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }
}
