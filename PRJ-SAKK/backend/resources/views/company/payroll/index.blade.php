@extends('layouts.portal')
@section('title','الرواتب')
@section('content')

<div class="card">
    <div class="row" style="justify-content:space-between">
        <h3 style="margin:0">دفعات الرواتب</h3>
        <a href="{{ route('company.payroll.create') }}" class="btn">+ دفعة جديدة</a>
    </div>
    <table style="margin-top:14px">
        <thead><tr><th>#</th><th>العنوان</th><th>العملة</th><th>الإجمالي</th><th>مدفوع/محجوز/فشل</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        @forelse($batches as $b)
            <tr>
                <td>{{ $b->id }}</td>
                <td>{{ $b->title ?: '—' }}</td>
                <td>{{ $b->currency }}</td>
                <td>{!! \App\Support\Money::format((float) $b->total_amount, $b->currency) !!}</td>
                <td>{{ $b->paid_count }} / {{ $b->held_count }} / {{ $b->failed_count }}</td>
                <td><span class="pill {{ $b->status === 'completed' ? 'ok' : ($b->status === 'failed' ? 'danger' : 'warn') }}">{{ $b->status_label }}</span></td>
                <td><a href="{{ route('company.payroll.show', $b) }}" class="btn ghost sm">عرض</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">لا دفعات بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:14px">{{ $batches->links() }}</div>
</div>
@endsection
