<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'avatar_url' => $this->avatar_url,
            'locale'     => $this->locale,
            'status'     => $this->status,
            'roles'      => $this->whenLoaded('roles', fn() => $this->getRoleNames()),
            'agency'     => new AgencyResource($this->whenLoaded('agency')),
            'agent'      => new AgentResource($this->whenLoaded('agent')),
            'created_at' => $this->created_at,
        ];
    }
}
