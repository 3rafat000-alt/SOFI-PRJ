@extends('layouts.admin')

@section('title', 'إضافة شركة')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-sukk-icon">
            <x-heroicon name="arrow_forward" />
        </a>
        <div>
            <h1 class="text-2xl font-extrabold" style="color: var(--text-primary)">إضافة شركة جديدة</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted)">إضافة شركة إلى النظام</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.companies.store') }}" class="space-y-6">
        @csrf

        @if($errors->any())
        <div class="card">
            <div class="card-body">
                @foreach($errors->all() as $e)
                <p class="text-sm font-bold" style="color: var(--danger)">{{ $e }}</p>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="business" />
                    بيانات الشركة
                </h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="label label-required">اسم الشركة</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="input @error('name') input-error @enderror">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">الاسم القانوني</label>
                        <input type="text" name="legal_name" value="{{ old('legal_name') }}" class="input">
                    </div>
                    <div>
                        <label class="label">المالك</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name') }}" class="input">
                    </div>
                    <div>
                        <label class="label">البريد</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="input" dir="ltr">
                    </div>
                    <div>
                        <label class="label">الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="input" dir="ltr">
                    </div>
                    <div>
                        <label class="label">الرقم الضريبي</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}" class="input">
                    </div>
                    <div>
                        <label class="label">السجل التجاري</label>
                        <input type="text" name="commercial_register" value="{{ old('commercial_register') }}" class="input">
                    </div>
                    <div>
                        <label class="label">المدينة</label>
                        <input type="text" name="city" value="{{ old('city') }}" class="input">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="settings" />
                    إعدادات البوابة
                </h3>
            </div>
            <div class="card-body space-y-6">
                <div>
                    <label class="label">بريد مشغّل البوابة (اختياري)</label>
                    <input type="email" name="operator_email" value="{{ old('operator_email') }}"
                           placeholder="بريد مستخدم SAKK مسجّل ليدير البوابة"
                           class="input" dir="ltr">
                    <p class="hint">يربط حساب مستخدم موجود ليسجّل دخول بوابة الشركة على /company.</p>
                </div>
                <label class="flex items-center gap-3 text-sm font-bold" style="color: var(--text-primary)">
                    <input type="checkbox" name="payroll_enabled" value="1" @checked(old('payroll_enabled'))
                           class="w-4 h-4 rounded" style="border-color:var(--border);color:var(--primary);accent-color:var(--primary);">
                    تفعيل توزيع الرواتب فوراً (تجاوز مراجعة المستندات)
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">
                <x-heroicon name="save" />
                إنشاء الشركة
            </button>
        </div>
    </form>
</div>
@endsection
