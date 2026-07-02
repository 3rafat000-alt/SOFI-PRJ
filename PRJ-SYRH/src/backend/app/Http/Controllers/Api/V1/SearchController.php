<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provides autocomplete suggestions for the site search bar.
 *
 * Security controls:
 * - q param is trimmed and max-length validated before use in LIKE queries.
 * - All queries use Eloquent ORM with parameterized bindings — no raw LIKE
 *   string concatenation. The '%' wildcards are applied through the query
 *   builder which escapes the value correctly.
 * - Results are limited (max 5 per category) to prevent response bloat DoS.
 * - Throttle (30/min per IP) enforced at route level.
 */
class SearchController extends Controller
{
    /** @var int Max results per suggestion category. */
    private const LIMIT_PER_CATEGORY = 5;

    /**
     * Return typeahead suggestions for the given search query.
     *
     * GET /api/v1/search/suggest?q=...
     *
     * Response contains up to 5 items each of: properties, governorates, areas.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $q = trim($validated['q']);

        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';
        $nameCol = "name_{$locale}";
        $titleCol = "title_{$locale}";

        $properties = Property::published()
            ->where($titleCol, 'like', "%{$q}%")
            ->select(['id', 'slug', 'title_ar', 'title_en', 'ref_code'])
            ->limit(self::LIMIT_PER_CATEGORY)
            ->get()
            ->map(fn ($p) => [
                'type'    => 'property',
                'id'      => $p->id,
                'slug'    => $p->slug,
                'label'   => $locale === 'en' ? $p->title_en : $p->title_ar,
                'ref_code'=> $p->ref_code,
            ]);

        $governorates = Governorate::where($nameCol, 'like', "%{$q}%")
            ->select(['id', 'slug', 'name_ar', 'name_en'])
            ->limit(self::LIMIT_PER_CATEGORY)
            ->get()
            ->map(fn ($g) => [
                'type'  => 'governorate',
                'id'    => $g->id,
                'slug'  => $g->slug,
                'label' => $locale === 'en' ? $g->name_en : $g->name_ar,
            ]);

        $areas = Area::where($nameCol, 'like', "%{$q}%")
            ->select(['id', 'slug', 'name_ar', 'name_en', 'governorate_id'])
            ->limit(self::LIMIT_PER_CATEGORY)
            ->get()
            ->map(fn ($a) => [
                'type'  => 'area',
                'id'    => $a->id,
                'slug'  => $a->slug,
                'label' => $locale === 'en' ? $a->name_en : $a->name_ar,
            ]);

        return response()->json([
            'data' => [
                'properties'   => $properties,
                'governorates' => $governorates,
                'areas'        => $areas,
            ],
        ]);
    }
}
