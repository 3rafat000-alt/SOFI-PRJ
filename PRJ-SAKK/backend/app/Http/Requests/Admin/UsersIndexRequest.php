<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UsersIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,suspended,banned,pending'],
            'kyc_status' => ['nullable', 'string', 'in:pending,submitted,verified,rejected'],
            'is_admin' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:created_at,first_name,last_name,email,status,kyc_status'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
