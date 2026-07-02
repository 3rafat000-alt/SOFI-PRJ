<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for agents rows.
 *
 * Security note: phone and whatsapp are included because they are the primary
 * contact mechanism for property inquiries. These are agent-public contacts,
 * not user PII. Do not expose bio_ar/bio_en or agency_id in the card variant;
 * use AgentDetailResource for that if needed in the future.
 *
 * verified is cast to bool so the client gets true/false, never null.
 */
class AgentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'display_name'  => $this->display_name,
            'phone'         => $this->phone,
            'whatsapp'      => $this->whatsapp,
            'photo_path'    => $this->photo_path,
            'rating'        => $this->rating,
            'reviews_count' => (int) $this->reviews_count,
            'verified'      => $this->verified_at !== null,
        ];
    }
}
