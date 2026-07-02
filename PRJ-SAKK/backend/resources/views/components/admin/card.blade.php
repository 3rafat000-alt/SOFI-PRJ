{{--
  Component: <x-admin.card>

  Props:
    $title    (string|null) — optional. Card heading shown in card-header.
    $subtitle (string|null) — optional. Muted sub-description below title.
    $icon     (string|null) — optional. Material Icons Round name beside title.
    $variant  (string)      — card style variant:
                                "default" — white surface, border, soft shadow (standard).
                                "accent"  — wine left-border accent stripe (highlight panels).
                                "ghost"   — no border/shadow, transparent bg (nested panels).
                                "flush"   — no outer padding on body, card border kept (for tables).
    $noPad    (bool)        — true = removes card-body padding (alias for $variant="flush").
    $noHover  (bool)        — true = disables hover shadow lift.
    $loading  (bool)        — true = shows skeleton shimmer overlay over the body slot.
    $id       (string|null) — optional id attribute on the root element.

  Named slots:
    $header  — replaces entire card-header region (when set, $title/$icon/$subtitle ignored).
    $actions — end area of card-header (buttons, badges, dropdowns).
    $footer  — card footer (right-aligned by default via .card-footer flex).

  Usage:
    Basic:
      <x-admin.card title="قائمة المستخدمين" icon="people">
          ...content...
      </x-admin.card>

    With actions + footer:
      <x-admin.card title="المعاملات" icon="swap_horiz" variant="accent">
          <x-slot:actions>
              <x-admin.button variant="primary" size="sm" icon="add">إضافة</x-admin.button>
          </x-slot:actions>
          ...content...
          <x-slot:footer>
              <x-admin.pagination :paginator="$items"/>
          </x-slot:footer>
      </x-admin.card>

    No-padding (for embedded tables):
      <x-admin.card title="المستخدمون" :noPad="true">
          <x-admin.data-table .../>
      </x-admin.card>

    Custom header override:
      <x-admin.card>
          <x-slot:header>
              <div class="card-header">...</div>
          </x-slot:header>
          ...content...
      </x-admin.card>

    Loading skeleton:
      <x-admin.card title="..." :loading="true">...</x-admin.card>

    Ghost (no chrome):
      <x-admin.card variant="ghost" title="ملاحظات">...</x-admin.card>
--}}
@props([
    'title'    => null,
    'subtitle' => null,
    'icon'     => null,
    'variant'  => 'default',
    'noPad'    => false,
    'noHover'  => false,
    'loading'  => false,
    'id'       => null,
])

@php
// Resolve effective no-pad: either explicit prop or flush variant.
$isFlush   = $noPad || $variant === 'flush';
$isGhost   = $variant === 'ghost';
$isAccent  = $variant === 'accent';
$isNoHover = $noHover || $isGhost;

// Build root class list.
$rootClasses = 'card';
if ($isGhost)   $rootClasses .= ' card--ghost';
if ($isAccent)  $rootClasses .= ' card--accent';
if ($isNoHover) $rootClasses .= ' card--no-hover';
@endphp

<div
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => $rootClasses]) }}
>

    {{-- ================================================================
         HEADER — custom slot overrides title/icon/subtitle auto-header
         ================================================================ --}}
    @if(isset($header))
        {{-- Caller provides the full header markup --}}
        {{ $header }}
    @elseif($title || isset($actions))
        <div class="card-header">
            {{-- Left / inline-end: icon + title + subtitle --}}
            <div class="card-title-group">
                <div class="card-title">
                    @if($icon)
                        <x-admin.icon :name="$icon" class="w-5 h-5" aria-hidden="true" />
                    @endif
                    <span class="card-title-text">{{ $title }}</span>
                </div>
                @if($subtitle)
                    <p class="card-subtitle">{{ $subtitle }}</p>
                @endif
            </div>

            {{-- Right / inline-start: action slot --}}
            @if(isset($actions))
                <div class="card-header-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{-- ================================================================
         BODY — with optional loading skeleton overlay
         ================================================================ --}}
    @if($isFlush)
        {{-- No wrapping div — slot renders directly inside card root --}}
        @if($loading)
            <div class="card-body" aria-busy="true" aria-label="جاري التحميل">
                <div class="card-skeleton" aria-hidden="true"></div>
            </div>
        @else
            {{ $slot }}
        @endif
    @else
        <div class="card-body">
            @if($loading)
                <div class="card-skeleton" role="status" aria-label="جاري التحميل" aria-live="polite">
                    {{-- Three shimmer lines --}}
                    <div class="card-skeleton__line" style="width:60%;height:14px;margin-bottom:10px;"></div>
                    <div class="card-skeleton__line" style="width:90%;height:12px;margin-bottom:8px;"></div>
                    <div class="card-skeleton__line" style="width:75%;height:12px;"></div>
                </div>
            @else
                {{ $slot }}
            @endif
        </div>
    @endif

    {{-- ================================================================
         FOOTER
         ================================================================ --}}
    @if(isset($footer))
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif

</div>


