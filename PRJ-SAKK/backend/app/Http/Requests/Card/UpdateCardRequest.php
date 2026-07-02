<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nickname' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[\p{L}\p{N}\s\-_.\']*$/u',
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
            'daily_limit' => [
                'nullable',
                'numeric',
                'min:100',
                'max:10000',
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
            'monthly_limit' => [
                'nullable',
                'numeric',
                'min:1000',
                'max:100000',
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
            'online_enabled' => [
                'nullable',
                'boolean',
            ],
            'international_enabled' => [
                'nullable',
                'boolean',
            ],
            'contactless_enabled' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.max' => 'الاسم المستعار يجب أن لا يتجاوز 50 حرف.',
            'nickname.regex' => 'الاسم المستعار يحتوي على أحرف غير صحيحة.',
            'color.regex' => 'لون البطاقة يجب أن يكون بصيغة HEX (#RRGGBB).',
            'spending_limit.min' => 'حد الإنفاق يجب أن يكون 100 على الأقل.',
            'spending_limit.max' => 'حد الإنفاق يجب أن لا يتجاوز 50,000.',
            'daily_limit.min' => 'الحد اليومي يجب أن يكون 100 على الأقل.',
            'daily_limit.max' => 'الحد اليومي يجب أن لا يتجاوز 10,000.',
            'monthly_limit.min' => 'الحد الشهري يجب أن يكون 1,000 على الأقل.',
            'monthly_limit.max' => 'الحد الشهري يجب أن لا يتجاوز 100,000.',
            'online_enabled.boolean' => 'قيمة غير صحيحة للتحكم في المشتريات الإلكترونية.',
            'international_enabled.boolean' => 'قيمة غير صحيحة للتحكم في المشتريات الدولية.',
            'contactless_enabled.boolean' => 'قيمة غير صحيحة للتحكم في الدفع بدون التلامس.',
        ];
    }
}
