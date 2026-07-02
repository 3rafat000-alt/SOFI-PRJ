<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
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
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
            'pin' => [
                'required',
                'string',
                'size:6',
                'regex:/^\d+$/', // Numeric only
            ],
            'destination' => [
                'nullable',
                'string',
                'max:100',
                'in:bank,agent,account',
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
            'amount.regex' => 'المبلغ يجب أن يكون صحيحاً.',
            'pin.required' => 'رمز PIN مطلوب.',
            'pin.string' => 'رمز PIN يجب أن يكون نصاً.',
            'pin.size' => 'رمز PIN يجب أن يكون 6 أرقام.',
            'pin.regex' => 'رمز PIN يجب أن يحتوي على أرقام فقط.',
            'destination.in' => 'وجهة السحب غير صحيحة.',
        ];
    }
}
