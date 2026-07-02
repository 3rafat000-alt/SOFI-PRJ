<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    public function index(): JsonResponse
    {
        $agents = Agent::active()->with('agency')->latest()->get();

        return response()->json(['data' => AgentResource::collection($agents)]);
    }

    public function show(Agent $agent): JsonResponse
    {
        return response()->json([
            'data' => new AgentResource($agent),
        ]);
    }
}
