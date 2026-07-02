@extends('layouts.admin')

@section('title', 'قوالب الرسائل')
@section('breadcrumbs')
<span class="breadcrumb-item">إعدادات النظام</span>
<span class="breadcrumb-item">قوالب الرسائل</span>
@endsection

@include('admin.system._shell')

{{-- Styles moved to base.css --}}

@section('content')
<div class="sys-head">
    <div class="sys-head-ico"><x-heroicon name="sms" /></div>
    <div class="sys-head-txt">
        <h1>قوالب الرسائل</h1>
        <p>عدّل عنوان ونص كل إشعار يُرسَل للمستخدمين. انقر على متغيّر لإدراجه في النص — تُستبدل المتغيّرات تلقائياً بقيمتها الحقيقية عند الإرسال.</p>
    </div>
    <div class="sys-head-actions">
        <div class="sys-head-stat"><div class="n">{{ $templates->count() }}</div><div class="l">قالب</div></div>
    </div>
</div>

<div class="space-y-5">
@forelse($templates as $template)
    <form method="POST" action="{{ route('admin.system.messages.update', $template) }}" class="card tpl-card" data-tpl="{{ $template->id }}">
        @csrf @method('PUT')
        <div class="gw-head">
            <div class="gw-logo" style="--brand-soft:var(--accent-soft);--brand:var(--accent)"><x-heroicon name="mark_email_read" /></div>
            <div class="meta">
                <div class="nm">{{ $template->name }}</div>
                <div class="ds" style="direction:ltr;text-align:start">{{ $template->code }}</div>
            </div>
            <label class="switch" title="تفعيل القالب">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }}>
                <span class="switch-track"></span><span class="switch-thumb"></span>
            </label>
        </div>
        <div class="card-body">
            <div class="tpl-grid">
                {{-- editor --}}
                <div class="tpl-editor">
                    <div class="field">
                        <label class="label">العنوان</label>
                        <input type="text" name="subject_ar" class="input tpl-subj" value="{{ $template->subject_ar }}" placeholder="عنوان الإشعار">
                    </div>
                    <div class="field">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.375rem">
                            <label class="label" style="margin:0">نص الرسالة</label>
                            <span class="char-count"><span class="cc-n">{{ mb_strlen($template->body_ar ?? '') }}</span> / 2000</span>
                        </div>
                        <textarea name="body_ar" class="input tpl-body" rows="4" maxlength="2000" required>{{ $template->body_ar }}</textarea>
                    </div>

                    @if(!empty($template->variables))
                    <div class="field">
                        <p class="hint" style="margin-bottom:.5rem;font-weight:800">المتغيّرات المتاحة — انقر للإدراج:</p>
                        <div class="brand-row">
                            @foreach($template->variables as $var)
                            <button type="button" class="var-chip" data-var="%{{ $var }}%">
                                <x-heroicon name="add" />%{{ $var }}%
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- live preview --}}
                <div class="tpl-preview">
                    <div class="ph-label">معاينة حيّة</div>
                    <div class="ph-note">
                        <div class="ph-app"><span class="badge-app"><x-heroicon name="account_balance_wallet" /></span> صكّ</div>
                        <div class="ph-subj tpl-prev-subj">{{ $template->subject_ar ?: 'عنوان الإشعار' }}</div>
                        <div class="ph-body tpl-prev-body">{{ $template->body_ar }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><x-heroicon name="save" class="icon-sm" /> حفظ القالب</button>
        </div>
    </form>
@empty
    <div class="card"><div class="table-empty">
        <x-heroicon name="drafts" class="table-empty-icon" />
        لا توجد قوالب بعد. شغّل <code>php artisan db:seed --class=SystemConfigSeeder</code>.
    </div></div>
@endforelse
</div>

@push('scripts')
<script>
(function () {
    function highlightVars(text) {
        var esc = (text || '').replace(/[&<>]/g, function (c) { return { '&':'&amp;','<':'&lt;','>':'&gt;' }[c]; });
        return esc.replace(/%([a-zA-Z0-9_]+)%/g, '<span class="vv">%$1%</span>');
    }
    function setHighlighted(el, text) {
        el.innerHTML = highlightVars(text);
    }
    document.querySelectorAll('.tpl-card').forEach(function (card) {
        var body = card.querySelector('.tpl-body');
        var subj = card.querySelector('.tpl-subj');
        var pBody = card.querySelector('.tpl-prev-body');
        var pSubj = card.querySelector('.tpl-prev-subj');
        var cc = card.querySelector('.cc-n');

        function sync() {
            if (pBody) setHighlighted(pBody, body.value);
            if (pSubj) pSubj.textContent = subj.value || 'عنوان الإشعار';
            if (cc) cc.textContent = body.value.length;
        }
        body.addEventListener('input', sync);
        subj.addEventListener('input', sync);
        sync();

        card.querySelectorAll('.var-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var token = chip.getAttribute('data-var');
                var start = body.selectionStart, end = body.selectionEnd;
                body.value = body.value.slice(0, start) + token + body.value.slice(end);
                body.focus();
                body.selectionStart = body.selectionEnd = start + token.length;
                sync();
            });
        });
    });
})();
</script>
@endpush
@endsection
