<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'sort',
        'listings_count',
    ];

    protected $casts = [
        'sort'           => 'integer',
        'listings_count' => 'integer',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
