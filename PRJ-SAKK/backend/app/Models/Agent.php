<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'agent_code',
        'owner_name',
        'phone',
        'avatar',
        'address',
        'city',
        'governorate',
        'latitude',
        'longitude',
        'services',
        'working_hours',
        // 🔒 SEC-003: commission_rate, min_amount, max_amount, rating, reviews_count,
        // is_featured, kyc_approved_at, kyc_rejection_reason intentionally NOT fillable.
        // is_active, is_verified, kyc_status, kyc_submitted_at are set by controller,
        // never from user input — safe to keep fillable.
        'is_active',
        'is_verified',
        'kyc_status',
        'kyc_submitted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'services' => 'array',
            'commission_rate' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'rating' => 'decimal:1',
            'reviews_count' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_verified' => 'boolean',
            'kyc_submitted_at' => 'datetime',
            'kyc_approved_at' => 'datetime',
            'approval_notified_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Agent $agent) {
            $agent->uuid ??= (string) Str::uuid();
            if (empty($agent->agent_code)) {
                $agent->agent_code = 'AG-' . str_pad((string) random_int(1000, 999999), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeService(Builder $query, string $service): Builder
    {
        // JSON column contains the requested service ("cash_in" / "cash_out").
        return $query->whereJsonContains('services', $service);
    }

    /**
     * Great-circle distance in kilometres between this agent and a point,
     * using the Haversine formula (computed in PHP for DB portability).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(BankAccount::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AgentDocument::class);
    }

    public function approvedDocuments(): HasMany
    {
        return $this->hasMany(AgentDocument::class)->where('status', 'approved');
    }

    public function getKycStatusLabelAttribute(): string
    {
        return match ($this->kyc_status) {
            'pending' => 'قيد المراجعة',
            'documents_required' => 'مستندات ناقصة',
            'approved' => 'مفعل',
            'rejected' => 'مرفوض',
            'suspended' => 'موقوف',
            default => $this->kyc_status,
        };
    }

    public function getKycStatusColorAttribute(): string
    {
        return match ($this->kyc_status) {
            'pending', 'documents_required' => 'warning',
            'approved' => 'success',
            'rejected', 'suspended' => 'danger',
            default => 'secondary',
        };
    }

    public function distanceKmFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($this->latitude - $lat);
        $dLng = deg2rad($this->longitude - $lng);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat)) * cos(deg2rad($this->latitude)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
