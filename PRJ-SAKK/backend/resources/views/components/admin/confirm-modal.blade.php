{{--
  Component: <x-admin.confirm-modal>
  Destructive-action confirmation dialog. RTL. No external CDN. Inline SVG icons.

  Props:
    $id        (string)  — unique modal id. Default: 'confirm-modal'.
    $title     (string)  — dialog heading. Default: 'تأكيد الإجراء'.
    $message   (string)  — body text. Default: 'هل أنت متأكد من تنفيذ هذا الإجراء؟ لا يمكن التراجع.'.
    $formId    (string)  — id of a <form> to submit on confirm. Optional.
    $okLabel   (string)  — confirm button label. Default: 'تأكيد'.
    $okVariant (string)  — btn variant class suffix: danger|primary|warning|success. Default: 'danger'.
    $cancelLabel (string) — cancel button label. Default: 'إلغاء'.
    $iconType  (string)  — icon style: warning|danger|info|success. Default: 'warning'.

  Usage — form-submit pattern:
    <x-admin.confirm-modal
        id="del-modal"
        title="حذف المستخدم"
        message="سيُحذف الحساب نهائياً ولا يمكن التراجع عن هذا الإجراء."
        form-id="del-form"
        ok-label="حذف نهائياً" />
    <form id="del-form" method="POST" action="{{ route('admin.users.destroy', $user) }}">
        @csrf @method('DELETE')
    </form>
    {{-- Trigger button: --}}
    <button type="button"
            onclick="document.getElementById('del-modal').style.display='flex'"
            class="btn btn-danger btn-sm">حذف</button>

  Usage — JS callback pattern (no formId needed):
    <x-admin.confirm-modal id="arc-modal" title="أرشفة السجل"
        message="سيُنقل السجل إلى الأرشيف." ok-variant="warning" ok-label="أرشفة" />
    <button type="button"
            onclick="document.getElementById('arc-modal').style.display='flex';
                     document.getElementById('arc-modal')._onConfirm=function(){ doArchive(); }">
        أرشفة
    </button>

  Data-attribute auto-confirm shortcut (no Blade needed for simple cases):
    <button type="submit" form="del-form"
            data-confirm="حذف|سيُحذف الحساب نهائياً. هل أنت متأكد؟">حذف</button>
    (handled globally by admin.js sakkConfirm / initAutoConfirm)
--}}
@props([
    'id'           => 'confirm-modal',
    'title'        => 'تأكيد الإجراء',
    'message'      => 'هل أنت متأكد من تنفيذ هذا الإجراء؟ لا يمكن التراجع.',
    'formId'       => null,
    'okLabel'      => 'تأكيد',
    'okVariant'    => 'danger',
    'cancelLabel'  => 'إلغاء',
    'iconType'     => 'warning',
])

@php
/* Icon colour tokens keyed by type */
$iconMeta = [
    'warning' => ['bg' => 'var(--warning-light)', 'color' => 'var(--warning-text)'],
    'danger'  => ['bg' => 'var(--danger-light)',  'color' => 'var(--danger-text)'],
    'info'    => ['bg' => 'var(--info-light)',    'color' => 'var(--info-text)'],
    'success' => ['bg' => 'var(--success-light)', 'color' => 'var(--success-text)'],
];
$icon = $iconMeta[$iconType] ?? $iconMeta['warning'];
@endphp

{{-- Backdrop — flex-centered, backdrop-blur, z-200 (from .modal-backdrop) --}}
<div
    id="{{ $id }}"
    class="modal-backdrop"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    aria-describedby="{{ $id }}-desc"
    dir="rtl"
    style="display:none"
    onclick="if(event.target===this) window._sakkCloseModal('{{ $id }}')"
    onkeydown="if(event.key==='Escape') window._sakkCloseModal('{{ $id }}')"
    tabindex="-1"
>
    <div class="modal-box confirm-modal-box" role="document">

        {{-- Header --}}
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:12px;min-width:0">
                {{-- Warning/status icon — inline SVG, no CDN --}}
                <span
                    aria-hidden="true"
                    style="width:40px;height:40px;border-radius:12px;
                           background:{{ $icon['bg'] }};color:{{ $icon['color'] }};
                           display:flex;align-items:center;justify-content:center;
                           flex-shrink:0"
                >
                    @if($iconType === 'danger' || $iconType === 'warning')
                        {{-- Triangle warning --}}
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                        </svg>
                    @elseif($iconType === 'info')
                        {{-- Info circle --}}
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                    @else
                        {{-- Check circle --}}
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    @endif
                </span>

                <span id="{{ $id }}-title" class="modal-title" style="min-width:0;overflow:hidden;text-overflow:ellipsis">
                    {{ $title }}
                </span>
            </div>

            {{-- Close ✕ --}}
            <button
                type="button"
                class="btn btn-sukk-icon"
                aria-label="إغلاق"
                onclick="window._sakkCloseModal('{{ $id }}')"
                style="flex-shrink:0"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="modal-body">
            <p
                id="{{ $id }}-desc"
                style="color:var(--text-secondary);font-size:var(--font-size-sm);font-family:'IBM Plex Sans Arabic',system-ui,sans-serif;line-height:1.75;margin:0"
            >{{ $message }}</p>

            {{-- Optional extra slot (details, affected records count, etc.) --}}
            @if($slot->isNotEmpty())
            <div style="margin-top:1rem">{{ $slot }}</div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="modal-footer" style="flex-direction:row-reverse;justify-content:flex-start">
            {{-- Confirm --}}
            @if($formId)
                <button
                    type="button"
                    class="btn btn-{{ $okVariant }}"
                    onclick="window._sakkCloseModal('{{ $id }}');
                             var f=document.getElementById('{{ $formId }}');
                             if(f) f.submit();"
                >{{ $okLabel }}</button>
            @else
                <button
                    type="button"
                    class="btn btn-{{ $okVariant }}"
                    onclick="(function(el){
                        var m=document.getElementById('{{ $id }}');
                        window._sakkCloseModal('{{ $id }}');
                        if(typeof m._onConfirm==='function') m._onConfirm();
                    })(this)"
                >{{ $okLabel }}</button>
            @endif

            {{-- Cancel --}}
            <button
                type="button"
                class="btn btn-secondary"
                onclick="window._sakkCloseModal('{{ $id }}')"
            >{{ $cancelLabel }}</button>
        </div>

    </div>
</div>

{{-- Inline helper — idempotent; safe to have multiple confirm-modal instances on one page --}}
<script>
(function () {
    if (window._sakkCloseModal) return; // already defined

    window._sakkCloseModal = function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.display = 'none';
        // Return focus to the element that opened the modal, if tracked
        if (el._returnFocus && el._returnFocus.focus) {
            try { el._returnFocus.focus(); } catch(e) {}
        }
    };

    // Global keyboard handler: Escape closes any open modal-backdrop
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var open = document.querySelectorAll(
            '.modal-backdrop[role="alertdialog"]'
        );
        open.forEach(function (m) {
            if (m.style.display !== 'none') {
                window._sakkCloseModal(m.id);
            }
        });
    });

    // Focus trap: when modal opens, intercept Tab/Shift-Tab inside the box
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab') return;
        var modal = document.querySelector(
            '.modal-backdrop[role="alertdialog"][style*="flex"]'
        );
        if (!modal) return;
        var focusable = Array.from(modal.querySelectorAll(
            'button:not([disabled]),a,input,select,textarea,[tabindex]:not([tabindex="-1"])'
        )).filter(function (el) { return el.offsetParent !== null; });
        if (!focusable.length) return;
        var first = focusable[0];
        var last  = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });
}());
</script>
