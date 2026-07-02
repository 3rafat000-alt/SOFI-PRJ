<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /** @var string[] Guarded fields rejected at validation layer. */
    private const GUARDED = ['kyc_level', 'kyc_verified_at', 'balance', 'email_verified_at'];

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', "unique:users,email,{$userId}"],
            'phone' => ['sometimes', 'string', "unique:users,phone,{$userId}"],
            'is_active' => ['sometimes', 'boolean'],
            'country_code' => ['sometimes', 'string', 'max:3'],
            'language' => ['sometimes', 'string', 'max:5'],
            'timezone' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /** @return array<string, mixed> */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        return array_diff_key($validated, array_flip(self::GUARDED));
    }

    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }
}
