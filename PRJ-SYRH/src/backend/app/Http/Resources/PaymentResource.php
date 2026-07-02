<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'agency_subscription_id' => $this->agency_subscription_id,
            'amount'                => (float) $this->amount,
            'currency'              => $this->currency,
            'payment_method'        => $this->payment_method,
            'gateway'               => $this->gateway,
            'transaction_id'        => $this->transaction_id,
            'status'                => $this->status,
            'paid_at'               => $this->paid_at,
            'created_at'            => $this->created_at,
        ];
    }
}
