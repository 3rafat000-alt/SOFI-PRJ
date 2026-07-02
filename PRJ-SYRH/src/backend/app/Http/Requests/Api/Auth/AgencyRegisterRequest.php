<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class AgencyRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Owner account
            'owner_name'     => 'required|string|max:255',
            'owner_email'    => 'required|email|max:255|unique:users,email',
            'owner_phone'    => 'required|string|max:20',
            'password'       => ['required', 'string', 'confirmed', new StrongPassword],

            // Agency info
            'agency_name'    => 'required|string|max:255',
            'license_no'     => 'nullable|string|max:50',
            'agency_email'   => 'nullable|email|max:255',
            'agency_phone'   => 'nullable|string|max:20',
            'whatsapp'       => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:500',
        ];
    }
}
