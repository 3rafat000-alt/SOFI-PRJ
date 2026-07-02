<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Concerns\HasLocalizedFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for property_types rows.
 *
 * Returns both raw locale columns so clients can switch language client-side
 * without a refetch, and a computed 'name' field matching the active locale.
 */
class PropertyTypeResource extends JsonResource
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
            'id'             => $this->id,
            'slug'           => $this->slug,
            'name'           => $this->localized('name'),
            'name_ar'        => $this->name_ar,
            'name_en'        => $this->name_en,
            'icon'           => $this->icon,
            'sort'           => $this->sort,
            'listings_count' => (int) $this->listings_count,
        ];
    }
}
