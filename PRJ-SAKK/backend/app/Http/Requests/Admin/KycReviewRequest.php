<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class KycReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:approved,rejected'],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'قرار المراجعة مطلوب.',
            'decision.in' => 'قرار المراجعة يجب أن يكون "موافق" أو "مرفوض".',
            'reason.required_if' => 'سبب الرفض مطلوب عند اختيار رفض الطلب.',
            'reason.max' => 'سبب الرفض يجب أن لا يتجاوز 500 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'decision' => 'قرار المراجعة',
            'reason' => 'سبب الرفض',
        ];
    }
}
