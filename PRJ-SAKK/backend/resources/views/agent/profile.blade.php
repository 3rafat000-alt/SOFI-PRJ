@extends('layouts.portal')
@section('title','ملف الوكيل')
@section('content')
<div class="card">
    <h3 class="sect">بيانات نقطة الخدمة</h3>
    <div class="grid g2" style="font-size:var(--fs-sm)">
        <div>الاسم: <b>{{ $agent->name }}</b></div>
        <div>الرمز: <span class="mono">{{ $agent->agent_code }}</span></div>
        <div>المالك: {{ $agent->owner_name ?: '—' }}</div>
        <div>الهاتف: <span dir="ltr">{{ $agent->phone ?: '—' }}</span></div>
        <div>المدينة: {{ $agent->city ?: '—' }}</div>
        <div>المحافظة: {{ $agent->governorate ?: '—' }}</div>
        <div style="grid-column:1/-1">العنوان: {{ $agent->address ?: '—' }}</div>
        <div>ساعات العمل: {{ $agent->working_hours ?: '—' }}</div>
        <div>الخدمات:
            @foreach((array)$agent->services as $s)<span class="pill muted">{{ $s === 'cash_in' ? 'إيداع' : 'سحب' }}</span> @endforeach
        </div>
    </div>
</div>
@endsection
