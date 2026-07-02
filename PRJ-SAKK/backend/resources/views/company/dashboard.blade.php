@extends('layouts.portal')
@section('title','لوحة التحكم')
@section('content')

@unless($company->payroll_enabled)
    <div class="flash err">⏳ توزيع الرواتب غير مفعّل بعد. بانتظار مراجعة المستندات والموافقة الإدارية.</div>
@endunless

<div class="grid g3">
    <div class="card"><div class="stat">{!! \App\Support\Money::format((float) optional($wallets->get('USD'))->available_balance, 'USD') !!}<small>رصيد USD المتاح</small></div></div>
    <div class="card"><div class="stat">{!! \App\Support\Money::format((float) optional($wallets->get('SYP'))->available_balance, 'SYP') !!}<small>رصيد SYP المتاح</small></div></div>
    <div class="card"><div class="stat">{{ $employeeCount }}<small>موظف نشط</small></div></div>
</div>

<div class="card">
    <div class="row" style="justify-content:space-between">
        <h3 style="margin:0">آخر دفعات الرواتب</h3>
        <a href="{{ route('company.payroll.create') }}" class="btn sm">+ دفعة جديدة</a>
    </div>
    <table style="margin-top:14px">
        <thead><tr><th>العنوان</th><th>العملة</th><th>الإجمالي</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        @forelse($recentBatches as $b)
            <tr>
                <td>{{ $b->title ?: 'دفعة #'.$b->id }}</td>
                <td>{{ $b->currency }}</td>
                <td>{!! \App\Support\Money::format((float) $b->total_amount, $b->currency) !!}</td>
                <td><span class="pill {{ $b->status === 'completed' ? 'ok' : ($b->status === 'failed' ? 'danger' : 'warn') }}">{{ $b->status_label }}</span></td>
                <td><a href="{{ route('company.payroll.show', $b) }}" class="btn ghost sm">عرض</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">لا دفعات بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
