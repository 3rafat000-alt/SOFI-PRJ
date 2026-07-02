@extends('layouts.portal')
@section('title','دفعة رواتب جديدة')
@section('content')

@unless($company->canRunPayroll())
    <div class="flash err">⏳ لا يمكن تنفيذ الرواتب حتى تُعتمد شركتك. يمكنك تجهيز الدفعة الآن وتنفيذها بعد الاعتماد.</div>
@endunless

@if($employees->isEmpty())
    <div class="card"><p class="muted">لا موظفين بعد. <a href="{{ route('company.employees.index') }}" style="text-decoration:underline">أضف موظفين أولاً ›</a></p></div>
@else
<form method="POST" action="{{ route('company.payroll.store') }}">
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
    <div class="card">
        <div class="row">
            <div style="flex:1"><label>عنوان الدفعة</label><input name="title" placeholder="رواتب {{ now()->format('Y-m') }}"></div>
            <div style="width:160px"><label>العملة</label><select name="currency"><option>USD</option><option>SYP</option></select></div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0">اختر الموظفين والمبالغ</h3>
        <table>
            <thead><tr><th>تضمين</th><th>الاسم</th><th>الهاتف</th><th>المبلغ</th></tr></thead>
            <tbody>
            @foreach($employees as $e)
                <tr>
                    <td><input type="checkbox" name="sel[]" value="{{ $e->phone }}" checked style="width:auto"></td>
                    <td>{{ $e->name ?: '—' }}<input type="hidden" name="nm[{{ $e->phone }}]" value="{{ $e->name }}"></td>
                    <td dir="ltr" style="text-align:start">{{ $e->phone }}</td>
                    <td><input type="number" step="0.01" min="0" name="amt[{{ $e->phone }}]" value="{{ $e->default_amount ? rtrim(rtrim(number_format((float)$e->default_amount,2,'.',''),'0'),'.') : '' }}" style="width:140px"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:18px"><button class="btn">إنشاء الدفعة ›</button></div>
    </div>
</form>
@endif
@endsection
