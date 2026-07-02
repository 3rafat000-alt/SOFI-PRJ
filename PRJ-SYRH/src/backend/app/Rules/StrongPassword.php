<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class StrongPassword implements ValidationRule
{
    /**
     * Require: ≥8 chars, uppercase, lowercase, digit, special char.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;
        $errors = [];

        if (strlen($value) < 8) {
            $errors[] = __('validation.min.string', ['attribute' => $attribute, 'min' => 8]);
        }
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = __('validation.password.letters') . ' ' . __('(حرف كبير)');
        }
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = __('validation.password.letters') . ' ' . __('(حرف صغير)');
        }
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = __('validation.password.numbers');
        }
        if (!preg_match('/[!@#$%^&*()_\-+=<>?\/{}\[\]~`|\\\\:;\'",.<>]/', $value)) {
            $errors[] = __('validation.password.symbols');
        }

        if (!empty($errors)) {
            $fail(implode(' | ', $errors));
        }
    }
}
