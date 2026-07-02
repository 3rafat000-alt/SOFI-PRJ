@extends('layouts.portal')
@section('title','المحفظة')
@section('content')

<div class="grid g2">
    @foreach(['USD','SYP'] as $cur)
        @php $w = $wallets->get($cur); @endphp
        <div class="card">
            <div class="muted">رصيد {{ $cur }}</div>
            <div class="stat" style="margin-top:6px">{!! \App\Support\Money::format((float) optional($w)->balance, $cur) !!}</div>
            <div class="muted" style="margin-top:6px">المتاح: {!! \App\Support\Money::format((float) optional($w)->available_balance, $cur) !!}
                · المحجوز: {!! \App\Support\Money::format((float) optional($w)->pending_balance, $cur) !!}</div>
        </div>
    @endforeach
</div>

<div class="card">
    <h3 style="margin-top:0">شحن المحفظة من رصيدك الشخصي</h3>
    <form method="POST" action="{{ route('company.wallet.topup') }}">
        @csrf
        <div class="row">
            <div style="flex:1"><label>المبلغ</label><input name="amount" type="number" step="0.01" min="0.01" required></div>
            <div style="width:140px"><label>العملة</label><select name="currency"><option>USD</option><option>SYP</option></select></div>
            <div><button class="btn">شحن</button></div>
        </div>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0">آخر حركات المحفظة</h3>
    <table>
        <thead><tr><th>العملية</th><th>المبلغ</th><th>التاريخ</th></tr></thead>
        <tbody>
        @forelse($transactions as $t)
            <tr>
                <td>{{ $t->title }}</td>
                <td style="color:{{ (float)$t->amount < 0 ? '#b4232f' : '#1f8a4c' }}">{!! \App\Support\Money::format((float)$t->amount, $t->currency) !!}</td>
                <td class="muted">{{ $t->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">لا حركات بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
