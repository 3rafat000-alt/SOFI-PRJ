<?php

namespace App\Support;

/**
 * Canonical phone handling for payroll matching.
 *
 * Employees are added by phone; their salary must land in the right SAKK user
 * wallet regardless of how that user stored their own number. We canonicalize to
 * country-prefixed digits (e.g. 963982183111) — the SAME digit logic as
 * WhatsAppService::chatId — and, for DB lookups against the un-normalized
 * users.phone column, expand a canonical number back into every plausible stored
 * form so a `whereIn` reliably finds the user.
 */
class PhoneNormalizer
{
    /** Default country code (Syria) — keep aligned with services.whatsapp.default_country. */
    public static function countryCode(): string
    {
        return (string) config('services.whatsapp.default_country', '963');
    }

    /**
     * Canonical, country-prefixed digits. Returns '' when there are no digits.
     *
     *  +963912345678 / 00963912345678 / 0982183111 / 982183111 / 963966878924
     *  all collapse to <cc><national>, e.g. 963982183111.
     */
    public static function canonical(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return '';
        }

        $cc = self::countryCode();

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        if ($cc !== '' && !str_starts_with($digits, $cc)) {
            $digits = $cc . $digits;
        }

        return $digits;
    }

    /**
     * Every plausible stored representation of a canonical number, so a lookup
     * against the un-normalized users.phone column matches.
     *
     * @return list<string>
     */
    public static function variants(?string $phone): array
    {
        $canonical = self::canonical($phone);
        if ($canonical === '') {
            return [];
        }

        $cc = self::countryCode();
        $national = ($cc !== '' && str_starts_with($canonical, $cc))
            ? substr($canonical, strlen($cc))
            : $canonical;

        $forms = [
            $canonical,            // 963982183111
            '+' . $canonical,      // +963982183111
            '00' . $canonical,     // 00963982183111
            '0' . $national,       // 0982183111
            $national,             // 982183111
        ];

        return array_values(array_unique(array_filter($forms, fn ($f) => $f !== '')));
    }
}
