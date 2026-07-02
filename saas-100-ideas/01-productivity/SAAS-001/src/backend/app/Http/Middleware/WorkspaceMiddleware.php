<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceMiddleware
{
    /**
     * Set current workspace from X-Workspace-Id header.
     *
     * Validates the user belongs to the workspace and sets it on
     * the request as an resolved model instance.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = $request->header('X-Workspace-Id');

        if (! $workspaceId) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $workspace = Workspace::find($workspaceId);

        if (! $workspace) {
            return new JsonResponse([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Workspace not found.',
                ],
            ], 404);
        }

        $isMember = $user->workspaces()->where('workspace_id', $workspace->id)->exists();

        if (! $isMember) {
            return new JsonResponse([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this workspace.',
                ],
            ], 403);
        }

        $request->merge(['current_workspace' => $workspace]);

        return $next($request);
    }
}
