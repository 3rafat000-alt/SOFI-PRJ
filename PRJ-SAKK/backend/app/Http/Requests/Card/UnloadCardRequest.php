<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class UnloadCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:10000',
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون 0.01 على الأقل.',
            'amount.max' => 'المبلغ يجب أن لا يتجاوز 10,000.',
            'amount.regex' => 'المبلغ يجب أن يكون صحيحاً.',
        ];
    }
}
