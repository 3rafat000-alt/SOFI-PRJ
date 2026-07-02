<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'logo_url'      => $this->logo_url,
            'logo_path'     => $this->logo_path,
            'phone'         => $this->phone,
            'whatsapp'      => $this->whatsapp,
            'email'         => $this->email,
            'description'   => $request->header('Accept-Language', 'ar') === 'ar'
                ? $this->description_ar : $this->description_en,
            'address'       => $this->address,
            'license_no'    => $this->license_no,
            'status'        => $this->status,
            'verified_at'   => $this->verified_at,
            'agents_count'  => $this->whenCounted('agents'),
            'properties_count' => $this->whenCounted('properties'),
            'subscription'  => new AgencySubscriptionResource($this->whenLoaded('subscription')),
            'owner'         => new UserResource($this->whenLoaded('owner')),
            'created_at'    => $this->created_at,
        ];
    }
}
