<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reference' => $this->reference,
            
            // Type & Category
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
                'label_ar' => $this->type->labelAr(),
                'icon' => $this->type->icon(),
                'is_credit' => $this->type->isCredit(),
                'is_debit' => $this->type->isDebit(),
            ],
            'category' => [
                'value' => $this->category->value,
                'label' => $this->category->label(),
                'label_ar' => $this->category->labelAr(),
                'icon' => $this->category->icon(),
            ],
            
            // Amounts
            'currency' => $this->currency,
            'amount' => (float) $this->amount,
            'fee' => (float) $this->fee,
            'net_amount' => (float) $this->net_amount,
            'formatted_amount' => $this->formatted_amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            
            // Exchange (if applicable)
            'original_currency' => $this->when($this->original_currency, $this->original_currency),
            'original_amount' => $this->when($this->original_amount, (float) $this->original_amount),
            'exchange_rate' => $this->when($this->exchange_rate, (float) $this->exchange_rate),
            
            // Status
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'label_ar' => $this->status->labelAr(),
                'color' => $this->status->color(),
                'is_final' => $this->status->isFinal(),
            ],
            
            // Details
            'title' => $this->title,
            'description' => $this->description,
            
            // Crypto (if applicable)
            'tx_hash' => $this->when($this->tx_hash, $this->tx_hash),
            'network' => $this->when($this->network, $this->network),
            'confirmations' => $this->when($this->confirmations !== null, $this->confirmations),
            
            // Failure (if applicable)
            'failure_reason' => $this->when($this->status->value === 'failed', $this->failure_reason),
            
            // Relations
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'card' => $this->when($this->card_id, function () {
                return [
                    'id' => $this->card->id,
                    'card_number_masked' => $this->card->card_number_masked,
                    'brand' => $this->card->brand->value,
                ];
            }),
            'recipient' => $this->when($this->recipient_id, function () {
                return [
                    'id' => $this->recipient->id,
                    'full_name' => $this->recipient->full_name,
                    'email' => $this->recipient->email,
                    'avatar' => $this->recipient->avatar,
                ];
            }),
            
            // Timestamps
            'processed_at' => $this->processed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
