<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Merchant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'merchant_code',
        'type',
        'store_name',
        'owner_name',
        'email',
        'phone',
        'description',
        'logo',
        'address',
        'city',
        'governorate',
        'latitude',
        'longitude',
        'website_url',
        'has_api_access',
        // 🔒 SEC-003: api_key, api_secret, webhook_url, commission_rate, balance,
        // total_earned, verified_at, kyc_approved_at, kyc_rejection_reason
        // intentionally NOT fillable. is_active, is_verified, kyc_status,
        // kyc_submitted_at are set by the controller, never from user input.
        'is_active',
        'is_verified',
        'kyc_status',
        'kyc_submitted_at',
        'environment',
        'payment_methods',
        'settings',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude'       => 'float',
            'longitude'      => 'float',
            'has_api_access' => 'boolean',
            'commission_rate' => 'decimal:2',
            'balance'        => 'decimal:2',
            'total_earned'   => 'decimal:2',
            'payment_methods' => 'array',
            'settings'       => 'array',
            'is_active'      => 'boolean',
            'is_verified'    => 'boolean',
            'verified_at'    => 'datetime',
            'kyc_submitted_at' => 'datetime',
            'kyc_approved_at' => 'datetime',
            'approval_notified_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Merchant $merchant) {
            $merchant->uuid ??= (string) Str::uuid();
            if (empty($merchant->merchant_code)) {
                $merchant->merchant_code = 'MCH-' . strtoupper(Str::random(8));
            }
            if (empty($merchant->api_key)) {
                $merchant->api_key = Str::random(32);
            }
            if (empty($merchant->api_secret)) {
                $merchant->api_secret = Str::random(64);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function regenerateApiKey(): void
    {
        // 🔒 api_key/api_secret are guarded (SEC-003) — update() would silently
        // drop them. forceFill is the trusted path.
        $this->forceFill([
            'api_key' => Str::random(32),
            'api_secret' => Str::random(64),
        ])->save();
    }

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
        return $this->hasMany(MerchantDocument::class);
    }

    public function approvedDocuments(): HasMany
    {
        return $this->hasMany(MerchantDocument::class)->where('status', 'approved');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'physical' => 'متجر فعلي',
            'ecommerce' => 'متجر إلكتروني',
            'both' => 'فعلي + إلكتروني',
            default => $this->type,
        };
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
}
