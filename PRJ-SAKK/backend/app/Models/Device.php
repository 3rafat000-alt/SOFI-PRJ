<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /// Security hold: a newly approved device cannot transact for this window.
    public const TRANSACTION_HOLD_HOURS = 48;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'device_type',
        'public_key',
        'is_trusted',
        'status',
        'approved_at',
        'transactions_locked_until',
        'last_ip',
        'last_active_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'last_used_at' => 'datetime',
            'approved_at' => 'datetime',
            'transactions_locked_until' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /// Whether the 48h post-approval hold is still active.
    public function isTransactionLocked(): bool
    {
        return $this->transactions_locked_until !== null
            && $this->transactions_locked_until->isFuture();
    }

    /// A device may move money only when approved AND past its security hold.
    public function canTransact(): bool
    {
        return $this->isApproved() && !$this->isTransactionLocked();
    }

    /// Approve the device and start the 48h transaction hold.
    public function approveWithHold(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'is_trusted' => true,
            'approved_at' => now(),
            'transactions_locked_until' => now()->addHours(self::TRANSACTION_HOLD_HOURS),
        ]);
    }

    /// Whether this pending device has exceeded the 72-hour auto-reject window.
    public function hasExceededAutoRejectWindow(): bool
    {
        return $this->isPending() && $this->created_at->isBefore(now()->subHours(72));
    }
}
// test comment 1782004702
