@props([
    'href' => '#',
    'icon' => 'circle',
    'label' => '',
    'routeIs' => '',
])

<a href="{{ $href }}" class="sd-link {{ request()->routeIs($routeIs) ? 'sd-link--active' : '' }}" @if(request()->routeIs($routeIs)) aria-current="page" @endif>
    <x-heroicon name="{{ $icon }}" class="w-5 h-5 shrink-0" />
    <span x-show="sidebarOpen">{{ $label }}</span>
</a>
