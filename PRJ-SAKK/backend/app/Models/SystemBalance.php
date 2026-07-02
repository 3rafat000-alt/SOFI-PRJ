<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SystemBalance extends Model
{
    protected $table = 'system_balances';

    protected $fillable = [
        'currency',
        'balance',
        'previous_balance',
        'daily_change',
        'source',
        'snapped_at',
    ];

    protected function casts(): array
    {
        return [
            'balance'         => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'daily_change'    => 'decimal:2',
            'snapped_at'      => 'datetime',
        ];
    }

    // ── Snapshot helpers ──────────────────────────────────────────────

    /**
     * Take a live snapshot of aggregated wallet balances for USD + SYP.
     */
    public static function snapshot(): void
    {
        $now = now();

        foreach (['USD', 'SYP'] as $currency) {
            $current = (float) Wallet::where('currency', $currency)->sum('balance');
            $prev    = (float) static::where('currency', $currency)
                ->whereDate('snapped_at', today())
                ->value('balance') ?? 0;

            static::create([
                'currency'         => $currency,
                'balance'          => $current,
                'previous_balance' => $prev,
                'daily_change'     => $current - $prev,
                'source'           => 'aggregation',
                'snapped_at'       => $now,
            ]);
        }
    }

    /**
     * Get the latest balance snapshot for a currency.
     */
    public static function latestFor(string $currency): ?self
    {
        return static::where('currency', $currency)
            ->latest('snapped_at')
            ->first();
    }

    /**
     * Get today's opening balance (first snapshot).
     */
    public static function todayOpen(string $currency): float
    {
        return (float) static::where('currency', $currency)
            ->whereDate('snapped_at', today())
            ->oldest('snapped_at')
            ->value('balance') ?? 0;
    }

    /**
     * Record a manual balance injection (e.g., treasury top-up).
     */
    public static function recordInjection(string $currency, float $amount, string $note = ''): self
    {
        $prev = static::latestFor($currency)?->balance ?? 0;

        return static::create([
            'currency'         => $currency,
            'balance'          => $prev + $amount,
            'previous_balance' => $prev,
            'daily_change'     => $amount,
            'source'           => 'injection',
            'snapped_at'       => now(),
        ]);
    }
}
