<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Concerns\HasLocalizedFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for amenities rows.
 */
class AmenityResource extends JsonResource
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
            'id'      => $this->id,
            'name'    => $this->localized('name'),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'icon'    => $this->icon,
            'group'   => $this->group,
        ];
    }
}
