<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgenciesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Agency::active()->withCount(['properties', 'agents']);

        // Filter by governorate slug — agencies with properties in that governorate
        if ($govSlug = $request->governorate) {
            $query->whereHas('properties', fn ($q) => $q
                ->whereHas('governorate', fn ($q) => $q->where('slug', $govSlug))
            );
        }

        // Filter by area slug — agencies with properties in that area
        if ($areaSlug = $request->area) {
            $query->whereHas('properties', fn ($q) => $q
                ->whereHas('area', fn ($q) => $q->where('slug', $areaSlug))
            );
        }

        $agencies = $query->orderBy('name')->get()->map(fn ($a) => [
            'id'               => $a->id,
            'name'             => $a->name,
            'slug'             => $a->slug,
            'logo_path'        => $a->logo_path,
            'description_ar'   => $a->description_ar,
            'description_en'   => $a->description_en,
            'properties_count' => (int) $a->properties_count,
            'agents_count'     => (int) $a->agents_count,
        ]);

        return response()->json(['data' => $agencies]);
    }

    public function show(string $slug): JsonResponse
    {
        $agency = Agency::active()
            ->where('slug', $slug)
            ->withCount(['properties', 'agents'])
            ->firstOrFail();

        $properties = Property::where('agency_id', $agency->id)
            ->where('status', 'available')
            ->with(['type', 'governorate', 'area', 'images'])
            ->latest()
            ->take(12)
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'ref_code'    => $p->ref_code,
                'slug'        => $p->slug,
                'title_ar'    => $p->title_ar,
                'title_en'    => $p->title_en,
                'purpose'     => $p->purpose,
                'status'      => $p->status,
                'price'       => $p->price,
                'currency'    => $p->currency,
                'area_sqm'    => $p->area_sqm,
                'bedrooms'    => $p->bedrooms,
                'bathrooms'   => $p->bathrooms,
                'is_featured' => $p->is_featured,
                'is_hot_deal' => $p->is_hot_deal,
                'cover_image' => (function () use ($p) {
                    $img = $p->images->firstWhere('is_cover', true) ?? $p->images->first();
                    return $img ? [
                        'path'   => $img->path,
                        'alt_ar' => $img->alt_ar,
                        'alt_en' => $img->alt_en,
                    ] : null;
                })(),
                'governorate' => $p->governorate ? ['id' => $p->governorate->id, 'name' => $p->governorate->name, 'slug' => $p->governorate->slug] : null,
                'area'        => $p->area ? ['id' => $p->area->id, 'name' => $p->area->name, 'slug' => $p->area->slug] : null,
            ]);

        return response()->json(['data' => [
            'id'               => $agency->id,
            'name'             => $agency->name,
            'slug'             => $agency->slug,
            'logo_path'        => $agency->logo_path,
            'cover_path'       => $agency->cover_path,
            'address'          => $agency->address,
            'description_ar'   => $agency->description_ar,
            'description_en'   => $agency->description_en,
            'properties_count' => (int) $agency->properties_count,
            'agents_count'     => (int) $agency->agents_count,
            'properties'       => $properties,
        ]]);
    }

    /**
     * Lightweight property list for chat property selector.
     * Returns minimal fields: id, slug, title, cover, price, currency, purpose.
     */
    public function properties(int $agencyId): JsonResponse
    {
        $agency = Agency::active()->findOrFail($agencyId);

        $properties = Property::where('agency_id', $agency->id)
            ->where('status', 'available')
            ->with('images')
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id'        => $p->id,
                'slug'      => $p->slug,
                'title_ar'  => $p->title_ar,
                'title_en'  => $p->title_en,
                'price'     => $p->price,
                'currency'  => $p->currency,
                'purpose'   => $p->purpose,
                'status'    => $p->status,
                'cover'     => (function () use ($p) {
                    $img = $p->images->firstWhere('is_cover', true) ?? $p->images->first();
                    return $img ? $img->path : null;
                })(),
            ]);

        return response()->json(['data' => $properties]);
    }
}
