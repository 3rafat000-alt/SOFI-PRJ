<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\AgentRepairAction;
use App\Models\AgentRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Emit secure webhook notifications for agent repair actions.
 *
 * Every auto-repair action sends a signed payload to configured webhook URLs.
 * The webhook includes the action UUID, type, reason, financial impact, and
 * signature so downstream systems can verify authenticity.
 *
 * Webhook URLs are configured in config/agents.php under 'webhooks'.
 */
class AgentWebhookService
{
    private AgentCryptographicSigner $signer;

    public function __construct(AgentCryptographicSigner $signer)
    {
        $this->signer = $signer;
    }

    /**
     * Emit a repair-action webhook to all configured endpoints.
     *
     * Returns array of endpoint => bool (success/failure).
     */
    public function notifyRepairAction(AgentRepairAction $action): array
    {
        $endpoints = config('agents.webhooks.repair_actions', []);
        if (empty($endpoints)) {
            return [];
        }

        $payload = $this->buildPayload($action);
        $results = [];

        foreach ($endpoints as $name => $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $results[$name] = false;
                continue;
            }

            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Agent-Webhook' => 'sakk-verification-agent',
                        'X-Agent-Signature' => $action->signature ?? '',
                        'X-Agent-Fingerprint' => $action->signing_key_fingerprint ?? '',
                    ])
                    ->post($url, $payload);

                $results[$name] = $response->successful();

                if (!$response->successful()) {
                    Log::warning('AgentWebhook: Non-200 response', [
                        'endpoint' => $name,
                        'status' => $response->status(),
                        'action_uuid' => $action->uuid,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('AgentWebhook: HTTP call failed', [
                    'endpoint' => $name,
                    'error' => $e->getMessage(),
                    'action_uuid' => $action->uuid,
                ]);
                $results[$name] = false;
            }
        }

        return $results;
    }

    /**
     * Emit an agent-run summary webhook (after run completes).
     */
    public function notifyRunCompleted(AgentRun $run): array
    {
        $endpoints = config('agents.webhooks.run_summaries', []);
        if (empty($endpoints)) {
            return [];
        }

        $payload = [
            'event' => 'agent_run_completed',
            'uuid' => $run->uuid,
            'agent_type' => $run->agent_type,
            'status' => $run->status,
            'items_scanned' => $run->items_scanned,
            'anomalies_found' => $run->anomalies_found,
            'auto_repairs_triggered' => $run->auto_repairs_triggered,
            'escalations' => $run->escalations,
            'duration_ms' => $run->duration_ms,
            'threshold_breached' => $run->threshold_breached,
            'summary' => $run->summary,
            'completed_at' => $run->completed_at?->toIso8601String(),
        ];

        $results = [];
        foreach ($endpoints as $name => $url) {
            try {
                $results[$name] = Http::timeout(10)->post($url, $payload)->successful();
            } catch (\Throwable $e) {
                $results[$name] = false;
            }
        }

        return $results;
    }

    /**
     * Build the webhook payload for a repair action.
     */
    private function buildPayload(AgentRepairAction $action): array
    {
        return [
            'event' => 'agent_repair_action',
            'version' => '1.0',
            'uuid' => $action->uuid,
            'agent_run_uuid' => $action->agentRun?->uuid,
            'agent_type' => $action->agent_type,
            'action_type' => $action->action_type,
            'action_category' => $action->action_category,
            'target_type' => $action->targetable_type,
            'target_id' => $action->targetable_id,
            'reason' => $action->reason,
            'financial_impact' => (float) $action->financial_impact,
            'status' => $action->status,
            'signature' => $action->signature,
            'signing_key_fingerprint' => $action->signing_key_fingerprint,
            'escalated_to_human' => $action->escalated_to_human,
            'executed_at' => $action->executed_at?->toIso8601String(),
            'created_at' => $action->created_at?->toIso8601String(),
        ];
    }
}
