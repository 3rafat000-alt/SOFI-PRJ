<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'agency_id'         => $this->agency_id,
            'property_id'       => $this->property_id,
            'agent_id'          => $this->agent_id,
            'type'              => $this->type,
            'price'             => (float) $this->price,
            'currency'          => $this->currency,
            'commission_rate'   => (float) $this->commission_rate,
            'commission_amount' => (float) $this->commission_amount,
            'deal_date'         => $this->deal_date?->format('Y-m-d'),
            'client_name'       => $this->client_name,
            'client_phone'      => $this->client_phone,
            'status'            => $this->status,
            'notes'             => $this->notes,
            'created_at'        => $this->created_at?->toISOString(),
            'property'          => $this->whenLoaded('property', fn() => [
                'id'    => $this->property->id,
                'slug'  => $this->property->slug,
                'title' => $this->property->{'title_' . app()->getLocale()},
            ]),
            'agent'             => $this->whenLoaded('agent', fn() => [
                'id'    => $this->agent->id,
                'name'  => $this->agent->display_name,
            ]),
        ];
    }
}
