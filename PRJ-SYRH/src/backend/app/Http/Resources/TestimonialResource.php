<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Concerns\HasLocalizedFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for testimonials rows.
 */
class TestimonialResource extends JsonResource
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
            'name'        => $this->name,
            'role'        => $this->localized('role'),
            'role_ar'     => $this->role_ar,
            'role_en'     => $this->role_en,
            'avatar_path' => $this->avatar_path,
            'rating'      => $this->rating,
            'quote'       => $this->localized('quote'),
            'quote_ar'    => $this->quote_ar,
            'quote_en'    => $this->quote_en,
            'is_featured' => (bool) $this->is_featured,
            'sort'        => $this->sort,
        ];
    }
}
