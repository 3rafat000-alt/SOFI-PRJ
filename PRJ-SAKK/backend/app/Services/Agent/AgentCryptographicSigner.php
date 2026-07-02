<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\AgentRepairAction;
use Illuminate\Support\Facades\Log;
use SodiumException;

/**
 * Cryptographic signing for agent repair actions.
 *
 * Uses libsodium (NaCl) Ed25519 signatures. Each repair action payload is signed
 * with the agent's private key before execution. The signature, along with a
 * key fingerprint, is stored alongside the action for immutable audit trail.
 *
 * The threshold check happens in Orchestrator BEFORE signing:
 *   - Drift < threshold   → sign + execute autonomously
 *   - Drift ≥ threshold   → escalate to human admin, never sign
 */
class AgentCryptographicSigner
{
    private string $publicKey;
    private string $privateKey;
    private string $keyFingerprint;

    /**
     * @param array{public_key:string,private_key:string,fingerprint:string} $keyConfig
     */
    public function __construct(?array $keyConfig = null)
    {
        $keyConfig ??= config('agents.signing');
        $this->publicKey = $keyConfig['public_key'] ?? '';
        $this->privateKey = $keyConfig['private_key'] ?? '';
        $this->keyFingerprint = $keyConfig['fingerprint'] ?? 'agent-key-v1';
    }

    /**
     * Sign a repair action payload for immutable audit trail.
     *
     * Returns the base64-encoded signature, or null if signing is unavailable.
     */
    public function sign(AgentRepairAction $action): ?string
    {
        if (empty($this->privateKey)) {
            Log::warning('AgentSigner: No private key configured — skipping signature.');
            return null;
        }

        $message = $this->buildSigningPayload($action);

        try {
            $signature = sodium_crypto_sign_detached($message, sodium_hex2bin($this->privateKey));

            $action->sign($this->keyFingerprint, sodium_bin2base64($signature, SODIUM_BASE64_VARIANT_ORIGINAL));

            Log::info('AgentSigner: Repair action signed', [
                'action_uuid' => $action->uuid,
                'fingerprint' => $this->keyFingerprint,
                'action_type' => $action->action_type,
            ]);

            return $action->signature;
        } catch (SodiumException $e) {
            Log::error('AgentSigner: Signing failed', [
                'action_uuid' => $action->uuid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify a repair action's signature against its payload.
     */
    public function verify(AgentRepairAction $action): bool
    {
        if (empty($action->signature) || empty($action->signing_key_fingerprint)) {
            return false;
        }

        if (empty($this->publicKey)) {
            Log::warning('AgentSigner: No public key configured — verification impossible.');
            return false;
        }

        $message = $this->buildSigningPayload($action);

        try {
            $binarySig = sodium_base642bin($action->signature, SODIUM_BASE64_VARIANT_ORIGINAL);

            return sodium_crypto_sign_verify_detached(
                $binarySig,
                $message,
                sodium_hex2bin($this->publicKey)
            );
        } catch (SodiumException $e) {
            Log::error('AgentSigner: Verification failed', [
                'action_uuid' => $action->uuid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build deterministic signing payload from action data.
     *
     * The payload includes: action_type, target morph class+id, reason,
     * target_snapshot hash, and status — ensuring the signature cannot be
     * replayed on a modified action.
     */
    private function buildSigningPayload(AgentRepairAction $action): string
    {
        $parts = [
            'action_type' => $action->action_type,
            'targetable_type' => $action->targetable_type,
            'targetable_id' => (string) ($action->targetable_id ?? '0'),
            'reason_hash' => sha1($action->reason ?? ''),
            'snapshot_hash' => sha1(json_encode($action->target_snapshot ?? [])),
            'status' => $action->status instanceof \App\Enums\RepairActionStatus
                ? $action->status->value
                : (string) $action->status,
            'created_at' => (string) $action->created_at?->timestamp,
        ];

        return json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
