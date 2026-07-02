<?php

namespace App\Models;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'company_id',
        'card_id',
        'recipient_id',
        'recipient_wallet_id',
        'type',
        'category',
        'currency',
        'amount',
        'fee',
        'net_amount',
        'balance_before',
        'balance_after',
        'original_currency',
        'original_amount',
        'exchange_rate',
        'reference',
        'status',
        'title',
        'description',
        'metadata',
        'external_reference',
        'provider',
        'provider_response',
        'tx_hash',
        'network',
        'confirmations',
        'failure_reason',
        'failure_details',
        'processed_at',
        'completed_at',
    ];

    // 🔒 SEC-002: أُزيل $guarded=[] — قائمة $fillable أعلاه هي المرجع المسموح به

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'fee' => 'decimal:8',
            'net_amount' => 'decimal:8',
            'balance_before' => 'decimal:8',
            'balance_after' => 'decimal:8',
            'original_amount' => 'decimal:8',
            'exchange_rate' => 'decimal:8',
            'metadata' => 'array',
            'provider_response' => 'array',
            'failure_details' => 'array',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'type' => TransactionType::class,
            'category' => TransactionCategory::class,
            'status' => TransactionStatus::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Transaction $transaction) {
            $transaction->uuid = Str::uuid();
            $transaction->reference ??= 'TXN-' . strtoupper(Str::random(12));
            $transaction->net_amount ??= $transaction->amount - ($transaction->fee ?? 0);

            // Set balance snapshots ONLY when the caller didn't provide them.
            // Callers that mutate the wallet before creating the record (e.g. the
            // exchange/convert flow) MUST pass explicit balance_before/balance_after
            // to avoid post-mutation snapshots being computed here.
            if ($transaction->wallet_id
                && ($transaction->balance_before === null || $transaction->balance_after === null)) {
                $wallet = Wallet::find($transaction->wallet_id);
                if ($wallet) {
                    $transaction->balance_before ??= $wallet->balance;
                    $transaction->balance_after ??= $wallet->balance + $transaction->amount;
                }
            }
        });
    }

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(VirtualCard::class, 'card_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function recipientWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'recipient_wallet_id');
    }

    // ==================== Status Methods ====================

    public function markAsProcessing(): bool
    {
        $this->status = TransactionStatus::PROCESSING;
        $this->processed_at = now();
        return $this->save();
    }

    public function markAsCompleted(): bool
    {
        $this->status = TransactionStatus::COMPLETED;
        $this->completed_at = now();
        return $this->save();
    }

    public function markAsFailed(string $reason, array $details = []): bool
    {
        $this->status = TransactionStatus::FAILED;
        $this->failure_reason = $reason;
        $this->failure_details = $details;
        return $this->save();
    }

    public function markAsCancelled(): bool
    {
        $this->status = TransactionStatus::CANCELLED;
        return $this->save();
    }

    public function reverse(): ?Transaction
    {
        if ($this->status !== TransactionStatus::COMPLETED) {
            return null;
        }

        // P1.25: an adjustment/reversal must never itself be reversed. Reversing
        // the credit produced by a prior reverse() would re-credit the wallet on
        // every call (infinite re-credit loop). Refuse to reverse a reversal.
        if ($this->type === TransactionType::ADJUSTMENT
            || $this->category === TransactionCategory::ADJUSTMENT) {
            return null;
        }

        return DB::transaction(function () {
            // Lock wallet row to prevent race conditions on balance correction
            $wallet = $this->wallet_id
                ? Wallet::where('id', $this->wallet_id)->lockForUpdate()->first()
                : null;

            if ($wallet) {
                $absAmount = abs($this->amount);
                if ($this->type->isCredit()) {
                    // Reversing a credit — debit the wallet to undo the add
                    if (! $wallet->debit($absAmount, "Reversal: {$this->reference}")) {
                        throw new \RuntimeException(
                            'Cannot reverse credit: insufficient balance or wallet frozen'
                        );
                    }
                } else {
                    // Reversing a debit — credit the wallet to restore the funds
                    if (! $wallet->credit($absAmount, "Reversal: {$this->reference}")) {
                        throw new \RuntimeException(
                            'Cannot reverse debit: wallet frozen'
                        );
                    }
                }
            }

            $this->status = TransactionStatus::REVERSED;
            $this->save();

            return self::create([
                'user_id' => $this->user_id,
                'wallet_id' => $this->wallet_id,
                'card_id' => $this->card_id,
                'type' => TransactionType::ADJUSTMENT,
                'category' => TransactionCategory::ADJUSTMENT,
                'currency' => $this->currency,
                'amount' => -$this->amount,
                'fee' => 0,
                'status' => TransactionStatus::COMPLETED,
                'title' => "Reversal: {$this->title}",
                'description' => "Reversal of transaction {$this->reference}",
                'metadata' => ['original_transaction_id' => $this->id],
            ]);
        });
    }

    // ==================== Scopes ====================

    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', TransactionStatus::PENDING);
    }

    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeOfType($query, TransactionType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Hide unfunded crypto-deposit placeholders (amount 0, still pending) created by
     * older builds when a user merely opened the deposit screen. They are not real
     * transactions and must never surface in user-facing history.
     */
    public function scopeVisibleToUser($query)
    {
        return $query->whereNot(function ($q) {
            $q->where('type', TransactionType::DEPOSIT)
                ->where('category', TransactionCategory::CRYPTO)
                ->where('status', TransactionStatus::PENDING)
                ->where('amount', '<=', 0);
        });
    }

    public function scopeForPeriod($query, string $period)
    {
        return match($period) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'year' => $query->whereYear('created_at', now()->year),
            default => $query,
        };
    }

    // ==================== Accessors ====================

    public function getIsDebitAttribute(): bool
    {
        return $this->amount < 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->amount > 0;
    }

    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->amount >= 0 ? '+' : '';
        return $sign . \App\Support\Money::format((float) abs($this->amount), $this->currency);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            TransactionStatus::COMPLETED => 'green',
            TransactionStatus::PENDING, TransactionStatus::PROCESSING => 'yellow',
            TransactionStatus::FAILED, TransactionStatus::CANCELLED => 'red',
            TransactionStatus::REVERSED, TransactionStatus::REFUNDED => 'blue',
        };
    }
}
