@extends('install.layout')

@section('content')
    <h2>إنشاء حساب المدير</h2>
    <p class="sub">حساب المدير العام للوصول إلى لوحة الإدارة.</p>

    @if ($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('install.admin.create') }}">
        @csrf

        <div class="field">
            <label>الاسم</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="مدير النظام">
        </div>
        <div class="field">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="admin@example.com">
        </div>
        <div class="field">
            <label>كلمة المرور</label>
            <input type="password" name="password" required placeholder="8 أحرف على الأقل، حروف وأرقام">
        </div>
        <div class="field">
            <label>تأكيد كلمة المرور</label>
            <input type="password" name="password_confirmation" required>
        </div>

        <div class="row">
            <a href="{{ route('install.database') }}" class="btn ghost">رجوع</a>
            <button type="submit" class="btn">إنشاء المدير وإنهاء التنصيب</button>
        </div>
    </form>
@endsection
