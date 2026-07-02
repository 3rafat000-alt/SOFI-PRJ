<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyImage extends Model
{
    protected $fillable = [
        'property_id',
        'path',
        'alt_ar',
        'alt_en',
        'sort',
        'is_cover',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'sort'        => 'integer',
        'is_cover'    => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
