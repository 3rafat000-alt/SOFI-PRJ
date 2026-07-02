{{--
  Component: <x-admin.modal>
  -----------------------------------------------------------------------
  A reusable, accessible, RTL-ready modal dialog wired to the SAKK admin
  design system (admin.css + admin.js). Zero external dependencies.

  Props:
    id          (string, required)  — Unique DOM id. Used to open/close the
                                      modal from anywhere:
                                        open:  document.getElementById('ID').dataset.open='1'
                                        close: document.getElementById('ID').dataset.open='0'
                                      Or via data attributes on any element:
                                        data-modal-open="ID"
                                        data-modal-close="ID"
    title       (string)            — Modal heading shown in header. Default: ''.
    size        (string)            — sm(360) | md(520, default) | lg(720) | xl(960) | full
    icon        (string)            — Optional Material Icon name shown left of the title.
    iconColor   (string)            — Icon chip colour: primary|success|warning|danger|info.
                                      Default: 'primary'.
    closeable   (bool)              — Show × close button and backdrop-click-to-close.
                                      Default: true.
    scrollable  (bool)              — Allow the body to scroll independently (fixed header/footer).
                                      Default: false.
    staticData  (bool)              — Alias kept for legacy callers. Same as closeable=false.
                                      Default: false.

  Named slots:
    $footer     — Action buttons rendered in the footer bar.
    default     — Modal body content.

  Usage:
    <x-admin.modal id="create-user-modal" title="إضافة مستخدم جديد" size="md" icon="person_add">
        <x-admin.form id="create-user-form" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <x-admin.input name="name" label="الاسم الكامل" required />
        </x-admin.form>
        <x-slot:footer>
            <x-admin.button variant="secondary" data-modal-close="create-user-modal">إلغاء</x-admin.button>
            <x-admin.button type="submit" form="create-user-form" icon="save">حفظ</x-admin.button>
        </x-slot:footer>
    </x-admin.modal>

    {{-- Trigger from anywhere --}}
    <x-admin.button data-modal-open="create-user-modal" icon="add">إضافة مستخدم</x-admin.button>

  JS API (global, added by this component):
    window.sakkModal.open('ID')
    window.sakkModal.close('ID')
--}}

@props([
    'id'         => null,
    'title'      => '',
    'size'       => 'md',
    'icon'       => null,
    'iconColor'  => 'primary',
    'closeable'  => true,
    'scrollable' => false,
    'staticData' => false,
])

@php
    // Resolve effective closeable flag
    $isCloseable = $closeable && !$staticData;

    // Max-width per size token
    $maxW = match($size) {
        'sm'   => '360px',
        'lg'   => '720px',
        'xl'   => '960px',
        'full' => 'calc(100vw - 2rem)',
        default => '520px',   // md
    };

    // Icon chip background / colour pairs (uses CSS custom properties)
    $iconChipStyle = match($iconColor) {
        'success' => 'background:var(--success-light,rgba(31,157,85,.10));color:var(--success-text,#1F9D55)',
        'warning' => 'background:var(--warning-light,rgba(217,119,6,.10));color:var(--warning-text,#D97706)',
        'danger'  => 'background:var(--danger-light);color:var(--danger)',
        'info'    => 'background:var(--info-light);color:var(--info)',
        default   => 'background:var(--primary-bg-md,#EDD0D5);color:var(--sukk-primary)',
    };

    // Scrollable: body gets constrained height so header/footer stay visible
    $bodyStyle = $scrollable
        ? 'max-height:60vh;overflow-y:auto;'
        : '';
@endphp

{{-- ─── Backdrop ──────────────────────────────────────────────────────────── --}}
<div
    id="{{ $id }}"
    class="modal-backdrop"
    role="dialog"
    aria-modal="true"
    @if($title) aria-labelledby="{{ $id }}-title" @endif
    dir="rtl"
    data-sakk-modal
    style="display:none"
    @if($isCloseable)
        onclick="if(event.target===this)window.sakkModal&&window.sakkModal.close('{{ $id }}')"
    @endif
>

    {{-- ─── Box ──────────────────────────────────────────────────────────── --}}
    <div
        class="modal-box"
        style="max-width:{{ $maxW }}"
        role="document"
    >

        {{-- ── Header ─────────────────────────────────────────────────────── --}}
        <div class="modal-header">

            {{-- Icon + Title group --}}
            <div style="display:flex;align-items:center;gap:12px;min-width:0">

                @if($icon)
                <div style="
                    width:38px;height:38px;
                    border-radius:12px;
                    display:flex;align-items:center;justify-content:center;
                    flex-shrink:0;
                    {{ $iconChipStyle }}
                " aria-hidden="true">
                    {{-- Inline SVG wrapper — icon rendered as Material symbol text node --}}
                    {{-- TODO: map $icon to Heroicon --}}
                    <x-heroicon name="{{ $icon }}"  />
                </div>
                @endif

                @if($title)
                <h2
                    id="{{ $id }}-title"
                    class="modal-title"
                    style="margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                >{{ $title }}</h2>
                @endif

            </div>

            {{-- Close button --}}
            @if($isCloseable)
            <button
                type="button"
                class="btn btn-sukk-icon btn-secondary"
                style="flex-shrink:0;"
                onclick="window.sakkModal&&window.sakkModal.close('{{ $id }}')"
                aria-label="إغلاق"
                data-modal-close="{{ $id }}"
            >
                {{-- × icon as inline SVG so no CDN required --}}
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"
                     xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            @endif

        </div>
        {{-- /modal-header --}}

        {{-- ── Body ──────────────────────────────────────────────────────── --}}
        <div class="modal-body" style="{{ $bodyStyle }}">
            {{ $slot }}
        </div>

        {{-- ── Footer (optional slot) ─────────────────────────────────────── --}}
        @if(isset($footer))
        <div class="modal-footer">
            {{ $footer }}
        </div>
        @endif

    </div>
    {{-- /modal-box --}}

