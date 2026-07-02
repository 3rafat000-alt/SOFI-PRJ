<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for property_images rows.
 */
class PropertyImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'path'     => $this->path,
            'alt_ar'   => $this->alt_ar,
            'alt_en'   => $this->alt_en,
            'sort'     => $this->sort,
            'is_cover' => (bool) $this->is_cover,
        ];
    }
}
