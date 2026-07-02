{{--
  Component: <x-admin.empty-state>

  A reusable empty / zero-results / error state panel.
  Uses .sakk-empty (defined in /sakk-admin/admin.css). RTL-native. No CDN.

  Props:
    $title    (string)       — heading. Default: 'لا توجد بيانات'.
    $desc     (string|null)  — optional muted description below the heading.
    $icon     (string)       — preset icon key OR raw SVG <path> d="…" string.
                               Preset keys: inbox (default) | search | users | file
                                            | folder | lock | chart | card | error | wifi.
    $size     (string)       — sm | md (default) | lg — scales illustration + text.
    $variant  (string)       — default | bordered | ghost.
                               "default"  — white .sakk-card surface, soft shadow.
                               "bordered" — same but with explicit border.
                               "ghost"    — no bg/border (embed inside another card).
    $loading  (bool)         — true = show shimmer skeleton instead of content.

  Named slot:
    $slot — optional CTA (any Blade markup: buttons, links, forms, …).

  Usage:
    Minimal:
      <x-admin.empty-state title="لا يوجد مستخدمون" />

    With description:
      <x-admin.empty-state
          title="لا توجد معاملات بعد"
          desc="لم يتم تسجيل أي معاملة حتى الآن."
          icon="card"
      />

    With CTA:
      <x-admin.empty-state
          title="لا توجد قوائم"
          desc="أضف أول عنصر الآن."
          icon="folder"
      >
          <x-admin.button variant="gold" href="{{ route('admin.items.create') }}">
              + إنشاء عنصر
          </x-admin.button>
      </x-admin.empty-state>

    Search no-results (ghost, small):
      <x-admin.empty-state
          title="لا توجد نتائج"
          desc="حاول تعديل كلمات البحث."
          icon="search"
          size="sm"
          variant="ghost"
      />

    Error state:
      <x-admin.empty-state
          title="حدث خطأ"
          desc="تعذّر تحميل البيانات. الرجاء المحاولة مرة أخرى."
          icon="error"
          size="md"
      >
          <x-admin.button variant="primary" onclick="location.reload()">
              إعادة المحاولة
          </x-admin.button>
      </x-admin.empty-state>
--}}
@props([
    'title'   => 'لا توجد بيانات',
    'desc'    => null,
    'icon'    => 'inbox',
    'size'    => 'md',
    'variant' => 'default',
    'loading' => false,
])

@php
/*
 * ── Preset icon library ─────────────────────────────────────────────────────
 * Each value is an array: ['viewBox', 'path', 'accent_color']
 * accent_color is the fill applied to the illustration circle.
 * Callers may also pass a raw SVG <path> d="…" string to $icon directly.
 */
