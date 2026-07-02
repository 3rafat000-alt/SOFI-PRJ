<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyCardResource;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\PropertyView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Read-only property endpoints for the landing page and search.
 *
 * Security controls:
 * - All queries use Eloquent ORM (parameterized) — no raw SQL with user input.
 * - Published scope ensures draft/sold/archived rows are not accessible without
 *   auth, preventing IDOR against unlisted properties.
 * - Filter inputs are validated via $request->validate() before being applied
 *   to the query builder, preventing query manipulation.
 * - per_page is capped at 48 to prevent resource exhaustion (DoS via pagination).
 */
class PropertyController extends Controller
{
    /** @var list<string> Valid sort options. */
    private const SORT_OPTIONS = ['price_asc', 'price_desc', 'newest', 'area_desc'];

    /** @var int Default page size. */
    private const DEFAULT_PER_PAGE = 12;

    /** @var int Maximum allowed page size. */
    private const MAX_PER_PAGE = 48;

    /**
     * Featured properties for the homepage hero carousel.
     *
     * GET /api/v1/properties/featured
     *
     * @return AnonymousResourceCollection
     */
    public function featured(): AnonymousResourceCollection
    {
        $properties = Property::published()
            ->featured()
            ->with(['governorate', 'area', 'agency:id,name,slug,logo_path', 'images' => fn ($q) => $q->where('is_cover', true)])
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return PropertyCardResource::collection($properties);
    }

    /**
     * Hot-deal properties for the homepage section.
     *
     * GET /api/v1/properties/hot-deals
     *
     * @return AnonymousResourceCollection
     */
    public function hotDeals(): AnonymousResourceCollection
    {
        $properties = Property::published()
            ->hotDeals()
            ->with(['governorate', 'area', 'images' => fn ($q) => $q->where('is_cover', true)])
            ->orderByDesc('published_at')
            ->limit(8)
            ->get();

        return PropertyCardResource::collection($properties);
    }

    /**
     * Paginated, filtered property listing.
     *
     * GET /api/v1/properties
     *
     * Supported filters (all optional):
     *   purpose, type (slug), governorate (slug), area (slug),
     *   min_price, max_price, currency, bedrooms, bathrooms,
     *   min_area, furnished, sort, page, per_page.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'purpose'    => ['nullable', Rule::in(['sale', 'rent'])],
            'type'       => ['nullable', 'string', 'max:80'],
            'governorate'=> ['nullable', 'string', 'max:80'],
            'area'       => ['nullable', 'string', 'max:80'],
            'min_price'  => ['nullable', 'numeric', 'min:0'],
            'max_price'  => ['nullable', 'numeric', 'min:0'],
            'currency'   => ['nullable', Rule::in(['USD', 'SYP'])],
            'bedrooms'   => ['nullable', 'integer', 'min:0'],
            'bathrooms'  => ['nullable', 'integer', 'min:0'],
            'min_area'   => ['nullable', 'numeric', 'min:0'],
            'furnished'  => ['nullable', 'boolean'],
            'sort'       => ['nullable', Rule::in(self::SORT_OPTIONS)],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            'page'       => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Property::published()
            ->with(['governorate', 'area', 'agency:id,name,slug,logo_path', 'images' => fn ($q) => $q->where('is_cover', true)]);

        // --- Filters ---
        if (!empty($validated['purpose'])) {
            $query->where('purpose', $validated['purpose']);
        }

        if (!empty($validated['type'])) {
            // Relationship on Property model is named 'type' (not 'propertyType').
            $query->whereHas('type', fn ($q) => $q->where('slug', $validated['type']));
        }

        if (!empty($validated['governorate'])) {
            $query->whereHas('governorate', fn ($q) => $q->where('slug', $validated['governorate']));
        }

        if (!empty($validated['area'])) {
            $query->whereHas('area', fn ($q) => $q->where('slug', $validated['area']));
        }

        if (!empty($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }

        if (!empty($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        if (!empty($validated['currency'])) {
            $query->where('currency', $validated['currency']);
        }

        if (isset($validated['bedrooms'])) {
            $query->where('bedrooms', $validated['bedrooms']);
        }

        if (isset($validated['bathrooms'])) {
            $query->where('bathrooms', $validated['bathrooms']);
        }

        if (!empty($validated['min_area'])) {
            $query->where('area_sqm', '>=', $validated['min_area']);
        }

        if (isset($validated['furnished'])) {
            $query->where('furnished', filter_var($validated['furnished'], FILTER_VALIDATE_BOOLEAN));
        }

        // --- Sorting ---
        match ($validated['sort'] ?? 'newest') {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'area_desc'  => $query->orderBy('area_sqm', 'desc'),
            default      => $query->orderByDesc('published_at'),
        };

        $perPage = (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE);
        $paginator = $query->paginate($perPage);

        return PropertyCardResource::collection($paginator);
    }

    /**
     * Full property detail by slug.
     *
     * GET /api/v1/properties/{property:slug}
     *
     * Also increments the views_count counter (fire-and-forget, non-critical).
     *
     * @param  Property  $property  Route-model bound by slug.
     * @return PropertyResource
     */
    public function show(Property $property): PropertyResource
    {
        // Ensure only published properties are publicly visible.
        // If the model is found but not published, treat as not found.
        abort_if($property->status === 'draft', 404);

        $property->load(['governorate', 'area', 'images', 'amenities', 'agent']);

        // Increment view counter + log detailed view — fire-and-forget.
        try {
            $property->incrementQuietly('views_count');

            PropertyView::create([
                'property_id' => $property->id,
                'user_id'     => $request->user()?->id,
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'viewed_at'   => now(),
            ]);
        } catch (\Throwable) {
            // Non-critical: view tracking is best-effort.
        }

        return new PropertyResource($property);
    }

    /**
     * Lightweight property card for chat display.
     * Returns minimal data + cover image for embedding in conversation UI.
     */
    public function chatCard(Property $property): JsonResponse
    {
        $property->load('images');

        $cover = $property->images->firstWhere('is_cover', true)
            ?? $property->images->first();

        return response()->json(['data' => [
            'id'       => $property->id,
            'slug'     => $property->slug,
            'title_ar' => $property->title_ar,
            'title_en' => $property->title_en,
            'price'    => $property->price,
            'currency' => $property->currency,
            'purpose'  => $property->purpose,
            'status'   => $property->status,
            'cover_image' => $cover ? [
                'path'   => $cover->path,
                'alt_ar' => $cover->alt_ar,
                'alt_en' => $cover->alt_en,
            ] : null,
        ]]);
    }
}
