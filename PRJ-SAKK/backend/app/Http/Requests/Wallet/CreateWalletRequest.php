<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class CreateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'currency' => [
                'required',
                'string',
                'in:USD,SYP',
                'max:3',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'currency.required' => 'العملة مطلوبة.',
            'currency.in' => 'العملة يجب أن تكون USD أو SYP.',
            'currency.max' => 'العملة يجب أن تكون 3 أحرف كحد أقصى.',
        ];
    }
}
