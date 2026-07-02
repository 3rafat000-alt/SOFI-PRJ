<?php

declare(strict_types=1);

/**
 * Agent Configuration — Verification & Auto-Repair System.
 *
 * Agent signing keys can be generated with:
 *   php -r "echo sodium_bin2hex(sodium_crypto_sign_keypair());"
 *
 * Extract public key:
 *   $kp = sodium_crypto_sign_keypair();
 *   echo 'private: ' . sodium_bin2hex($kp) . PHP_EOL;
 *   echo 'public:  ' . sodium_bin2hex(sodium_crypto_sign_publickey($kp)) . PHP_EOL;
 *   echo 'seed:    ' . sodium_bin2hex(sodium_crypto_sign_secretkey($kp)) . PHP_EOL;
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-Repair Threshold
    |--------------------------------------------------------------------------
    |
    | Maximum financial impact (in SYP equivalent) before an agent must escalate
    | to a human admin instead of auto-repairing. Drift below this threshold is
    | signed and executed autonomously.
    |
    */
    'auto_repair_threshold' => env('AGENT_AUTO_REPAIR_THRESHOLD', 100_000), // SYP ~$7.70

    /*
    |--------------------------------------------------------------------------
    | Cryptographic Signing Keys
    |--------------------------------------------------------------------------
    |
    | Ed25519 keypair used to sign every repair action before execution.
    | Generate with: php -r "echo sodium_bin2hex(sodium_crypto_sign_keypair());"
    |
    | The private key MUST stay secret. The public key can be shared for
    | verification by downstream systems.
    |
    */
    'signing' => [
        'public_key' => env('AGENT_SIGNING_PUBLIC_KEY', ''),
        'private_key' => env('AGENT_SIGNING_PRIVATE_KEY', ''),
        'fingerprint' => env('AGENT_SIGNING_FINGERPRINT', 'agent-key-v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Endpoints
    |--------------------------------------------------------------------------
    |
    | HTTP endpoints that receive signed webhook notifications for agent events.
    | Each endpoint receives a JSON payload with the event type, action details,
    | and cryptographic signature for verification.
    |
    */
    'webhooks' => [
        'repair_actions' => [
            // 'admin-panel' => env('AGENT_WEBHOOK_REPAIR', 'https://admin.sakk.local/webhooks/agent-repair'),
        ],
        'run_summaries' => [
            // 'monitoring' => env('AGENT_WEBHOOK_SUMMARY', 'https://monitor.sakk.local/agent-summary'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | KYC Agent Configuration
    |--------------------------------------------------------------------------
    */
    'kyc' => [
        // Minimum confidence score (0.0 – 1.0) for auto-approval
        'min_confidence' => (float) env('AGENT_KYC_MIN_CONFIDENCE', 0.85),

        // Document types that always require human review
        'always_escalate_types' => explode(',', env('AGENT_KYC_ALWAYS_ESCALATE', '')),

        // Max documents to scan per run
        'batch_limit' => (int) env('AGENT_KYC_BATCH_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Financial Agent Configuration
    |--------------------------------------------------------------------------
    */
    'financial' => [
        // Max wallets to scan per run
        'batch_limit' => (int) env('AGENT_FINANCIAL_BATCH_LIMIT', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Concurrency Settings
    |--------------------------------------------------------------------------
    */
    'concurrency' => [
        // Cache lock TTL in seconds (prevents duplicate agent runs)
        'lock_ttl' => (int) env('AGENT_LOCK_TTL', 600),
    ],
];
