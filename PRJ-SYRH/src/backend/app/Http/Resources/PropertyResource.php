<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Concerns\HasLocalizedFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full property detail resource.
 *
 * Extends the card fields with description, address, coordinates, images,
 * amenities, and the full agent sub-resource.
 *
 * Assumes eager loading of:
 *   - governorate
 *   - area
 *   - images (all, sorted by 'sort' ASC)
 *   - amenities
 *   - agent
 *
 * Security note: IDOR — caller (PropertyController@show) must apply the
 * Published scope or equivalent so draft/archived properties are never
 * served through this resource without authorization.
 */
class PropertyResource extends JsonResource
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
            // --- Card fields (duplicated for full detail) ---
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

            // --- Detail-only fields ---
            'description'     => $this->localized('description'),
            'description_ar'  => $this->description_ar,
            'description_en'  => $this->description_en,
            'parking'         => $this->parking,
            'floor'           => $this->floor,
            'year_built'      => $this->year_built,
            'furnished'       => (bool) $this->furnished,
            'address'         => $this->localized('address'),
            'address_ar'      => $this->address_ar,
            'address_en'      => $this->address_en,
            'lat'             => $this->lat,
            'lng'             => $this->lng,
            'views_count'     => (int) $this->views_count,
            'published_at'    => $this->published_at?->toIso8601String(),

            // --- Relations ---
            'governorate' => $this->whenLoaded('governorate', function () {
                return new GovernorateResource($this->governorate);
            }),
            'area' => $this->whenLoaded('area', function () {
                return new AreaResource($this->area);
            }),
            'images' => $this->whenLoaded('images', function () {
                return PropertyImageResource::collection($this->images);
            }),
            'amenities' => $this->whenLoaded('amenities', function () {
                return AmenityResource::collection($this->amenities);
            }),
            'agent' => $this->whenLoaded('agent', function () {
                return new AgentResource($this->agent);
            }),
        ];
    }
}
