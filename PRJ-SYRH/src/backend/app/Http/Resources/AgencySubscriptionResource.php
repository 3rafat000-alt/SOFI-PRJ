<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencySubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'plan'          => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'start_at'      => $this->start_at,
            'end_at'        => $this->end_at,
            'status'        => $this->status,
            'trial_ends_at' => $this->trial_ends_at,
            'is_active'     => $this->isActive(),
            'created_at'    => $this->created_at,
        ];
    }
}
