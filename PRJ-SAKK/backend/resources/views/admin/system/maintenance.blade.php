@extends('layouts.admin')

@section('title', 'الصيانة وتنظيف البيانات')
@section('breadcrumbs')
<span class="breadcrumb-item">إعدادات النظام</span>
<span class="breadcrumb-item">الصيانة وتنظيف البيانات</span>
@endsection

@include('admin.system._shell')

{{-- Styles moved to base.css --}}

@php
    $totalRecords = collect($stats)->sum('count');
    $cleanableCount = collect($stats)->where('exists', true)->count();
    $protected = ['transactions','wallets','users','gold_transactions','gold_wallets','savings_goals','savings_transactions','virtual_cards','agents','merchants'];
@endphp

@section('content')
<div class="sys-head">
    <div class="sys-head-ico"><x-heroicon name="cleaning_services" /></div>
    <div class="sys-head-txt">
        <h1>الصيانة وتنظيف البيانات</h1>
        <p>تفريغ السجلات المؤقتة والقديمة لتسريع النظام وتوفير المساحة. تُحذف فقط الجداول المحدّدة أدناه ضمن شروط زمنية آمنة — السجلات المالية والهويّات محميّة ولا تُمسّ إطلاقاً.</p>
    </div>
    <div class="sys-head-actions">
        <div class="sys-head-stat"><div class="n">{{ number_format($totalRecords) }}</div><div class="l">سجل قابل للمراجعة</div></div>
    </div>
</div>

<form method="POST" action="{{ route('admin.system.maintenance.clean') }}"
      onsubmit="return confirm('تأكيد التنظيف؟ سيتم حذف السجلات المؤقتة المحددة فقط ضمن الشروط الزمنية الآمنة. لا يمكن التراجع.')">
    @csrf

    <div class="sys-group">
        <div class="sys-group-label">
            <x-heroicon name="auto_delete" />
            <span class="t">الجداول القابلة للتنظيف</span>
            <span class="line"></span>
            <button type="button" id="clean-select-all" class="btn btn-ghost btn-sm">تحديد الكل</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($stats as $table => $info)
            <label class="clean-tile {{ $info['exists'] ? '' : 'disabled' }}">
                <input type="checkbox" name="tables[]" value="{{ $table }}" {{ $info['exists'] ? '' : 'disabled' }}>
                <span class="box"><x-heroicon name="check" /></span>
                <span class="info">
                    <span class="name">{{ $info['label'] }}</span>
                    <span class="tbl">{{ $table }}</span>
                </span>
                <span class="cnt"><span class="n">{{ number_format($info['count']) }}</span><br><span class="u">سجل</span></span>
            </label>
            @endforeach
        </div>
    </div>

    <div class="save-bar">
        <span class="hint"><x-heroicon name="warning" class="icon-sm" style="vertical-align:middle;color:var(--warning)" /> يُحذف فقط ما يتجاوز عمره الحد الآمن (مثلاً الجلسات > 30 يوم).</span>
        <button type="submit" class="btn btn-danger"><x-heroicon name="delete_sweep" class="icon-sm" /> تنظيف الجداول المحدّدة</button>
    </div>
</form>

<div class="sys-note" style="--note:var(--success);margin-top:1.5rem">
    <x-heroicon name="verified_user" />
    <div class="bd">
        <strong>جداول محميّة بحاجز صلب — لا تظهر هنا ولا يمكن حذفها عبر هذه الأداة:</strong>
        <div class="prot-grid" style="margin-top:.6rem">
            @foreach($protected as $p)<span class="prot-chip"><x-heroicon name="lock" />{{ $p }}</span>@endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.clean-tile input[type=checkbox]').forEach(function (cb) {
        var tile = cb.closest('.clean-tile');
        cb.addEventListener('change', function () { tile.classList.toggle('on', cb.checked); });
    });
    var btn = document.getElementById('clean-select-all');
    if (btn) btn.addEventListener('click', function () {
        var boxes = Array.from(document.querySelectorAll('.clean-tile input:not(:disabled)'));
        var allOn = boxes.every(function (b) { return b.checked; });
        boxes.forEach(function (b) { b.checked = !allOn; b.dispatchEvent(new Event('change')); });
        btn.textContent = allOn ? 'تحديد الكل' : 'إلغاء التحديد';
    });
})();
</script>
@endpush
@endsection
