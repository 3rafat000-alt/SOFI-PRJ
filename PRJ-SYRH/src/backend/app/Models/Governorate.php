<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    use HasFactory;
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'lat',
        'lng',
        'properties_count',
    ];

    protected $casts = [
        'lat'              => 'decimal:7',
        'lng'              => 'decimal:7',
        'properties_count' => 'integer',
    ];

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
