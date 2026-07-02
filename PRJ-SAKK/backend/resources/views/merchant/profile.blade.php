@extends('layouts.portal')
@section('title','الملف التجاري')
@section('content')

<div class="card">
    <h3 class="sect">بيانات المتجر</h3>
    <div class="grid g2" style="font-size:var(--fs-sm)">
        <div>اسم المتجر: <b>{{ $merchant->store_name }}</b></div>
        <div>الرمز: <span class="mono">{{ $merchant->merchant_code }}</span></div>
        <div>النوع: {{ $merchant->typeLabel() }}</div>
        <div>المالك: {{ $merchant->owner_name ?: '—' }}</div>
        <div>الهاتف: <span dir="ltr">{{ $merchant->phone ?: '—' }}</span></div>
        <div>البريد: {{ $merchant->email ?: '—' }}</div>
    </div>
</div>

<div class="card">
    <h3 class="sect">مفاتيح الـAPI</h3>
    @if($merchant->is_verified || $merchant->has_api_access)
        <label>API Key</label>
        <div class="mono">{{ $merchant->api_key }}</div>
        <label>API Secret</label>
        <div class="mono">{{ \Illuminate\Support\Str::mask((string) $merchant->api_secret, '•', 6) }}</div>
        <form method="POST" action="{{ route('merchant.keys.regenerate') }}" style="margin-top:16px" onsubmit="return confirm('تجديد المفاتيح؟ ستتوقف المفاتيح الحالية فوراً.')">
            @csrf<button class="btn gold">تجديد المفاتيح</button>
        </form>
    @else
        <p class="muted">تُفعَّل مفاتيح الـAPI بعد اعتماد متجرك من الإدارة.</p>
    @endif
</div>
@endsection
