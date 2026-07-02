<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Concerns\HasLocalizedFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for areas rows.
 */
class AreaResource extends JsonResource
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
            'id'               => $this->id,
            'governorate_id'   => $this->governorate_id,
            'slug'             => $this->slug,
            'name'             => $this->localized('name'),
            'name_ar'          => $this->name_ar,
            'name_en'          => $this->name_en,
            'lat'              => $this->lat,
            'lng'              => $this->lng,
            'properties_count' => (int) $this->properties_count,
        ];
    }
}
