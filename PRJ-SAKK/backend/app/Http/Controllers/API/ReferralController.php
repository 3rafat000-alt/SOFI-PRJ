<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referralService) {}

    /** Referral code, configurable reward amount, and the user's referral stats. */
    public function info(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->referralService->info($request->user()),
        ]);
    }
}
