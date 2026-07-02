<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'buy_rate',
        'sell_rate',
        'spread',
        'source',
        'is_active',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'buy_rate' => 'decimal:8',
            'sell_rate' => 'decimal:8',
            'spread' => 'decimal:4',
            'is_active' => 'boolean',
            'fetched_at' => 'datetime',
        ];
    }

    public function getBuyRate(): float
    {
        $halfSpread = $this->spread / 200;
        return round((float) $this->rate * (1 - $halfSpread), 6);
    }

    public function getSellRate(): float
    {
        $halfSpread = $this->spread / 200;
        return round((float) $this->rate * (1 + $halfSpread), 6);
    }
}
