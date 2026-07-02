<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Governorate;
use App\Models\Property;
use App\Models\SiteStat;
use Illuminate\Http\JsonResponse;

/**
 * Returns aggregate site statistics for the landing page counters.
 */
class StatsController extends Controller
{
    /**
     * Return the current site stats.
     *
     * GET /api/v1/stats
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $stat = SiteStat::first();

        return response()->json([
            'data' => [
                'total_properties'   => Property::count(),
                'total_agents'       => Agent::count(),
                'total_agencies'     => Agency::count(),
                'total_governorates' => Governorate::count(),
                'happy_clients'      => $stat?->happy_clients ?? 0,
                'satisfaction_pct'   => $stat?->satisfaction_pct ?? 0,
            ],
        ]);
    }
}
