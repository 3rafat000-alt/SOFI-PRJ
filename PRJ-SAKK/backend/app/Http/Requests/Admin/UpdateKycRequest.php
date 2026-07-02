<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
            'level' => ['required', 'integer', 'min:0', 'max:10'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
            'extracted_data' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'حالة KYC مطلوبة.',
            'status.in' => 'الحالة يجب أن تكون "معلّق" أو "موافق" أو "مرفوض".',
            'level.required' => 'مستوى KYC مطلوب.',
            'level.integer' => 'المستوى يجب أن يكون رقماً صحيحاً.',
            'level.min' => 'المستوى يجب أن يكون 0 على الأقل.',
            'level.max' => 'المستوى يجب أن لا يتجاوز 10.',
            'rejection_reason.max' => 'سبب الرفض يجب أن لا يتجاوز 500 حرف.',
            'extracted_data.array' => 'البيانات المستخرجة يجب أن تكون مصفوفة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'الحالة',
            'level' => 'المستوى',
            'rejection_reason' => 'سبب الرفض',
            'extracted_data' => 'البيانات المستخرجة',
        ];
    }
}
