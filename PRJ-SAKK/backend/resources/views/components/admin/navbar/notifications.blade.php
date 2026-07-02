@php
    $__alerts = \App\Models\AdminAlert::forAdmin()->latest()->limit(10)->get();
    $__unread = \App\Models\AdminAlert::forAdmin()->whereNull('read_at')->count();
@endphp

<div class="nv-dropdown-root" x-data="dropdown">
    <button @click="toggle()" class="nv-icon-btn" aria-label="الإشعارات">
        <x-heroicon name="notifications_none"  />
        @if ($__unread > 0)
            <span class="nv-dot"></span>
        @endif
    </button>
    <div x-show="open" x-cloak @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="nv-dropdown nv-dropdown--notif">
        <div class="nv-dropdown-hd">
            <span class="nv-dropdown-title">الإشعارات</span>
            @if ($__unread > 0)
                <span class="nv-dropdown-count">{{ $__unread }} جديد</span>
            @endif
        </div>
        <div class="nv-dropdown-body">
            @forelse ($__alerts as $__a)
                <div class="nv-alert {{ $__a->read_at ? '' : 'nv-alert--unread' }}"
                     data-id="{{ $__a->id }}"
                     @if ($__a->link)
                     role="link" tabindex="0"
                     @click="window.location.href='{{ $__a->link }}'"
                     @keydown.enter.prevent="window.location.href='{{ $__a->link }}'"
                     @keydown.space.prevent="window.location.href='{{ $__a->link }}'"
                     @endif>
                    <span class="nv-alert-dot nv-alert-dot--{{ $__a->type }}"></span>
                    <div class="nv-alert-content">
                        <div class="nv-alert-title">{{ $__a->title }}</div>
                        <div class="nv-alert-msg">{{ $__a->message }}</div>
                        <div class="nv-alert-time">{{ $__a->created_at->diffForHumans() }}</div>
                    </div>
                    <button type="button" @click.stop="dismissAdminAlert({{ $__a->id }},this)"
                            class="nv-alert-x" title="حذف">
                        <x-heroicon name="close"  />
                    </button>
                </div>
            @empty
                <div class="nv-dropdown-empty">
                    <x-heroicon name="notifications_off"  />
                    <p>لا إشعارات جديدة</p>
                </div>
            @endforelse
        </div>
        @if ($__alerts->isNotEmpty())
            <div class="nv-dropdown-ft">
                <button type="button" @click="markAllAdminAlertsRead()" class="nv-dropdown-action">
                    تحديد الكل كمقروء
                </button>
            </div>
        @endif
    </div>
</div>
