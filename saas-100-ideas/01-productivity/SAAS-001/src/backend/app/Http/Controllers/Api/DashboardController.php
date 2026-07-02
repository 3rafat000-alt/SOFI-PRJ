<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    /**
     * Get aggregate dashboard stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'string', 'uuid', 'exists:workspaces,id'],
        ]);

        $workspace = Workspace::findOrFail($request->workspace_id);

        Gate::authorize('view', $workspace);

        $stats = $this->dashboardService->stats($workspace);

        return response()->json([
            'data' => $stats,
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get recent activity for a workspace.
     */
    public function activity(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'string', 'uuid', 'exists:workspaces,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $workspace = Workspace::findOrFail($request->workspace_id);

        Gate::authorize('view', $workspace);

        $limit = min((int) ($request->limit ?? 20), 100);
        $activities = $this->dashboardService->activity($workspace, $limit);

        return response()->json([
            'data' => $activities,
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
