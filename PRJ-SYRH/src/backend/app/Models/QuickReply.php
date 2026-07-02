<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickReply extends Model
{
    protected $fillable = [
        'agency_id',
        'property_id',
        'title',
        'content',
        'placeholders',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'placeholders' => 'array',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer',
        ];
    }

    public function agency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function property(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Substitute placeholders with actual values.
     */
    public function render(array $values = []): string
    {
        $text = $this->content;
        foreach ($values as $key => $val) {
            $text = str_replace('{' . $key . '}', (string) $val, $text);
        }
        // Strip any remaining unreplaced placeholders
        $text = preg_replace('/\{(\w+)\}/', '', $text);
        return $text;
    }

    /**
     * List available placeholder keys (without braces).
     */
    public function availablePlaceholders(): array
    {
        preg_match_all('/\{(\w+)\}/', $this->content, $matches);
        return array_unique($matches[1]);
    }
}
