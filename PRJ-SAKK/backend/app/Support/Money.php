<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Canonical money formatting for the whole backend (API responses,
 * notifications, admin alerts, payroll messages, pay links, etc.).
 *
 * Rules (TRUE SCALE — never divide/multiply SYP by 100):
 * - Symbol is ALWAYS on the LEFT (prefix), never a suffix.
 * - USD -> "$1,234.50" (2 decimals, thousands separators).
 * - SYP -> "ل.س 13,000" (no decimals, true scale, thousands separators).
 *   The whole "symbol + number" token is wrapped in a real Unicode LTR
 *   ISOLATE (LRI U+2066 ... PDI U+2069) — NOT the HTML entity `&lrm;`.
 *   An isolate forces the bidi algorithm to treat the enclosed run as one
 *   opaque left-to-right unit, so it can never be reordered/reversed when
 *   embedded inside RTL Arabic text. Being real Unicode chars (not HTML
 *   entities) they render safely through Blade's `{{ }}` escaping too.
 */
final class Money
{
    private const LRI = "\u{2066}";
    private const PDI = "\u{2069}";

    private function __construct()
    {
    }

    /**
     * Format an amount with its currency symbol on the left.
     * USD -> "$1,234.50" | SYP -> "ل.س 13,000" (true scale, no decimals),
     * both wrapped in an LRI/PDI Unicode isolate so the symbol+number stay
     * left-to-right and unreordered inside RTL page context.
     */
    public static function format(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);
        $symbol = self::symbol($currency);
        $number = self::number($amount, $currency);

        return self::LRI . $symbol . $number . self::PDI;
    }

    /**
     * Format the bare number with thousand separators (no symbol).
     * USD -> 2 decimals | SYP -> no decimals, true scale (no ÷100/×100).
     */
    public static function number(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);
        $decimals = $currency === 'USD' ? 2 : 0;

        return number_format($amount, $decimals);
    }

    /**
     * Currency symbol/label used as a left-hand prefix.
     */
    public static function symbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'USD' => '$',
            'SYP' => 'ل.س ',
            'SAR' => 'ر.س ',
            default => strtoupper($currency) . ' ',
        };
    }
}
