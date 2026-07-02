<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Concerns\HasLocalizedFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight property representation for grids and carousels.
 *
 * Only the fields the listing card UI needs — no images array, no amenities,
 * no description body. Cover image is resolved from the eager-loaded images
 * relationship (where is_cover = true) if available.
 *
 * Assumes eager loading of:
 *   - governorate (GovernorateResource)
 *   - area (AreaResource)
 *   - images (filtered to is_cover = true, or the controller eager-loads all
 *     and we take the first cover here)
 */
class PropertyCardResource extends JsonResource
{
    use HasLocalizedFields;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'ref_code'    => $this->ref_code,
            'slug'        => $this->slug,
            'title'       => $this->localized('title'),
            'title_ar'    => $this->title_ar,
            'title_en'    => $this->title_en,
            'purpose'     => $this->purpose,
            'status'      => $this->status,
            'price'       => $this->price,
            'currency'    => $this->currency,
            'rent_period' => $this->rent_period,
            'area_sqm'    => $this->area_sqm,
            'bedrooms'    => $this->bedrooms,
            'bathrooms'   => $this->bathrooms,
            'is_featured' => (bool) $this->is_featured,
            'is_hot_deal' => (bool) $this->is_hot_deal,
            'cover_image' => $this->resolveCoverImage(),
            'governorate' => $this->whenLoaded('governorate', function () {
                return [
                    'id'   => $this->governorate->id,
                    'name' => (new GovernorateResource($this->governorate))->localized('name'),
                    'slug' => $this->governorate->slug,
                ];
            }),
            'area' => $this->whenLoaded('area', function () {
                return [
                    'id'   => $this->area->id,
                    'name' => (new AreaResource($this->area))->localized('name'),
                    'slug' => $this->area->slug,
                ];
            }),
            'agency' => $this->whenLoaded('agency', function () {
                return [
                    'id'        => $this->agency->id,
                    'name'      => $this->agency->name,
                    'slug'      => $this->agency->slug,
                    'logo_path' => $this->agency->logo_path,
                ];
            }),
        ];
    }

    /**
     * Resolve the cover image from the loaded images relationship.
     *
     * Returns null if no images are loaded or none is marked as_cover.
     *
     * @return array<string, mixed>|null
     */
    private function resolveCoverImage(): ?array
    {
        if (!$this->relationLoaded('images')) {
            return null;
        }

        /** @var \App\Models\PropertyImage|null $cover */
        $cover = $this->images->firstWhere('is_cover', true)
            ?? $this->images->first();

        if ($cover === null) {
            return null;
        }

        return [
            'path'   => $cover->path,
            'alt_ar' => $cover->alt_ar,
            'alt_en' => $cover->alt_en,
        ];
    }
}
