<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'agency_id', 'display_name', 'email', 'phone', 'whatsapp',
        'photo_path', 'license_no', 'rating', 'reviews_count',
        'bio_ar', 'bio_en', 'verified_at', 'status',
    ];

    protected $casts = [
        'rating'       => 'decimal:1',
        'reviews_count' => 'integer',
        'verified_at'  => 'datetime',
        'status'       => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function scopeActive($q)
    {
        $q->where('status', 'active');
    }
}
