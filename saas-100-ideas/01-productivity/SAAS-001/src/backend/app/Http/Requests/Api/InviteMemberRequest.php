<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'in:admin,member,viewer'],
            'message' => ['nullable', 'string', 'max:1000'],
            'channel' => ['nullable', 'string', 'in:email,whatsapp'],
        ];
    }
}
