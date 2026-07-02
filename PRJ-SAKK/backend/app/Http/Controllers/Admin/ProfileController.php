<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Admin Profile — standalone, fully decoupled from Settings.
 * Owns the signed-in admin's identity + password.
 */
class ProfileController extends Controller
{
    public function index()
    {
        return view('admin.profile.index');
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'nullable|required_with:password',
            'password' => 'nullable|min:8|confirmed',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'first_name.max' => 'الاسم الأول يجب أن لا يتجاوز 255 حرف.',
            'last_name.required' => 'اسم العائلة مطلوب.',
            'last_name.max' => 'اسم العائلة يجب أن لا يتجاوز 255 حرف.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
            'current_password.required_with' => 'كلمة المرور الحالية مطلوبة عند تغيير كلمة المرور.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ]);

        // Verify current password BEFORE mutating anything when rotating password.
        if ($request->filled('password') && !Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة'])
                ->withInput();
        }

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('admin.profile')
            ->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }
}
