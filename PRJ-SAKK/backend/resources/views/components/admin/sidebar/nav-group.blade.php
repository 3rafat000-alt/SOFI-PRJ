@props([
    'icon' => 'folder',
    'label' => '',
    'routeIs' => '',
])

<div x-data="sidebarNav" data-initial-open="{{ request()->routeIs($routeIs) ? 'true' : 'false' }}">
    <button @click="sidebarOpen ? (open = !open) : (sidebarOpen = true, open = true)" :class="open && 'sd-group--open'" class="sd-link w-full {{ request()->routeIs($routeIs) ? 'sd-link--active' : '' }}" aria-label="{{ $label }}" title="{{ $label }}" @if(request()->routeIs($routeIs)) aria-current="page" @endif>
        <x-heroicon name="{{ $icon }}" class="w-5 h-5 shrink-0" />
        <span x-show="sidebarOpen" class="flex-1 text-right">{{ $label }}</span>
        <x-heroicon name="expand_more" x-show="sidebarOpen" class="sd-group-chevron shrink-0" ::class="open && 'sd-chevron--open'" />
    </button>
    <div class="sd-subtree" x-show="open && sidebarOpen" x-collapse>
        {{ $slot }}
    </div>
</div>
