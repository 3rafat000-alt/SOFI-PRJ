<?php

declare(strict_types=1);

namespace App\Support\Concerns;

/**
 * Provides locale-aware field resolution for Eloquent models and API Resources.
 *
 * Assumes the model/resource has attributes following the _ar / _en suffix
 * convention defined in the frozen column spec (e.g. name_ar, name_en).
 *
 * Usage in a Resource:
 *   use App\Support\Concerns\HasLocalizedFields;
 *   ...
 *   'name' => $this->localized('name'),
 *
 * Usage in a Model (add trait + call on $this):
 *   public function getNameAttribute(): string
 *   {
 *       return $this->localized('name');
 *   }
 */
trait HasLocalizedFields
{
    /**
     * Return the value of the _ar or _en column for the active application locale.
     *
     * Falls back to the opposite locale if the preferred one is empty,
     * then to an empty string so callers never receive null from this method.
     *
     * @param  string  $field  Base field name without locale suffix (e.g. 'name', 'description').
     * @return string
     */
    public function localized(string $field): string
    {
        $locale = app()->getLocale();
        // Clamp to our two supported locales; anything unknown falls to 'ar'.
        $suffix = $locale === 'en' ? '_en' : '_ar';

        $preferred  = (string) ($this->{$field . $suffix} ?? '');
        if ($preferred !== '') {
            return $preferred;
        }

        // Fallback: the other locale.
        $altSuffix  = $suffix === '_ar' ? '_en' : '_ar';
        return (string) ($this->{$field . $altSuffix} ?? '');
    }
}
