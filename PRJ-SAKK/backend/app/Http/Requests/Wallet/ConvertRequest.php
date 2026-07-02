<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class ConvertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'from_currency' => [
                'required',
                'string',
                'in:USD,SYP',
                'max:3',
            ],
            'to_currency' => [
                'required',
                'string',
                'in:USD,SYP',
                'max:3',
                'different:from_currency',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100000',
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'from_currency.required' => 'عملة المصدر مطلوبة.',
            'from_currency.in' => 'عملة المصدر غير مدعومة.',
            'to_currency.required' => 'عملة الهدف مطلوبة.',
            'to_currency.in' => 'عملة الهدف غير مدعومة.',
            'to_currency.different' => 'يجب أن تختلف عملة المصدر عن الهدف.',
            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون 0.01 على الأقل.',
            'amount.max' => 'المبلغ يجب أن لا يتجاوز 100,000.',
        ];
    }
}
