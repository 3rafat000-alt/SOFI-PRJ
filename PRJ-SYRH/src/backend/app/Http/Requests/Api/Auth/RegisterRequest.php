<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
            'phone'    => 'nullable|string|max:20',
            'locale'   => 'nullable|in:ar,en',
        ];
    }
}
