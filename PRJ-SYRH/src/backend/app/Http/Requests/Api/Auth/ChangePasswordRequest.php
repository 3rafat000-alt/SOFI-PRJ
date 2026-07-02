<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'         => 'required|string|current_password',
            'password'                 => ['required', 'string', 'confirmed', new StrongPassword],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => __('auth.current_password_incorrect'),
        ];
    }
}
