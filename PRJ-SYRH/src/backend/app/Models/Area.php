<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;
    protected $fillable = [
        'governorate_id',
        'name_ar',
        'name_en',
        'slug',
        'lat',
        'lng',
        'properties_count',
    ];

    protected $casts = [
        'governorate_id'   => 'integer',
        'lat'              => 'decimal:7',
        'lng'              => 'decimal:7',
        'properties_count' => 'integer',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
