<?php

namespace App\Jobs;

use App\Enums\CardStatus;
use App\Models\VirtualCard;
use App\Services\AdminNotificationService;
use App\Services\StripeIssuingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStripeIssuingWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public string $eventType,
        public array $eventData,
    ) {}

    public function handle(
        StripeIssuingService $stripeService,
        AdminNotificationService $adminNotificationService,
    ): void {
        match ($this->eventType) {
            'issuing_authorization.created' => $this->handleAuthorizationCreated($this->eventData, $stripeService),
            'issuing_authorization.updated' => $this->handleAuthorizationUpdated($this->eventData, $stripeService),
            'issuing_transaction.created' => $this->handleTransactionCreated($this->eventData),
            'issuing_transaction.updated' => $this->handleTransactionUpdated($this->eventData),
            'issuing_card.created' => $this->handleCardCreated($this->eventData),
            'issuing_card.updated' => $this->handleCardUpdated($this->eventData),
            'issuing_cardholder.created' => $this->handleCardholderCreated($this->eventData),
            'issuing_cardholder.updated' => $this->handleCardholderUpdated($this->eventData),
            'issuing_dispute.created' => $this->handleDisputeCreated($this->eventData, $adminNotificationService),
            'issuing_dispute.updated' => $this->handleDisputeUpdated($this->eventData),
            default => null,
        };
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessStripeIssuingWebhook failed', [
            'event_type' => $this->eventType,
            'error' => $e->getMessage(),
        ]);
    }

    protected function handleAuthorizationCreated(array $authorization, StripeIssuingService $stripeService): void
    {
        Log::info('Authorization created', [
            'id' => $authorization['id'] ?? 'unknown',
            'status' => $authorization['status'] ?? 'pending',
            'amount' => $authorization['amount'] ?? 0,
        ]);

        if (($authorization['status'] ?? 'pending') === 'closed') {
            $stripeService->handleAuthorizationCapture($authorization);
        }
    }

    protected function handleAuthorizationUpdated(array $authorization, StripeIssuingService $stripeService): void
    {
        $status = $authorization['status'] ?? 'pending';

        Log::info('Authorization updated', [
            'id' => $authorization['id'] ?? 'unknown',
            'status' => $status,
        ]);

        if ($status === 'closed') {
            $stripeService->handleAuthorizationCapture($authorization);
        } elseif ($status === 'reversed') {
            $stripeService->handleAuthorizationReversal($authorization);
        }
    }

    protected function handleTransactionCreated(array $transaction): void
    {
        Log::info('Issuing transaction created', [
            'id' => $transaction['id'] ?? 'unknown',
            'amount' => $transaction['amount'] ?? 0,
            'type' => $transaction['type'] ?? 'unknown',
        ]);
    }

    protected function handleTransactionUpdated(array $transaction): void
    {
        Log::info('Issuing transaction updated', [
            'id' => $transaction['id'] ?? 'unknown',
        ]);
    }

    protected function handleCardCreated(array $card): void
    {
        Log::info('Issuing card created', [
            'id' => $card['id'] ?? 'unknown',
            'last4' => $card['last4'] ?? 'xxxx',
        ]);
    }

    protected function handleCardUpdated(array $card): void
    {
        Log::info('Issuing card updated', [
            'id' => $card['id'] ?? 'unknown',
            'status' => $card['status'] ?? 'unknown',
        ]);

        $localCard = VirtualCard::where('provider_card_id', $card['id'])
            ->where('provider', 'stripe')
            ->first();

        if ($localCard) {
            $stripeStatus = $card['status'] ?? 'active';
            $newStatus = match ($stripeStatus) {
                'active' => CardStatus::ACTIVE,
                'inactive' => CardStatus::FROZEN,
                'canceled' => CardStatus::CANCELLED,
                default => $localCard->status,
            };

            $localCard->forceFill([
                'status' => $newStatus,
                'is_active' => $stripeStatus === 'active',
            ])->save();
        }
    }

    protected function handleCardholderCreated(array $cardholder): void
    {
        Log::info('Cardholder created', [
            'id' => $cardholder['id'] ?? 'unknown',
        ]);
    }

    protected function handleCardholderUpdated(array $cardholder): void
    {
        Log::info('Cardholder updated', [
            'id' => $cardholder['id'] ?? 'unknown',
            'status' => $cardholder['status'] ?? 'unknown',
        ]);
    }

    protected function handleDisputeCreated(array $dispute, AdminNotificationService $service): void
    {
        Log::warning('Issuing dispute created', [
            'id' => $dispute['id'] ?? 'unknown',
            'amount' => $dispute['amount'] ?? 0,
            'reason' => $dispute['reason'] ?? 'unknown',
        ]);

        $service->cardDisputeCreated(
            (string) ($dispute['id'] ?? 'unknown'),
            (float) ($dispute['amount'] ?? 0),
            (string) ($dispute['reason'] ?? 'unknown'),
        );
    }

    protected function handleDisputeUpdated(array $dispute): void
    {
        Log::info('Issuing dispute updated', [
            'id' => $dispute['id'] ?? 'unknown',
            'status' => $dispute['status'] ?? 'unknown',
        ]);
    }
}
