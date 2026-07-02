@extends('layouts.portal')
@section('title','دفعة #'.$batch->id)
@section('content')

<div class="card">
    <div class="row" style="justify-content:space-between">
        <div>
            <h3 style="margin:0">{{ $batch->title ?: 'دفعة #'.$batch->id }}</h3>
            <div class="muted">{{ $batch->currency }} · إجمالي {!! \App\Support\Money::format((float)$batch->total_amount, $batch->currency) !!} · {{ $batch->items_count }} موظف</div>
        </div>
        <span class="pill {{ $batch->status === 'completed' ? 'ok' : ($batch->status === 'failed' ? 'danger' : 'warn') }}">{{ $batch->status_label }}</span>
    </div>

    <div class="grid g3" style="margin-top:16px">
        <div><div class="stat" style="font-size:20px;color:#1f8a4c">{{ $batch->paid_count }}</div><small class="muted">مدفوع</small></div>
        <div><div class="stat" style="font-size:20px;color:#b06f00">{{ $batch->held_count }}</div><small class="muted">محجوز (بانتظار التسجيل)</small></div>
        <div><div class="stat" style="font-size:20px;color:#b4232f">{{ $batch->failed_count }}</div><small class="muted">فشل</small></div>
    </div>

    @if($batch->isRunnable())
        <form method="POST" action="{{ route('company.payroll.run', $batch) }}" style="margin-top:18px" onsubmit="return confirm('تنفيذ الدفعة الآن؟ سيتم خصم المبالغ من محفظة الشركة.')">
            @csrf
            <button class="btn">▶ تنفيذ الدفعة الآن</button>
            @if($batch->status === 'partially_completed')<span class="muted" style="margin-inline-start:10px">سيُعاد فقط تنفيذ العناصر غير المدفوعة.</span>@endif
        </form>
    @endif
</div>

<div class="card">
    <h3 style="margin-top:0">الموظفون</h3>
    <table>
        <thead><tr><th>الاسم</th><th>الهاتف</th><th>المبلغ</th><th>الحالة</th><th>ملاحظة</th></tr></thead>
        <tbody>
        @foreach($batch->items as $it)
            <tr>
                <td>{{ $it->employee_name ?: '—' }}</td>
                <td dir="ltr" style="text-align:start">{{ $it->employee_phone }}</td>
                <td>{!! \App\Support\Money::format((float)$it->amount, $it->currency) !!}</td>
                <td>
                    @php $map = ['paid'=>['ok','مدفوع'],'held'=>['warn','محجوز'],'failed'=>['danger','فشل'],'pending'=>['muted','بانتظار']]; $m = $map[$it->status] ?? ['muted',$it->status]; @endphp
                    <span class="pill {{ $m[0] }}">{{ $m[1] }}</span>
                </td>
                <td class="muted">{{ $it->failure_reason ?: ($it->status==='held' ? 'بانتظار تسجيل الموظف' : '') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
