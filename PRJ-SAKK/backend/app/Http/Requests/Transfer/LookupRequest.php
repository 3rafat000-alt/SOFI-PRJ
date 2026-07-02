<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class LookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'identifier' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?:[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}|[+\d\s\-()]{10,}|[@#]?[A-Za-z0-9_]+|SK\d{8})$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'أدخل وسم المستلم أو بريده أو رقم هاتفه.',
            'identifier.string' => 'معرف المستقبل يجب أن يكون نصاً.',
            'identifier.max' => 'معرف المستقبل طويل جداً.',
            'identifier.regex' => 'معرف المستقبل غير صحيح (بريد، هاتف، وسم، أو رقم حساب).',
        ];
    }
}
