<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PayrollBatch extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PARTIAL = 'partially_completed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'company_id',
        'currency',
        'status',
        'idempotency_key',
        'total_amount',
        'items_count',
        'created_by',
        'title',
        'notes',
        // 🔒 paid_count/held_count/failed_count/processed_at/completed_at are set
        // only by PayrollService during a run, never from request input.
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:8',
            'items_count' => 'integer',
            'paid_count' => 'integer',
            'held_count' => 'integer',
            'failed_count' => 'integer',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PayrollBatch $batch) {
            $batch->uuid ??= (string) Str::uuid();
            $batch->idempotency_key ??= (string) Str::uuid();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function isRunnable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_PARTIAL,
        ], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_PENDING => 'بانتظار التنفيذ',
            self::STATUS_PROCESSING => 'قيد التنفيذ',
            self::STATUS_PARTIAL => 'مكتملة جزئياً',
            self::STATUS_COMPLETED => 'مكتملة',
            self::STATUS_FAILED => 'فشلت',
            self::STATUS_CANCELLED => 'ملغاة',
            default => $this->status,
        };
    }
}
