<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'language' => 'nullable|string|in:ar,en',
            'timezone' => 'nullable|string|max:50',
            'referral_code' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'first_name.max' => 'الاسم الأول يجب أن لا يتجاوز 50 حرف.',
            'last_name.required' => 'اسم العائلة مطلوب.',
            'last_name.max' => 'اسم العائلة يجب أن لا يتجاوز 50 حرف.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'email.max' => 'البريد الإلكتروني يجب أن لا يتجاوز 255 حرف.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
            'phone.max' => 'رقم الهاتف يجب أن لا يتجاوز 20 رقم.',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقاً.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'language.in' => 'اللغة يجب أن تكون "ar" أو "en".',
            'timezone.max' => 'المنطقة الزمنية يجب أن لا يتجاوز 50 حرف.',
        ];
    }
}
