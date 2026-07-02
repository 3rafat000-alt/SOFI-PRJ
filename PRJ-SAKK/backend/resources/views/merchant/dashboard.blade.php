@extends('layouts.portal')
@section('title','لوحة التحكم')
@section('content')

@unless($merchant->is_verified)
    <div class="flash err">⏳ متجرك قيد المراجعة. <a href="{{ route('merchant.documents') }}" style="text-decoration:underline">أكمل المستندات ›</a></div>
@endunless

<div class="grid g3">
    <div class="card"><div class="stat money">{!! \App\Support\Money::format((float)$merchant->balance, 'USD') !!}<small>الرصيد</small></div></div>
    <div class="card"><div class="stat money">{!! \App\Support\Money::format((float)$merchant->total_earned, 'USD') !!}<small>إجمالي الأرباح</small></div></div>
    <div class="card"><div class="stat">{{ rtrim(rtrim(number_format((float)$merchant->commission_rate,2),'0'),'.') }}%<small>نسبة العمولة</small></div></div>
</div>

<div class="card">
    <h3 class="sect">معلومات المتجر</h3>
    <div class="grid g2" style="font-size:var(--fs-sm)">
        <div>النوع: <b>{{ $merchant->typeLabel() }}</b></div>
        <div>الهاتف: <span dir="ltr">{{ $merchant->phone ?: '—' }}</span></div>
        <div>المدينة: {{ $merchant->city ?: '—' }}</div>
        <div>الحالة: <span class="pill {{ $merchant->is_verified ? 'ok' : 'warn' }}">{{ $merchant->kyc_status_label }}</span></div>
    </div>
    <div class="row" style="margin-top:16px">
        <a href="{{ route('merchant.profile') }}" class="btn ghost sm">الملف التجاري + API</a>
        <a href="{{ route('merchant.documents') }}" class="btn ghost sm">المستندات</a>
    </div>
</div>
@endsection
