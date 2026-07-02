<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'role_ar',
        'role_en',
        'avatar_path',
        'rating',
        'quote_ar',
        'quote_en',
        'is_featured',
        'sort',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'is_featured' => 'boolean',
        'sort'        => 'integer',
    ];
}
