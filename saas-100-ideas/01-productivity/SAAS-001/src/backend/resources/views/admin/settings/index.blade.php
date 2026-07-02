@extends('admin.layout')
@section('title', 'الإعدادات')

@section('content')
    <div class="panel" style="max-width:640px">
        <div class="head"><h2>إعدادات النظام</h2></div>
        <form method="POST" action="{{ route('admin.settings.update') }}" style="padding:24px">
            @csrf @method('PUT')

            @if ($errors->any())
                <div class="tag red" style="display:block;padding:11px 14px;margin-bottom:16px">{{ $errors->first() }}</div>
            @endif

            <div class="field">
                <label>اسم التطبيق</label>
                <input type="text" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required>
            </div>
            <div class="field">
                <label>بريد الدعم</label>
                <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" required>
            </div>
            <div class="field">
                <label>الحد الأقصى لأعضاء الخطة المجانية</label>
                <input type="number" name="free_plan_max_members" min="1" max="100"
                       value="{{ old('free_plan_max_members', $settings['free_plan_max_members']) }}" required>
            </div>
            <div class="field" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="registration_open" value="1" id="reg" style="width:auto"
                       @checked(old('registration_open', $settings['registration_open']))>
                <label for="reg" style="margin:0">السماح بالتسجيل الجديد</label>
            </div>

            <button type="submit" class="btn primary" style="margin-top:8px">حفظ الإعدادات</button>
        </form>
    </div>
@endsection