</div>
{{-- /modal-backdrop --}}

{{-- ─── Modal JS kernel (injected once per page via singleton guard) ─────── --}}
@once
<script>
(function () {
    'use strict';

    /* Guard: only initialise the kernel once even if the component
       appears multiple times on the same page. */
    if (window.sakkModal) return;

    /* Scroll lock (ref-counted) — #mainContent is the scroll container now
       (contained app-shell: body never scrolls), so lock IT, not body.
       Ref-counted so stacked modals (e.g. a confirm dialog opened from
       inside another modal) only restore scroll once the LAST one closes. */
    var _scrollLockCount = 0;
    var _savedMainOverflow = null;
    function _scrollEl() {
        return document.getElementById('mainContent') || document.body;
    }
    function _lockBodyScroll() {
        if (_scrollLockCount === 0) {
            var el = _scrollEl();
            _savedMainOverflow = el.style.overflow;
            el.style.overflow = 'hidden';
        }
        _scrollLockCount++;
    }
    function _unlockBodyScroll() {
        if (_scrollLockCount === 0) return;
        _scrollLockCount--;
        if (_scrollLockCount === 0) {
            _scrollEl().style.overflow = _savedMainOverflow || '';
            _savedMainOverflow = null;
        }
    }

    /**
     * Focus trap utility: returns an array of all focusable elements
     * inside a container.
     */
    function getFocusable(el) {
        return Array.from(el.querySelectorAll(
            'a[href],button:not([disabled]),input:not([disabled]),' +
            'select:not([disabled]),textarea:not([disabled]),' +
            '[tabindex]:not([tabindex="-1"])'
        )).filter(function (n) {
            return !n.closest('[hidden]') && getComputedStyle(n).display !== 'none';
        });
    }

    /** Open a modal by id. */
    function open(id) {
        var el = document.getElementById(id);
        if (!el) return;

        // Prevent background scroll — body is the native scroll container.
        _lockBodyScroll();

        // Store the element that triggered open so we can restore focus on close
        el._sakk_opener = document.activeElement;

        // Show with animation
        el.style.display = 'flex';
        el.dataset.animating = '1';
        setTimeout(function () { delete el.dataset.animating; }, 300);

        // Move focus to first focusable child (or the box itself)
        var box = el.querySelector('.modal-box');
        var focusable = getFocusable(el);
        setTimeout(function () {
            if (focusable.length) {
                focusable[0].focus();
            } else if (box) {
                box.setAttribute('tabindex', '-1');
                box.focus();
            }
        }, 30);

        // Keyboard handler scoped to this modal
        function onKeydown(e) {
            if (e.key === 'Escape') {
                // Only close if closeable (backdrop has no onclick → no close)
                if (!el.getAttribute('onclick')) return;
                close(id);
                return;
            }
            if (e.key === 'Tab') {
                var nodes = getFocusable(el);
                if (!nodes.length) return;
                var first = nodes[0], last = nodes[nodes.length - 1];
                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    }
                } else {
                    if (document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            }
        }

        el._sakk_keydown = onKeydown;
        document.addEventListener('keydown', onKeydown);
    }

    /** Close a modal by id. */
    function close(id) {
        var el = document.getElementById(id);
        if (!el) return;

        el.style.display = 'none';

        // Remove keyboard listener
        if (el._sakk_keydown) {
            document.removeEventListener('keydown', el._sakk_keydown);
            delete el._sakk_keydown;
        }

        // Release the ref-counted scroll lock (matches the lock() in open()).
        _unlockBodyScroll();

        // Restore focus to the element that opened the modal
        if (el._sakk_opener && el._sakk_opener.focus) {
            try { el._sakk_opener.focus(); } catch (e) {}
            delete el._sakk_opener;
        }
    }

    /** Delegate data-modal-open / data-modal-close clicks on the document. */
    document.addEventListener('click', function (e) {
        var openBtn  = e.target.closest('[data-modal-open]');
        var closeBtn = e.target.closest('[data-modal-close]');

        if (openBtn) {
            e.preventDefault();
            open(openBtn.dataset.modalOpen);
            return;
        }
        if (closeBtn) {
            e.preventDefault();
            close(closeBtn.dataset.modalClose);
        }
    });

    window.sakkModal = { open: open, close: close };
}());
</script>
@endonce
