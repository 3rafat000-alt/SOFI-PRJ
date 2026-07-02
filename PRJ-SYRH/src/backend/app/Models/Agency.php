<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agency extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'slug', 'logo_path', 'cover_path', 'phone', 'whatsapp', 'email',
        'description_ar', 'description_en', 'address', 'license_no',
        'commission_rate', 'verified_at', 'status', 'owner_id',
        'sakk_merchant_id', 'sakk_api_key_encrypted', 'sakk_verified', 'sakk_verified_at',
        'governorate_id', 'area_id', 'lat', 'lng',
    ];

    protected $appends = ['logo_url', 'cover_url'];

    protected $casts = [
        'verified_at'     => 'datetime',
        'sakk_verified'   => 'boolean',
        'sakk_verified_at'=> 'datetime',
        'status'          => 'string',
        'governorate_id'  => 'integer',
        'area_id'         => 'integer',
        'lat'             => 'float',
        'lng'             => 'float',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(AgencySubscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgencySubscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function quickReplies(): HasMany
    {
        return $this->hasMany(QuickReply::class);
    }

    public function scopeActive($q)
    {
        $q->where('status', 'active');
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription?->isActive() ?? false;
    }

    // -----------------------------------------------------------------------
    // Plan limit helpers
    // -----------------------------------------------------------------------

    /**
     * Max properties allowed by current plan (0 = unlimited)
     * Only counts if subscription is active (paid or valid trial).
     */
    public function maxPropertiesAllowed(): int
    {
        if (!$this->hasActiveSubscription()) {
            return 0;
        }

        return $this->subscription?->plan?->max_properties ?? 0;
    }

    /**
     * Max agents allowed by current plan (0 = unlimited)
     * Only counts if subscription is active (paid or valid trial).
     */
    public function maxAgentsAllowed(): int
    {
        if (!$this->hasActiveSubscription()) {
            return 0;
        }

        return $this->subscription?->plan?->max_agents ?? 0;
    }

    /**
     * Can this agency add another property?
     */
    public function canAddProperty(): bool
    {
        $max = $this->maxPropertiesAllowed();
        if ($max === 0) {
            return true; // unlimited
        }

        return $this->properties()->count() < $max;
    }

    /**
     * Can this agency add another agent?
     */
    public function canAddAgent(): bool
    {
        $max = $this->maxAgentsAllowed();
        if ($max === 0) {
            return true; // unlimited
        }

        return $this->agents()->count() < $max;
    }

    /**
     * Arabic error message for property limit
     */
    public function propertyLimitMessage(): string
    {
        $max = $this->maxPropertiesAllowed();
        $current = $this->properties()->count();

        return "باقتك تسمح بحد أقصى {$max} عقار. لديك حالياً {$current} عقار. قم بترقية باقتك لإضافة المزيد.";
    }

    /**
     * Arabic error message for agent limit
     */
    public function agentLimitMessage(): string
    {
        $max = $this->maxAgentsAllowed();
        $current = $this->agents()->count();

        return "باقتك تسمح بحد أقصى {$max} وكيل. لديك حالياً {$current} وكيل. قم بترقية باقتك لإضافة المزيد.";
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) return null;
        return str_starts_with($this->logo_path, 'http')
            ? $this->logo_path
            : url($this->logo_path);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_path) return null;
        return str_starts_with($this->cover_path, 'http')
            ? $this->cover_path
            : url($this->cover_path);
    }
}
