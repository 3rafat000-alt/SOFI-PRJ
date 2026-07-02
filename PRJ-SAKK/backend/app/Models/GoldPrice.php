<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPrice extends Model
{
    protected $fillable = [
        'karat',
        'buy_price',
        'sell_price',
        'spread',
        'source',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'buy_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'spread' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getKaratLabelAttribute(): string
    {
        return match ($this->karat) {
            '24' => 'عيار 24',
            '22' => 'عيار 22',
            '21' => 'عيار 21',
            '18' => 'عيار 18',
            default => "عيار {$this->karat}",
        };
    }

    public function getPurityAttribute(): float
    {
        return (int) $this->karat / 24 * 100;
    }
}
