<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ref_code',
        'property_type_id',
        'agent_id',
        'agency_id',
        'governorate_id',
        'area_id',
        'purpose',
        'status',
        'title_ar',
        'title_en',
        'slug',
        'description_ar',
        'description_en',
        'price',
        'currency',
        'rent_period',
        'area_sqm',
        'bedrooms',
        'bathrooms',
        'parking',
        'floor',
        'year_built',
        'furnished',
        'address_ar',
        'address_en',
        'lat',
        'lng',
        'is_featured',
        'is_hot_deal',
        'views_count',
        'published_at',
    ];

    protected $casts = [
        'property_type_id' => 'integer',
        'agent_id'         => 'integer',
        'agency_id'        => 'integer',
        'governorate_id'   => 'integer',
        'area_id'          => 'integer',
        'price'            => 'decimal:2',
        'area_sqm'         => 'integer',
        'bedrooms'         => 'integer',
        'bathrooms'        => 'integer',
        'parking'          => 'integer',
        'floor'            => 'integer',
        'year_built'       => 'integer',
        'furnished'        => 'boolean',
        'is_featured'      => 'boolean',
        'is_hot_deal'      => 'boolean',
        'views_count'      => 'integer',
        'lat'              => 'decimal:7',
        'lng'              => 'decimal:7',
        'published_at'     => 'datetime',
    ];

    // ---------------------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------------------

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenity');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PropertyReview::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PropertyView::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    // ---------------------------------------------------------------------------
    // Query scopes
    // ---------------------------------------------------------------------------

    /**
     * Listings that are published: not draft and published_at in the past/now.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', 'draft')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', Carbon::now());
            });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeHotDeals(Builder $query): Builder
    {
        return $query->where('is_hot_deal', true);
    }
}
