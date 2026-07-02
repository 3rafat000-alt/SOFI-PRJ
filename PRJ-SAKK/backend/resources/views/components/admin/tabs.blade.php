{{--
  Component: <x-admin.tabs>
  Props:
    $tabs  (array) — required. Array of ['id'=>'...','label'=>'...','icon'=>'...(optional)']
    $active (string) — id of the default active tab.
  Slot: tab panel content — must use <x-slot:panel-{id}> named slot for each tab.

  NOTE: This component uses CSS + data-attrs managed by admin.js [data-tabs] pattern.
  For simple 2-3 tab pages, prefer direct HTML with .tabs / .tab-item / .tab-panel classes.

  Usage:
    <x-admin.tabs :tabs="[
        ['id'=>'info',   'label'=>'بيانات الحساب', 'icon'=>'person'],
        ['id'=>'kyc',    'label'=>'توثيق الهوية',  'icon'=>'badge'],
        ['id'=>'cards',  'label'=>'البطاقات',       'icon'=>'credit_card'],
    ]" active="info">
        <x-slot:panel-info> ... </x-slot:panel-info>
        <x-slot:panel-kyc>  ... </x-slot:panel-kyc>
        <x-slot:panel-cards>... </x-slot:panel-cards>
    </x-admin.tabs>
--}}
@props(['tabs' => [], 'active' => null])

@php $activeId = $active ?? ($tabs[0]['id'] ?? ''); @endphp

<div data-tabs class="tc">
    <div class="tabs" role="tablist">
        @foreach($tabs as $tab)
        <button role="tab"
                class="tab-item {{ $tab['id'] === $activeId ? 'active' : '' }}"
                data-tab="{{ $tab['id'] }}"
                aria-selected="{{ $tab['id'] === $activeId ? 'true' : 'false' }}"
                aria-controls="panel-{{ $tab['id'] }}"
                id="tab-{{ $tab['id'] }}">
            @if(!empty($tab['icon']))
                <x-admin.icon :name="$tab['icon']" class="w-5 h-5" aria-hidden="true" />
            @endif
            {{ $tab['label'] }}
        </button>
        @endforeach
    </div>

    <div style="padding-top:var(--sp-5)">
        @foreach($tabs as $tab)
        <div role="tabpanel"
             id="panel-{{ $tab['id'] }}"
             data-tab-panel="{{ $tab['id'] }}"
             aria-labelledby="tab-{{ $tab['id'] }}"
             class="tab-panel {{ $tab['id'] === $activeId ? 'active' : '' }}"
             @if($tab['id'] !== $activeId) hidden @endif>
            {{ $slot ?? '' }}
            @isset($${'panel-' . $tab['id']})
                {{ $${'panel-' . $tab['id']} }}
            @endisset
        </div>
        @endforeach
    </div>
</div>
