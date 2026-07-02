@extends('layouts.portal')
@section('title','الموظفون')
@section('content')

<div class="grid g2">
    <div class="card">
        <h3 style="margin-top:0">إضافة موظف</h3>
        <form method="POST" action="{{ route('company.employees.store') }}">
            @csrf
            <label>الاسم</label><input name="name" value="{{ old('name') }}">
            <label>رقم الهاتف *</label><input name="phone" value="{{ old('phone') }}" required placeholder="09xxxxxxxx">
            <div class="row">
                <div style="flex:1"><label>الراتب الافتراضي</label><input name="default_amount" type="number" step="0.01" value="{{ old('default_amount') }}"></div>
                <div style="width:120px"><label>العملة</label><select name="default_currency"><option>USD</option><option>SYP</option></select></div>
            </div>
            <div style="margin-top:16px"><button class="btn">إضافة</button></div>
        </form>
    </div>
    <div class="card">
        <h3 style="margin-top:0">استيراد من CSV</h3>
        <p class="muted">الأعمدة: الهاتف، الاسم، الراتب، العملة (USD/SYP).</p>
        <form method="POST" action="{{ route('company.employees.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".csv,.txt" required>
            <div style="margin-top:16px"><button class="btn ghost">استيراد</button></div>
        </form>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0">قائمة الموظفين ({{ $employees->total() }})</h3>
    <table>
        <thead><tr><th>الاسم</th><th>الهاتف</th><th>الراتب</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        @forelse($employees as $e)
            <tr>
                <td>{{ $e->name ?: '—' }}</td>
                <td dir="ltr" style="text-align:start">{{ $e->phone }}</td>
                <td>{!! $e->default_amount ? \App\Support\Money::format((float)$e->default_amount, $e->default_currency) : '—' !!}</td>
                <td>@if($e->employee_user_id)<span class="pill ok">مسجّل</span>@else<span class="pill muted">غير مسجّل</span>@endif</td>
                <td>
                    <form method="POST" action="{{ route('company.employees.destroy', $e) }}" onsubmit="return confirm('حذف الموظف؟')">
                        @csrf @method('DELETE')<button class="btn ghost sm">حذف</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">لا موظفين بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:14px">{{ $employees->links() }}</div>
</div>
@endsection
