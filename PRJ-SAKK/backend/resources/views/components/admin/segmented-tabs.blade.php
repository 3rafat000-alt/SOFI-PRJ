{{--
  Component: <x-admin.segmented-tabs>

  A pill-style segmented control where tabs live inside a single rounded capsule.
  Distinct from <x-admin.tabs> (underline border style). Use for 2-5 options that
  are mutually exclusive and visually prominent (filters, view toggles, status split).

  Props:
    $tabs   (array, required)
            Each item: ['id' => string, 'label' => string, 'icon' => string (optional), 'count' => int|null (optional)]
    $active (string, optional) — id of the initially active tab. Defaults to first.
    $size   (string)           — 'sm' | 'md' (default) | 'lg'
    $id     (string)           — unique id prefix for this control (auto-generated if omitted)
    $name   (string)           — aria-label / group name (Arabic). Default: 'تصفية'

  Slot content — named panel slot per tab id:
    <x-slot:panel-{id}> ... </x-slot:panel-{id}>

  Usage:
    <x-admin.segmented-tabs
        :tabs="[
            ['id'=>'all',     'label'=>'الكل',      'icon'=>'<svg .../>',  'count'=>120],
            ['id'=>'active',  'label'=>'نشط',       'icon'=>'<svg .../>',  'count'=>98],
            ['id'=>'frozen',  'label'=>'مجمّد',     'count'=>14],
            ['id'=>'closed',  'label'=>'مغلق',      'count'=>8],
        ]"
        active="all"
        id="card-status">
        <x-slot:panel-all>    {{ $allContent    }} </x-slot:panel-all>
        <x-slot:panel-active> {{ $activeContent }} </x-slot:panel-active>
        <x-slot:panel-frozen> {{ $frozenContent }} </x-slot:panel-frozen>
        <x-slot:panel-closed> {{ $closedContent }} </x-slot:panel-closed>
    </x-admin.segmented-tabs>

  JS: wired via data-tabs (same initTabs() in admin.js) — data-tab / data-tab-panel attrs.
--}}

@props([
    'tabs'   => [],
    'active' => null,
    'size'   => 'md',
    'id'     => null,
    'name'   => 'تصفية',
])

@php
    $activeId  = $active ?? ($tabs[0]['id'] ?? '');
    $controlId = $id ?? 'seg-tabs-' . substr(md5(json_encode($tabs)), 0, 6);
@endphp

{{-- ============================================================
     SEGMENTED CONTROL WRAPPER
     ============================================================ --}}
<div data-tabs id="{{ $controlId }}" dir="rtl">

    {{-- Pill bar --}}
    <div role="tablist" aria-label="{{ $name }}"
         class="seg-tabs__list seg-tabs__list--{{ $size }}">

        @foreach($tabs as $tab)
        @php $isActive = $tab['id'] === $activeId; @endphp

        <button type="button" role="tab"
            id="{{ $controlId }}-tab-{{ $tab['id'] }}"
            data-tab="{{ $tab['id'] }}"
            aria-selected="{{ $isActive ? 'true' : 'false' }}"
            aria-controls="{{ $controlId }}-panel-{{ $tab['id'] }}"
            class="seg-tabs__btn seg-tabs__btn--{{ $size }}{{ $isActive ? ' seg-tabs__btn--active' : '' }}">

            {{-- Optional inline SVG icon --}}
            @if(!empty($tab['icon']))
            <span aria-hidden="true" class="seg-tabs__icon {{ $isActive ? 'seg-tabs__icon--active' : 'seg-tabs__icon--inactive' }}">
                {!! $tab['icon'] !!}
            </span>
            @endif

            {{-- Label --}}
            <span>{{ $tab['label'] }}</span>

            {{-- Optional count badge --}}
            @if(isset($tab['count']) && $tab['count'] !== null)
            <span aria-label="{{ $tab['count'] }} عنصر"
                  class="seg-tabs__count seg-tabs__count--{{ $size }} {{ $isActive ? 'seg-tabs__count--active' : 'seg-tabs__count--inactive' }}">
                {{ number_format($tab['count']) }}
            </span>
            @endif

        </button>
        @endforeach

    </div>

    {{-- Tab panels --}}
    <div style="margin-top:var(--sp-5)">
        @foreach($tabs as $tab)
        @php $panelSlot = 'panel-' . $tab['id']; @endphp
        <div role="tabpanel"
            id="{{ $controlId }}-panel-{{ $tab['id'] }}"
            data-tab-panel="{{ $tab['id'] }}"
            aria-labelledby="{{ $controlId }}-tab-{{ $tab['id'] }}"
            class="tab-panel {{ $tab['id'] === $activeId ? 'active' : '' }}"
            @if($tab['id'] !== $activeId) hidden @endif>
            @isset($$panelSlot)
                {{ $$panelSlot }}
            @endisset
        </div>
        @endforeach
    </div>

</div>