$icons = [
    'inbox'  => ['0 0 24 24', 'M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z', '#6E1B2D'],
    'search' => ['0 0 24 24', 'M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z', '#6E1B2D'],
    'users'  => ['0 0 24 24', 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z', '#6E1B2D'],
    'file'   => ['0 0 24 24', 'M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z', '#6E1B2D'],
    'folder' => ['0 0 24 24', 'M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z', '#6E1B2D'],
    'lock'   => ['0 0 24 24', 'M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z', '#4A1320'],
    'chart'  => ['0 0 24 24', 'M3.5 18.5l6-6 4 4L22 6.92 20.59 5.5l-7.09 8.07-4-4L2 17l1.5 1.5z', '#1F9D55'],
    'card'   => ['0 0 24 24', 'M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z', '#6E1B2D'],
    'error'  => ['0 0 24 24', 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z', '#C0392B'],
    'wifi'   => ['0 0 24 24', 'M1 9l2 2c5.52-5.52 14.48-5.52 20 0l2-2C19.93 3.46 4.07 3.46 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z', '#2563EB'],
];

// Resolve icon: preset key → SVG path; else treat raw value as <path> d string.
$isPreset     = isset($icons[$icon]);
$iconData     = $isPreset ? $icons[$icon] : ['0 0 24 24', $icon, '#6E1B2D'];
$iconViewBox  = $iconData[0];
$iconPath     = $iconData[1];
$iconAccent   = $iconData[2];

// ── Size scale ──────────────────────────────────────────────────────────────
$sizeMap = [
    'sm' => ['ill' => '40px',  'svg' => '22', 'title' => '.9375rem', 'desc' => '.8125rem', 'gap' => '10px', 'pad' => '1.5rem 1.25rem'],
    'md' => ['ill' => '64px',  'svg' => '30', 'title' => '1.125rem', 'desc' => '.875rem',  'gap' => '14px', 'pad' => '2.5rem 1.5rem'],
    'lg' => ['ill' => '88px',  'svg' => '42', 'title' => '1.375rem', 'desc' => '1rem',     'gap' => '18px', 'pad' => '3.5rem 2rem'],
];
$scale = $sizeMap[$size] ?? $sizeMap['md'];

// ── Variant surface ──────────────────────────────────────────────────────────
$surfaceStyle = match($variant) {
    'ghost'    => 'background:transparent;border:none;box-shadow:none;',
    'bordered' => 'background:var(--surface);border:1.5px solid rgba(0,0,0,0.08);box-shadow:none;',
    default    => 'background:var(--surface);box-shadow:var(--shadow-sm);border:1px solid rgba(0,0,0,0.04);',
};
@endphp



{{-- ── Root element ────────────────────────────────────────────────────────── --}}
<div
    dir="rtl"
    role="status"
    aria-live="polite"
    {{ $attributes->merge(['class' => 'sakk-empty']) }}
    style="{{ $surfaceStyle }} padding:{{ $scale['pad'] }};"
>
    @if($loading)
        {{-- ── Loading skeleton ───────────────────────────────────────────── --}}
        <div
            class="sakk-empty__ill"
            aria-hidden="true"
            style="
                width:{{ $scale['ill'] }};
                height:{{ $scale['ill'] }};
                background:#F2ECE5;
                margin-bottom:{{ $scale['gap'] }};
            "
        ></div>

        <div
            class="sakk-empty__skeleton-line"
            aria-hidden="true"
            style="width:160px;height:{{ $scale['svg'] === '42' ? '18px' : ($scale['svg'] === '22' ? '13px' : '15px') }};margin-bottom:8px;"
        ></div>
        <div
            class="sakk-empty__skeleton-line"
            aria-hidden="true"
            style="width:240px;height:12px;"
        ></div>
    @else
        {{-- ── Illustration circle + icon ────────────────────────────────── --}}
        <div
            class="sakk-empty__ill"
            aria-hidden="true"
            style="
                width:{{ $scale['ill'] }};
                height:{{ $scale['ill'] }};
                background:{{ $iconAccent }}1A;
                margin-bottom:{{ $scale['gap'] }};
            "
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="{{ $scale['svg'] }}"
                height="{{ $scale['svg'] }}"
                viewBox="{{ $iconViewBox }}"
                fill="{{ $iconAccent }}"
                aria-hidden="true"
                focusable="false"
            >
                <path d="{{ $iconPath }}"/>
            </svg>
        </div>

        {{-- ── Heading ─────────────────────────────────────────────────── --}}
        <h3
            class="sakk-empty__title"
            style="font-size:{{ $scale['title'] }};margin-bottom:{{ $desc ? '6px' : '0' }};"
        >
            {{ $title }}
        </h3>

        {{-- ── Description ─────────────────────────────────────────────── --}}
        @if($desc)
            <p
                class="sakk-empty__desc"
                style="font-size:{{ $scale['desc'] }};max-width:380px;margin-bottom:0;"
            >
                {{ $desc }}
            </p>
        @endif

        {{-- ── CTA slot ─────────────────────────────────────────────────── --}}
        @if($slot->isNotEmpty())
            <div
                class="sakk-empty__cta"
                style="margin-top:{{ $scale['gap'] }};"
            >
                {{ $slot }}
            </div>
        @endif
    @endif
</div>
