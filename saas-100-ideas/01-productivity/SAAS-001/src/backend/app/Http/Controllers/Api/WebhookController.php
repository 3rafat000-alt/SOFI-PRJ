<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWebhookRequest;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * List webhooks for a workspace.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'string', 'uuid', 'exists:workspaces,id'],
        ]);

        $workspace = \App\Models\Workspace::findOrFail($request->workspace_id);
        Gate::authorize('view', $workspace);

        $webhooks = Webhook::byWorkspace($request->workspace_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $webhooks->map(fn (Webhook $wh) => [
                'id' => $wh->id,
                'workspace_id' => $wh->workspace_id,
                'url' => $wh->url,
                'events' => $wh->events,
                'is_active' => $wh->is_active,
                'last_sent_at' => $wh->last_sent_at?->toIso8601String(),
                'last_status_code' => $wh->last_status_code,
                'created_at' => $wh->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a webhook.
     */
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $workspace = \App\Models\Workspace::findOrFail($request->workspace_id);
        Gate::authorize('update', $workspace);

        $secret = $request->secret ?? 'whsec_'.Str::random(32);

        $webhook = Webhook::create([
            'workspace_id' => $request->workspace_id,
            'url' => $request->url,
            'events' => $request->events,
            'secret' => $secret,
            'is_active' => true,
        ]);

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'workspace_id' => $webhook->workspace_id,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'secret' => $secret,
                'is_active' => $webhook->is_active,
                'last_sent_at' => null,
                'last_status_code' => null,
                'created_at' => $webhook->created_at?->toIso8601String(),
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Delete a webhook.
     */
    public function destroy(Request $request, Webhook $webhook): JsonResponse
    {
        $workspace = $webhook->workspace;
        Gate::authorize('update', $workspace);

        $webhook->delete();

        return response()->json([
            'data' => [
                'message' => 'Webhook deleted.',
            ],
            'meta' => [
                'request_id' => request()->header('X-Request-Id', Str::random(12)),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Test a webhook by sending a test payload.
     */
    public function test(Request $request, Webhook $webhook): JsonResponse
    {
        $workspace = $webhook->workspace;
        Gate::authorize('update', $workspace);

        $payload = [
            'event' => 'webhook.test',
            'workspace_id' => $webhook->workspace_id,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook from TaskSync Pro.',
            ],
        ];

        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhook->url, $payload);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $webhook->update([
                'last_sent_at' => now(),
                'last_status_code' => $response->status(),
                'last_response' => mb_substr($response->body(), 0, 1000),
            ]);

            return response()->json([
                'data' => [
                    'status_code' => $response->status(),
                    'response_body' => mb_substr($response->body(), 0, 500),
                    'duration_ms' => $durationMs,
                    'sent_at' => now()->toIso8601String(),
                ],
                'meta' => [
                    'request_id' => request()->header('X-Request-Id', Str::random(12)),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $webhook->update([
                'last_sent_at' => now(),
                'last_status_code' => 0,
                'last_response' => $e->getMessage(),
            ]);

            return response()->json([
                'data' => [
                    'status_code' => 0,
                    'response_body' => $e->getMessage(),
                    'duration_ms' => $durationMs,
                    'sent_at' => now()->toIso8601String(),
                ],
                'meta' => [
                    'request_id' => request()->header('X-Request-Id', Str::random(12)),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        }
    }
}
