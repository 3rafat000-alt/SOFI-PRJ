@extends('layouts.portal')
@section('title','لوحة التحكم')
@section('content')

@unless($agent->is_verified)
    <div class="flash err">⏳ حسابك قيد المراجعة. <a href="{{ route('agent.documents') }}" style="text-decoration:underline">أكمل المستندات ›</a></div>
@endunless

<div class="grid g3">
    <div class="card"><div class="stat">{{ number_format((float)$agent->rating, 1) }}<small>التقييم</small></div></div>
    <div class="card"><div class="stat">{{ (int)$agent->reviews_count }}<small>عدد التقييمات</small></div></div>
    <div class="card"><div class="stat">{{ rtrim(rtrim(number_format((float)$agent->commission_rate,2),'0'),'.') }}%<small>نسبة العمولة</small></div></div>
</div>

<div class="card">
    <h3 class="sect">الخدمات والحالة</h3>
    <div class="grid g2" style="font-size:var(--fs-sm)">
        <div>الخدمات:
            @foreach((array)$agent->services as $s)<span class="pill muted">{{ $s === 'cash_in' ? 'إيداع' : 'سحب' }}</span> @endforeach
        </div>
        <div>الحالة: <span class="pill {{ $agent->is_verified ? 'ok' : 'warn' }}">{{ $agent->kyc_status_label }}</span></div>
        <div>المدينة: {{ $agent->city ?: '—' }}</div>
        <div>ساعات العمل: {{ $agent->working_hours ?: '—' }}</div>
    </div>
    <div class="row" style="margin-top:16px">
        <a href="{{ route('agent.profile') }}" class="btn ghost sm">ملف الوكيل</a>
        <a href="{{ route('agent.documents') }}" class="btn ghost sm">المستندات</a>
    </div>
</div>
@endsection
