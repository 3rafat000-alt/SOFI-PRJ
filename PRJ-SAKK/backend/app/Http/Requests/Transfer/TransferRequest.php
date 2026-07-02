<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
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
                'regex:/^(?:[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}|[+\d\s\-()]{10,}|[@#]?[A-Za-z0-9_]+|SK\d{8})$/', // Email, phone, tag, or account number
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100000',
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
            'currency' => [
                'required',
                'string',
                'in:USD,SYP',
                'max:3',
            ],
            'note' => [
                'nullable',
                'string',
                'max:140',
                'regex:/^[\p{L}\p{N}\s\-_.\'،،]*$/u', // Allow letters, numbers, spaces, punctuation, Arabic comma
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'المستقبل مطلوب.',
            'identifier.string' => 'معرف المستقبل يجب أن يكون نصاً.',
            'identifier.max' => 'معرف المستقبل طويل جداً.',
            'identifier.regex' => 'معرف المستقبل غير صحيح (بريد، هاتف، وسم، أو رقم حساب).',
            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون 0.01 على الأقل.',
            'amount.max' => 'المبلغ يجب أن لا يتجاوز 100,000.',
            'amount.regex' => 'المبلغ يجب أن يكون صحيحاً.',
            'currency.required' => 'العملة مطلوبة.',
            'currency.in' => 'العملة يجب أن تكون USD أو SYP.',
            'note.max' => 'الملاحظة طويلة جداً (140 حرف كحد أقصى).',
            'note.regex' => 'الملاحظة تحتوي على أحرف غير صحيحة.',
        ];
    }
}
