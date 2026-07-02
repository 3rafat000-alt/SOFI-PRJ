<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the POST /api/v1/properties/{property}/inquiries payload.
 *
 * Security controls implemented here:
 *
 * 1. SQL injection — all data flows through Eloquent parameterized bindings;
 *    no raw SQL is used with inquiry input.
 * 2. Mass-assignment — the controller must use explicit $fillable fields on
 *    \App\Models\Inquiry and pass only validated() data; never $request->all().
 * 3. XSS — 'message' and 'name' are stored as plain text; the API Resource
 *    returns them JSON-encoded (inherently escaped). Blade views must use
 *    {{ }} not {!! !!} when displaying these fields.
 * 4. offer_amount — required_if:type,offer prevents clients from submitting
 *    an offer inquiry without an amount, and numeric|min:0 blocks negative
 *    amounts that could corrupt financial calculations.
 * 5. preferred_at — after:now prevents booking slots in the past.
 * 6. throttle:10,1 is enforced at the route level (10 requests / 1 minute
 *    per IP), not here, to keep the Form Request concern-clean.
 *
 * Phone validation accepts:
 *   +963XXXXXXXXX  (international Syrian format, 8–9 digits after country code)
 *   09XXXXXXXX     (local Syrian mobile, 10 digits total)
 */
class StoreInquiryRequest extends FormRequest
{
    /**
     * All inquiry submissions are open to the public (no auth required).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:120'],
            'phone'        => ['required', 'string', 'regex:/^(\+963[0-9]{8,9}|09[0-9]{8})$/'],
            'email'        => ['nullable', 'email:rfc,dns'],
            'message'      => ['required', 'string', 'max:2000'],
            'type'         => ['required', 'in:visit,callback,offer'],
            'preferred_at' => ['nullable', 'date', 'after:now'],
            'offer_amount' => ['nullable', 'numeric', 'min:0', 'required_if:type,offer'],
        ];
    }

    /**
     * Custom validation messages in Arabic (primary locale).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'         => 'الاسم مطلوب.',
            'name.string'           => 'الاسم يجب أن يكون نصاً.',
            'name.max'              => 'الاسم يجب ألا يتجاوز 120 حرفاً.',

            'phone.required'        => 'رقم الهاتف مطلوب.',
            'phone.string'          => 'رقم الهاتف يجب أن يكون نصاً.',
            'phone.regex'           => 'رقم الهاتف يجب أن يكون رقماً سورياً صالحاً (مثال: 09XXXXXXXX أو +963XXXXXXXXX).',

            'email.email'           => 'البريد الإلكتروني غير صالح.',

            'message.required'      => 'الرسالة مطلوبة.',
            'message.string'        => 'الرسالة يجب أن تكون نصاً.',
            'message.max'           => 'الرسالة يجب ألا تتجاوز 2000 حرف.',

            'type.required'         => 'نوع الطلب مطلوب.',
            'type.in'               => 'نوع الطلب يجب أن يكون: زيارة، أو معاودة الاتصال، أو عرض سعر.',

            'preferred_at.date'     => 'التاريخ المفضل غير صالح.',
            'preferred_at.after'    => 'التاريخ المفضل يجب أن يكون في المستقبل.',

            'offer_amount.numeric'  => 'قيمة العرض يجب أن تكون رقماً.',
            'offer_amount.min'      => 'قيمة العرض يجب أن تكون صفراً أو أكثر.',
            'offer_amount.required_if' => 'قيمة العرض مطلوبة عند اختيار نوع الطلب "عرض سعر".',
        ];
    }

    /**
     * Custom attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'         => 'الاسم',
            'phone'        => 'رقم الهاتف',
            'email'        => 'البريد الإلكتروني',
            'message'      => 'الرسالة',
            'type'         => 'نوع الطلب',
            'preferred_at' => 'التاريخ المفضل',
            'offer_amount' => 'قيمة العرض',
        ];
    }
}
