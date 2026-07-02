<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class CreateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => [
                'required',
                'integer',
                'exists:wallets,id',
                'min:1',
            ],
            'brand' => [
                'required',
                'string',
                'in:visa,mastercard',
                'max:20',
            ],
            'nickname' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[\p{L}\p{N}\s\-_.\']*$/u', // Allow letters, numbers, spaces, and basic punctuation
            ],
            'color' => [
                'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'spending_limit' => [
                'nullable',
                'numeric',
                'min:100',
                'max:50000',
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => 'المحفظة مطلوبة.',
            'wallet_id.exists' => 'المحفظة غير موجودة.',
            'wallet_id.integer' => 'معرف المحفظة يجب أن يكون رقماً.',
            'brand.required' => 'نوع البطاقة مطلوب.',
            'brand.in' => 'نوع البطاقة يجب أن يكون visa أو mastercard.',
            'nickname.max' => 'الاسم المستعار يجب أن لا يتجاوز 50 حرف.',
            'nickname.regex' => 'الاسم المستعار يحتوي على أحرف غير صحيحة.',
            'color.regex' => 'لون البطاقة يجب أن يكون بصيغة HEX (#RRGGBB).',
            'spending_limit.min' => 'حد الإنفاق يجب أن يكون 100 على الأقل.',
            'spending_limit.max' => 'حد الإنفاق يجب أن لا يتجاوز 50,000.',
            'spending_limit.numeric' => 'حد الإنفاق يجب أن يكون رقماً.',
        ];
    }
}
