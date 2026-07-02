<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
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
                'max:100000',
                'regex:/^\d+(\.\d{1,8})?$/', // 8 decimals max
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون 0.01 على الأقل.',
            'amount.max' => 'المبلغ يجب أن لا يتجاوز 100,000.',
            'amount.regex' => 'المبلغ يجب أن يكون صحيحاً (8 منازل عشرية كحد أقصى).',
        ];
    }
}
