<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user'        => new UserResource($this->whenLoaded('user')),
            'rating'      => $this->rating,
            'title'       => $this->title,
            'body'        => $this->body,
            'is_approved' => $this->is_approved,
            'created_at'  => $this->created_at,
        ];
    }
}
