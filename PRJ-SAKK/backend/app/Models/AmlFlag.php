<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AmlFlag extends Model
{
    protected $fillable = [
        'uuid',
        'transaction_id',
        'user_id',
        'rule_name',
        'severity',
        'rule_context',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'flagged_at',
    ];

    protected function casts(): array
    {
        return [
            'rule_context' => 'array',
            'reviewed_at' => 'datetime',
            'flagged_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AmlFlag $flag): void {
            $flag->uuid ??= (string) Str::uuid();
            $flag->flagged_at ??= now();
        });
    }

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
