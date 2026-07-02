{{--
  Component: <x-admin.quick-card>

  A compact shortcut/action card used in dashboard grids and quick-access panels.
  Renders a rounded white card with an icon bubble, title, optional subtitle,
  optional badge/count, and an optional action link or button. Fully RTL, no CDN.

  Props:
    $title      (string)       — required. Primary label in Arabic.
    $subtitle   (string|null)  — optional. Secondary muted line below title.
    $icon       (string)       — Material Icons Round ligature name. Default: 'bolt'.
    $iconColor  (string)       — Icon bubble color variant:
                                   wine | gold | green | amber | red | blue | slate | cyan.
                                   Default: 'wine'.
    $href       (string|null)  — If set, the entire card becomes a navigable link.
    $badge      (string|null)  — Optional short badge text (e.g. count "٥", "جديد").
    $badgeType  (string)       — Badge color type passed to <x-admin.badge>:
                                   success | warning | danger | info | slate. Default: 'info'.
    $variant    (string)       — Layout variant:
                                   'default'   — icon left (inline-end in RTL) + text stack (standard).
                                   'horizontal' — icon + text on one row (compact bar style).
                                   'icon-top'  — icon above text (center-aligned column).
                                   Default: 'default'.
    $disabled   (bool)         — Dims the card and removes pointer events. Default: false.
    $loading    (bool)         — Shows skeleton shimmer instead of content. Default: false.
    $id         (string|null)  — Optional id attribute. Default: null.

  Named slots:
    $actions — optional area rendered below the subtitle (inline CTAs, buttons).
    $footer  — optional footer strip at the bottom of the card.

  Usage:
    Basic shortcut:
      <x-admin.quick-card
          title="إدارة المستخدمين"
          subtitle="عرض وتحرير حسابات المستخدمين"
          icon="manage_accounts"
          icon-color="wine"
          href="{{ route('admin.users.index') }}"
      />

    With badge count:
      <x-admin.quick-card
          title="الطلبات المعلقة"
          icon="pending_actions"
          icon-color="amber"
          badge="{{ $pendingCount }}"
          badge-type="warning"
          href="{{ route('admin.orders.pending') }}"
      />

    Icon-top variant with action slot:
      <x-admin.quick-card
          title="تقرير المبيعات"
          subtitle="تنزيل تقرير الشهر الحالي"
          icon="bar_chart"
          icon-color="green"
          variant="icon-top"
      >
          <x-slot:actions>
              <x-admin.button variant="ghost" size="sm" href="#">تنزيل</x-admin.button>
          </x-slot:actions>
      </x-admin.quick-card>

    Horizontal compact bar:
      <x-admin.quick-card
          title="إجمالي المعاملات"
          badge="١٢٣"
          badge-type="info"
          icon="swap_horiz"
          variant="horizontal"
      />

    Loading state:
      <x-admin.quick-card title="..." :loading="true" />
--}}
@props([
    'title'     => '',
    'subtitle'  => null,
    'icon'      => 'bolt',
    'iconColor' => 'wine',
    'href'      => null,
    'badge'     => null,
    'badgeType' => 'info',
    'variant'   => 'default',
    'disabled'  => false,
    'loading'   => false,
    'id'        => null,
])

@php
/*
 * Icon bubble inline styles — SAKK identity colors + standard palette.
 * Matches the stat component pattern; no external dependency.
 */
$iconColorMap = [
    'wine'  => 'background:rgba(110,27,45,0.10);color:#6E1B2D;',
    'gold'  => 'background:var(--accent-soft);color:var(--accent-dark);',
    'green' => 'background:rgba(31,157,85,0.10);color:#1F9D55;',
    'amber' => 'background:rgba(183,121,31,0.10);color:#B7791F;',
    'red'   => 'background:rgba(192,57,43,0.10);color:#C0392B;',
    'blue'  => 'background:rgba(37,99,235,0.10);color:#2563EB;',
    'cyan'  => 'background:rgba(110,27,45,0.08);color:#6E1B2D;',
    'slate' => 'background:rgba(110,95,99,0.10);color:var(--text-secondary);',
];
$iconStyle = $iconColorMap[$iconColor] ?? $iconColorMap['wine'];

