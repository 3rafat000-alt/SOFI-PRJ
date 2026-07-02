<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateHistory extends Model
{
    use HasFactory;
    protected $table = 'exchange_rate_history';

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'buy_rate',
        'sell_rate',
        'source',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'buy_rate' => 'decimal:8',
            'sell_rate' => 'decimal:8',
            'recorded_at' => 'datetime',
        ];
    }
}
