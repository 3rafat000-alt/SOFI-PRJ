<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'currency' => $this->currency,
            'is_crypto' => $this->is_crypto,
            
            // Balances
            'balance' => (float) $this->balance,
            'available_balance' => (float) $this->available_balance,
            'pending_balance' => (float) $this->pending_balance,
            'formatted_balance' => $this->formatted_balance,
            
            // Limits
            'daily_limit' => (float) $this->daily_limit,
            'monthly_limit' => (float) $this->monthly_limit,
            'daily_spent' => (float) $this->daily_spent,
            'monthly_spent' => (float) $this->monthly_spent,
            'daily_remaining' => (float) ($this->daily_limit - $this->daily_spent),
            'monthly_remaining' => (float) ($this->monthly_limit - $this->monthly_spent),
            
            // Statistics
            'total_deposits' => (float) $this->total_deposits,
            'total_withdrawals' => (float) $this->total_withdrawals,
            'total_sent' => (float) $this->total_sent,
            'total_received' => (float) $this->total_received,
            'transaction_count' => $this->transaction_count,
            
            // Status
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'is_frozen' => $this->is_frozen,
            'frozen_reason' => $this->when($this->is_frozen, $this->frozen_reason),
            
            // Crypto specific
            'network' => $this->when($this->is_crypto, $this->network),
            'deposit_address' => $this->when($this->is_crypto, $this->deposit_address),
            
            // Relations
            'cards_count' => $this->whenCounted('cards'),
            
            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
