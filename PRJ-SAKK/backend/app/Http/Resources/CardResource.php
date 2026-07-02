<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            
            // Card Details (masked) — never expose the full PAN here
            'card_number_masked' => $this->card_number_masked,
            'last_four' => substr((string) $this->card_number_masked, -4),
            'expiry' => $this->expiry,
            'expiry_month' => $this->expiry_month,
            'expiry_year' => $this->expiry_year,
            'cardholder_name' => $this->cardholder_name,
            
            // Type & Brand
            'card_type' => [
                'value' => $this->card_type->value,
                'label' => $this->card_type->label(),
                'label_ar' => $this->card_type->labelAr(),
            ],
            'brand' => [
                'value' => $this->brand->value,
                'label' => $this->brand->label(),
                'logo' => $this->brand->logo(),
            ],
            'bin' => $this->bin,
            
            // Balance & Limits
            'balance' => (float) $this->balance,
            'formatted_balance' => $this->formatted_balance,
            'spending_limit' => (float) $this->spending_limit,
            'daily_limit' => (float) $this->daily_limit,
            'monthly_limit' => (float) $this->monthly_limit,
            'per_transaction_limit' => (float) $this->per_transaction_limit,
            
            // Spending
            'daily_spent' => (float) $this->daily_spent,
            'monthly_spent' => (float) $this->monthly_spent,
            'total_spent' => (float) $this->total_spent,
            'daily_remaining' => (float) ($this->daily_limit - $this->daily_spent),
            'monthly_remaining' => (float) ($this->monthly_limit - $this->monthly_spent),
            
            // Status
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'label_ar' => $this->status->labelAr(),
                'color' => $this->status->color(),
            ],
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
            'frozen_reason' => $this->when($this->status->value === 'frozen', $this->frozen_reason),
            
            // Settings
            'online_enabled' => $this->online_enabled,
            'international_enabled' => $this->international_enabled,
            'contactless_enabled' => $this->contactless_enabled,
            'atm_enabled' => $this->atm_enabled,
            
            // Digital Wallets
            'apple_pay_enabled' => $this->apple_pay_enabled,
            'google_pay_enabled' => $this->google_pay_enabled,
            'samsung_pay_enabled' => $this->samsung_pay_enabled,
            
            // Customization
            'nickname' => $this->nickname,
            'color' => $this->color,
            
            // Relations
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            
            // Timestamps
            'activated_at' => $this->activated_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
