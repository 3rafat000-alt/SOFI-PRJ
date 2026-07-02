<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for the site_stats row.
 *
 * site_stats is a single-row config table; @show always returns the first row.
 */
class StatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'happy_clients'      => (int) $this->happy_clients,
            'properties_listed'  => (int) $this->properties_listed,
            'agents_count'       => (int) $this->agents_count,
            'satisfaction_pct'   => (int) $this->satisfaction_pct,
        ];
    }
}
