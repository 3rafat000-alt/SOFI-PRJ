<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'country_code' => $this->country_code,
            'language' => $this->language,
            'timezone' => $this->timezone,
            
            // KYC
            'kyc_status' => $this->kyc_status ? [
                'value' => $this->kyc_status->value,
                'label' => $this->kyc_status->label(),
                'label_ar' => $this->kyc_status->labelAr(),
                'color' => $this->kyc_status->color(),
            ] : null,
            'is_kyc_verified' => $this->is_kyc_verified,
            'kyc_level' => $this->kyc_level ?? 0,
            'kyc_verified_at' => $this->kyc_verified_at?->toIso8601String(),
            
            // Status
            'status' => $this->status ? [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ] : null,
            'is_active' => $this->is_active,
            
            // Security
            'has_pin' => !empty($this->pin_code),
            'two_factor_enabled' => $this->two_factor_enabled,
            'email_verified' => !is_null($this->email_verified_at),
            'phone_verified' => !is_null($this->phone_verified_at),
            
            // Referral
            'referral_code' => $this->referral_code,
            'referrals_count' => $this->whenCounted('referrals'),
            
            // Relations
            'wallets' => WalletResource::collection($this->whenLoaded('wallets')),
            'cards' => CardResource::collection($this->whenLoaded('cards')),
            
            // Timestamps
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
