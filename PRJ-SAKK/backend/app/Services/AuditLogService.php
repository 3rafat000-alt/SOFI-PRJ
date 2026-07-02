<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Transaction audit logging service for compliance and forensics.
 * Every financial transaction (wallet, card, transfer) creates an audit record.
 */
class AuditLogService
{
    private ?Request $request = null;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? app('request');
    }

    /**
     * Log a transaction action with full context.
     *
     * @param string $action - e.g., 'wallet.deposit', 'card.load', 'transfer.sent'
     * @param string $modelType - e.g., 'Wallet', 'VirtualCard', 'Transaction'
     * @param int|string $modelId - ID of the model affected
     * @param array $changes - Before/after data: ['before' => [...], 'after' => [...]]
     * @param string $status - 'completed', 'pending', 'failed'
     * @param array $metadata - Additional context
     */
    public function log(
        string $action,
        string $modelType,
        int|string $modelId,
        array $changes = [],
        string $status = 'completed',
        array $metadata = []
    ): AuditLog {
        $user = Auth::user();

        $oldValues = $changes['before'] ?? [];
        $newValues = $changes['after'] ?? $changes;

        $log = AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->request?->userAgent() ?? 'cli',
            'device_type' => $this->getDeviceType(),
            'metadata' => array_merge($metadata, ['status' => $status]),
        ]);

        return $log;
    }

    /**
     * Log a wallet transaction
     */
    public function logWalletTransaction(
        User $user,
        int $walletId,
        string $type, // 'deposit', 'withdraw', 'convert'
        float $amount,
        string $currency,
        array $metadata = []
    ): AuditLog {
        return $this->log(
            action: "wallet.{$type}",
            modelType: 'Wallet',
            modelId: $walletId,
            changes: [
                'after' => [
                    'amount' => $amount,
                    'currency' => $currency,
                    'type' => $type,
                ]
            ],
            status: 'completed',
            metadata: $metadata
        );
    }

    /**
     * Log a card transaction
     */
    public function logCardTransaction(
        User $user,
        int $cardId,
        string $operation, // 'load', 'unload', 'freeze', 'cancel'
        float $amount = 0,
        array $metadata = []
    ): AuditLog {
        return $this->log(
            action: "card.{$operation}",
            modelType: 'VirtualCard',
            modelId: $cardId,
            changes: [
                'after' => [
                    'amount' => $amount,
                    'operation' => $operation,
                ]
            ],
            status: 'completed',
            metadata: $metadata
        );
    }

    /**
     * Log a P2P transfer
     */
    public function logTransfer(
        User $sender,
        int $recipientId,
        float $amount,
        string $currency,
        array $metadata = []
    ): AuditLog {
        return $this->log(
            action: 'transfer.sent',
            modelType: 'Transaction',
            modelId: 0, // Will be updated later with transaction ID
            changes: [
                'after' => [
                    'sender_id' => $sender->id,
                    'recipient_id' => $recipientId,
                    'amount' => $amount,
                    'currency' => $currency,
                ]
            ],
            status: 'completed',
            metadata: $metadata
        );
    }

    /**
     * Log a failed transaction
     */
    public function logFailure(
        string $action,
        string $modelType,
        int|string $modelId,
        string $reason,
        array $context = []
    ): AuditLog {
        return $this->log(
            action: $action,
            modelType: $modelType,
            modelId: $modelId,
            changes: array_merge(['reason' => $reason], $context),
            status: 'failed'
        );
    }

    /**
     * Get audit trail for a user
     */
    public function getUserAudit(User $user, int $limit = 50, int $offset = 0): array
    {
        return AuditLog::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'model' => $log->model_type,
                'status' => $log->metadata['status'] ?? null,
                'timestamp' => $log->created_at->toIso8601String(),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ])
            ->toArray();
    }

    /**
     * Get audit trail for a specific model
     */
    public function getModelAudit(string $modelType, int $modelId): array
    {
        return AuditLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->latest()
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'action' => $log->action,
                'status' => $log->metadata['status'] ?? null,
                'timestamp' => $log->created_at->toIso8601String(),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ])
            ->toArray();
    }

    /**
     * Get failed transactions in a date range
     */
    public function getFailedTransactions(string $from, string $to): array
    {
        return AuditLog::whereJsonContains('metadata->status', 'failed')
            ->whereIn('action', ['wallet.deposit', 'wallet.withdraw', 'transfer.sent', 'card.load'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get()
            ->toArray();
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        if (!$this->request) {
            return 'unknown';
        }

        $ip = $this->request->ip();
        return $ip ?: 'unknown';
    }

    /**
     * Get or generate request ID for tracing
     */
    private function getRequestId(): string
    {
        if (!$this->request) {
            return 'cli-' . Str::random(8);
        }

        return $this->request->header('X-Request-ID') ?? 'req-' . Str::random(12);
    }

    /**
     * Detect device type from user agent
     */
    private function getDeviceType(): string
    {
        if (!$this->request) {
            return 'unknown';
        }

        $userAgent = strtolower($this->request->userAgent() ?? '');

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
