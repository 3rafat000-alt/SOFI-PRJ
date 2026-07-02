<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentRequest extends Model
{
    protected $fillable = [
        'user_id',
        'requestee_id',
        'currency',
        'amount',
        'note',
        // 🔒 SEC-003: status, payer_id, transaction_id, paid_at, responded_at
        // intentionally NOT fillable. Status is managed through the state
        // machine methods below. Payer and transaction are set by the
        // controller after successful transfer via markAsPaid().
        'merchant_name',
        'response_note',
        'callback_url',
        'callback_secret',
        'success_url',
        'cancel_url',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'paid_at' => 'datetime',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PaymentRequest $request) {
            $request->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isPending(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }
        return true;
    }

    // ==================== State machine ====================

    /**
     * Mark as paid after a successful transfer.
     */
    public function markAsPaid(int $payerId, int $transactionId): void
    {
        $this->forceFill([
            'status' => 'paid',
            'payer_id' => $payerId,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
            'responded_at' => now(),
        ])->save();
    }

    /**
     * Reject a directed request.
     */
    public function reject(?string $note = null): void
    {
        $this->forceFill([
            'status' => 'rejected',
            'response_note' => $note,
            'responded_at' => now(),
        ])->save();
    }

    /**
     * Cancel a pending request (owner only).
     */
    public function cancel(): void
    {
        $this->forceFill(['status' => 'cancelled'])->save();
    }

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestee_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