/*
 * Variant → layout modifier class.
 */
$variantClass = match($variant) {
    'horizontal' => 'quick-card--horizontal',
    'icon-top'   => 'quick-card--icon-top',
    default      => '',
};

/*
 * Root element: <a> when href provided, else <div>.
 * Disabled cards always render as <div> regardless of href.
 */
$isLink    = $href && !$disabled;
$tag       = $isLink ? 'a' : 'div';
$linkAttr  = $isLink ? 'href="' . e($href) . '"' : '';

/*
 * Root class list.
 */
$rootClasses = trim(implode(' ', array_filter([
    'quick-card',
    $variantClass,
    $disabled ? 'quick-card--disabled' : '',
    $isLink   ? 'quick-card--link'     : '',
])));

/*
 * Accessibility role.
 */
$ariaRole = $isLink ? '' : 'region';
@endphp

{{-- ================================================================
     Root element — <a> or <div>
     ================================================================ --}}
<{{ $tag }}
    @if($id) id="{{ $id }}" @endif
    @if($isLink) {!! $linkAttr !!} @endif
    {{ $attributes->merge(['class' => $rootClasses]) }}
    @if($ariaRole) role="{{ $ariaRole }}" @endif
    @if($disabled) aria-disabled="true" @endif
    @if(!$isLink && $title) aria-label="{{ $title }}" @endif
    dir="rtl"
>

    {{-- ================================================================
         LOADING — skeleton shimmer replaces content
         ================================================================ --}}
    @if($loading)
        <div class="quick-card__inner" aria-hidden="true">
            {{-- Icon placeholder --}}
            <div
                class="quick-card__skeleton-line"
                style="width:44px;height:44px;border-radius:var(--radius-sm);flex-shrink:0;"
            ></div>
            <div style="display:flex;flex-direction:column;gap:8px;flex:1;min-width:0;">
                <div class="quick-card__skeleton-line" style="height:14px;width:65%;"></div>
                <div class="quick-card__skeleton-line" style="height:11px;width:85%;"></div>
                <div class="quick-card__skeleton-line" style="height:11px;width:50%;"></div>
            </div>
        </div>

    {{-- ================================================================
         CONTENT
         ================================================================ --}}
    @else
        <div class="quick-card__inner">

            {{-- Icon bubble --}}
            <div
                class="quick-card__icon"
                style="{{ $iconStyle }}"
                aria-hidden="true"
            >
                {{-- TODO: map $icon to Heroicon --}}
                <x-heroicon name="{{ $icon }}"  />
            </div>

            {{-- Text body --}}
            <div class="quick-card__body">
                <span class="quick-card__title">{{ $title }}</span>

                @if($subtitle)
                    <span class="quick-card__subtitle">{{ $subtitle }}</span>
                @endif

                @if($badge !== null && $badge !== '')
                    <div class="quick-card__meta" style="margin-top:6px;">
                        <x-admin.badge :type="$badgeType" dot>{{ $badge }}</x-admin.badge>
                    </div>
                @endif
            </div>

            {{-- Chevron arrow for link cards (RTL: points inline-start = left) --}}
            @if($isLink)
                <div class="quick-card__chevron" aria-hidden="true">
                    {{-- Inline SVG chevron — no CDN --}}
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        {{-- Chevron-left: points left, which is "forward" in RTL nav --}}
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </div>
            @endif

        </div>

        {{-- ============================================================
             ACTIONS slot — rendered below main body, above footer
             ============================================================ --}}
        @if(isset($actions))
            <div class="quick-card__actions">
                {{ $actions }}
            </div>
        @endif

        {{-- ============================================================
             DEFAULT SLOT — arbitrary inline content (below icon row)
             ============================================================ --}}
        @if(!$slot->isEmpty())
            <div style="margin-top:12px;">
                {{ $slot }}
            </div>
        @endif

        {{-- ============================================================
             FOOTER slot
             ============================================================ --}}
        @if(isset($footer))
            <div class="quick-card__footer">
                {{ $footer }}
            </div>
        @endif
    @endif

</{{ $tag }}>
