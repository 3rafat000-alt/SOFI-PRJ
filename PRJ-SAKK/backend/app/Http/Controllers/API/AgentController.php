<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * List authorized cash agents. When `lat` & `lng` are supplied the result
     * is annotated with the distance (km) from the user and sorted nearest-first.
     * Supports optional `service` (cash_in|cash_out), `city`, `q` (search) and
     * `limit` filters.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'service' => ['nullable', 'string', 'in:cash_in,cash_out'],
            'city' => ['nullable', 'string', 'max:80'],
            'q' => ['nullable', 'string', 'max:80'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = Agent::query()->active();

        if (!empty($validated['service'])) {
            $query->service($validated['service']);
        }

        if (!empty($validated['city'])) {
            $query->where('city', $validated['city']);
        }

        if (!empty($validated['q'])) {
            $q = $validated['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('agent_code', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }

        $agents = $query->orderByDesc('is_featured')->orderByDesc('rating')->get();

        $hasLocation = isset($validated['lat'], $validated['lng']);
        $lat = (float) ($validated['lat'] ?? 0);
        $lng = (float) ($validated['lng'] ?? 0);

        $items = $agents->map(fn (Agent $a) => $this->transform(
            $a,
            $hasLocation ? $a->distanceKmFrom($lat, $lng) : null,
        ));

        if ($hasLocation) {
            $items = $items->sortBy('distance_km')->values();
        }

        if (!empty($validated['limit'])) {
            $items = $items->take((int) $validated['limit'])->values();
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $items->count(),
                'has_location' => $hasLocation,
            ],
        ]);
    }

    public function show(Agent $agent): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->transform($agent, null),
        ]);
    }

    private function transform(Agent $a, ?float $distanceKm): array
    {
        return [
            'id' => $a->id,
            'uuid' => $a->uuid,
            'name' => $a->name,
            'agent_code' => $a->agent_code,
            'owner_name' => $a->owner_name,
            'phone' => $a->phone,
            'avatar' => $a->avatar,
            'address' => $a->address,
            'city' => $a->city,
            'governorate' => $a->governorate,
            'latitude' => $a->latitude,
            'longitude' => $a->longitude,
            'services' => $a->services ?? [],
            'working_hours' => $a->working_hours,
            'commission_rate' => $a->commission_rate,
            'min_amount' => $a->min_amount,
            'max_amount' => $a->max_amount,
            'rating' => $a->rating,
            'reviews_count' => $a->reviews_count,
            'is_featured' => $a->is_featured,
            'is_verified' => $a->is_verified,
            'distance_km' => $distanceKm,
        ];
    }
}
