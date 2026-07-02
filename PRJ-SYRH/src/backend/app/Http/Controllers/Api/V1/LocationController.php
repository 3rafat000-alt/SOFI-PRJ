<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AreaResource;
use App\Http\Resources\GovernorateResource;
use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Exposes governorates and areas for location filters and dropdowns.
 */
class LocationController extends Controller
{
    /**
     * Return all governorates with their top-5 areas by properties_count.
     *
     * GET /api/v1/locations
     *
     * Response shape:
     * {
     *   "data": {
     *     "governorates": [...],
     *     "popular_areas": [...]
     *   }
     * }
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $governorates = Governorate::orderBy('name_ar')->get();

        // Popular areas: top 10 by properties_count across all governorates.
        $popularAreas = Area::orderByDesc('properties_count')
            ->with('governorate')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'governorates'  => GovernorateResource::collection($governorates),
                'popular_areas' => AreaResource::collection($popularAreas),
            ],
        ]);
    }

    /**
     * Return all areas belonging to the given governorate.
     *
     * GET /api/v1/locations/{governorate}/areas
     *
     * @param  string  $governorate  Governorate slug.
     * @return AnonymousResourceCollection
     */
    public function areas(string $governorate): AnonymousResourceCollection
    {
        $gov = Governorate::where('slug', $governorate)->firstOrFail();

        $areas = Area::where('governorate_id', $gov->id)
            ->orderBy('name_ar')
            ->get();

        return AreaResource::collection($areas);
    }
}
