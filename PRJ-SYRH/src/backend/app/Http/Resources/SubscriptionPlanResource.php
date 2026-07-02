<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('Accept-Language', 'ar');

        return [
            'id'             => $this->id,
            'name'           => $lang === 'ar' ? $this->name_ar : $this->name_en,
            'slug'           => $this->slug,
            'description'    => $lang === 'ar' ? $this->description_ar : $this->description_en,
            'price'          => (float) $this->price,
            'currency'       => $this->currency,
            'duration_days'  => $this->duration_days,
            'max_properties' => $this->max_properties,
            'max_agents'     => $this->max_agents,
            'is_featured'    => $this->is_featured,
            'features'       => $this->features,
            'is_active'      => $this->is_active,
        ];
    }
}
