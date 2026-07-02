@extends('layouts.installer')

@section('title', 'اكتمل التنصيب')
@section('subtitle', 'تم تثبيت صكك | SAKK Wallet بنجاح')

@php $currentStep = 5; @endphp

@section('content')
<div class="space-y-8 text-center flex flex-col items-center">
    <!-- Success Icon -->
    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-xl shadow-green-500/30">
        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <div>
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">اكتمل التنصيب بنجاح!</h2>
        <p class="text-gray-400 text-base mt-2">تم تثبيت صكك | SAKK Wallet. يمكنك الآن البدء باستخدام النظام.</p>
    </div>

    <!-- Summary -->
    <div class="w-full max-w-xl bg-gray-50 rounded-2xl border border-gray-100 p-6 text-right">
        <h3 class="text-lg font-bold text-gray-800 mb-4">ملخص التنصيب</h3>
        <div class="divide-y divide-gray-100 text-sm">
            <div class="flex items-center justify-between py-2.5">
                <span class="text-gray-500">اسم التطبيق</span>
                <span class="text-gray-900 font-semibold">{{ $settings['app_name'] ?? 'صكك | SAKK Wallet' }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-gray-500">بريد المشرف</span>
                <span class="text-gray-900 font-semibold ltr text-left" dir="ltr">{{ $admin['email'] ?? 'admin@sakk.com' }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-gray-500">قاعدة البيانات</span>
                <span class="text-gray-900 font-semibold">{{ ($database['driver'] ?? 'sqlite') === 'sqlite' ? 'SQLite' : (($database['driver'] ?? '') === 'mysql' ? 'MySQL' : 'PostgreSQL') }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-gray-500">العملة الافتراضية</span>
                <span class="text-gray-900 font-semibold">{{ $settings['default_currency'] ?? 'USD' }}</span>
            </div>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="w-full max-w-xl bg-amber-50 border border-amber-200 rounded-2xl p-6 text-right">
        <div class="flex items-start gap-4">
            <svg class="w-6 h-6 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="text-amber-700 font-semibold text-base mb-2">خطوات أمان مهمة بعد التنصيب</p>
                <ul class="text-amber-600 text-sm space-y-2">
                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                        احذف مسار <code class="bg-amber-100 px-2 py-0.5 rounded text-xs font-mono">/install</code> أو أضف له حماية
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                        اجعل <code class="bg-amber-100 px-2 py-0.5 rounded text-xs font-mono">APP_DEBUG=false</code> في بيئة الإنتاج
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                        هيّئ شهادة HTTPS صالحة للموقع
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                        أعد نسخ احتياطي منتظم لقاعدة البيانات
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Buttons -->
    <div class="flex gap-4">
        <a href="{{ route('admin.login') }}"
           class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-xl font-semibold text-base hover:opacity-90 transition-all hover:shadow-lg hover:shadow-primary/30 active:scale-[0.98]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            لوحة التحكم
        </a>
        <a href="/"
           class="inline-flex items-center gap-2 px-8 py-3 border-2 border-gray-200 text-gray-700 rounded-xl font-semibold text-base hover:bg-gray-50 transition-colors active:scale-[0.98]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            الصفحة الرئيسية
        </a>
    </div>
</div>
@endsection
