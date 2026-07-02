@extends('install.layout')

@section('content')
    <div style="text-align:center">
        <div class="success-icon">✓</div>
        <h2>اكتمل التنصيب بنجاح</h2>
        <p class="sub">التطبيق جاهز الآن. تم إنشاء حساب المدير وتأمين معالج التنصيب.</p>

        <div class="row" style="flex-direction:column">
            <a href="{{ route('admin.login') }}" class="btn">الدخول إلى لوحة الإدارة</a>
            <a href="{{ route('landing') }}" class="btn ghost">عرض الصفحة الرئيسية</a>
        </div>
    </div>
@endsection
