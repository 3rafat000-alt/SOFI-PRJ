{{--
  Component: <x-admin.badge>

  Props:
    type  (string) — color variant. Accepts either palette names or semantic aliases.

      Palette:   cyan | green | amber | red | blue | slate | outline
      Semantic:  active | inactive | pending | rejected | frozen
                 success (→ green) | warning (→ amber) | danger (→ red) | info (→ blue)

    dot   (bool, default false) — show a leading color dot matching the variant
    size  (string) — sm (default) | md | lg  — adjusts font/padding via inline style

  Slot: badge label text (Arabic)

  Usage:
    <x-admin.badge type="active">نشط</x-admin.badge>
    <x-admin.badge type="pending" :dot="true">معلق</x-admin.badge>
    <x-admin.badge type="success">مكتمل</x-admin.badge>
    <x-admin.badge type="danger">محظور</x-admin.badge>
    <x-admin.badge type="outline">عادي</x-admin.badge>
    <x-admin.badge type="amber" size="lg">تحت المراجعة</x-admin.badge>
--}}
@props([
    'type' => 'slate',
    'dot'  => false,
    'size' => 'sm',
])

@php
/*
 * Normalize semantic shorthand → CSS palette class.
 * All .badge-* classes are defined in /sakk-admin/admin.css.
 */
$typeMap = [
    'success' => 'green',
    'warning' => 'amber',
    'danger'  => 'red',
    'info'    => 'blue',
];

$resolvedType = $typeMap[$type] ?? $type;

/*
 * Dot color: reuse the same semantic mapping so the dot always matches
 * the badge background's accent hue.
 */
$dotColorMap = [
    'green'    => '#1F9D55',
    'active'   => '#1F9D55',
    'amber'    => '#B7791F',
    'pending'  => '#B7791F',
    'red'      => '#C0392B',
    'rejected' => '#C0392B',
    'cyan'     => 'var(--sukk-primary)',
    'wine'     => 'var(--sukk-primary)',
    'gold'     => 'var(--accent)',
    'blue'     => '#2563EB',
    'frozen'   => '#2563EB',
    'info'     => '#2563EB',
    'slate'    => 'var(--text-secondary)',
    'inactive' => 'var(--text-secondary)',
    'outline'  => 'var(--text-secondary)',
];
$dotColor = $dotColorMap[$resolvedType] ?? ($dotColorMap[$type] ?? 'var(--text-secondary)');

/*
 * Size modifiers — supplementary inline adjustments when "md" or "lg" requested.
 * Base .badge already handles the default "sm" sizing via CSS.
 */
$sizeStyle = match($size) {
    'md' => 'font-size:.8125rem;padding:4px 13px;',
    'lg' => 'font-size:.875rem;padding:5px 16px;',
    default => '',   // sm: governed fully by .badge in admin.css
};
@endphp



<span
    dir="rtl"
    {{ $attributes->merge(['class' => 'badge badge-' . $resolvedType]) }}
    @if($sizeStyle) style="{{ $sizeStyle }}" @endif
>
    @if($dot)
        <span
            aria-hidden="true"
            style="
                display:inline-block;
                width:6px;
                height:6px;
                border-radius:50%;
                background:{{ $dotColor }};
                flex-shrink:0;
                margin-inline-end:2px;
            "
        ></span>
    @endif
    {{ $slot }}
</span>
