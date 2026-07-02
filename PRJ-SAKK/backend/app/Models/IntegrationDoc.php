<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationDoc extends Model
{
    protected $fillable = [
        'integration_id', 'title', 'title_ar', 'slug',
        'content', 'content_ar', 'section', 'order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }
}
