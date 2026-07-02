@php
    $__u = auth()->user();
    $__fn = $__u->first_name ?? 'مدير';
    $__ln = $__u->last_name ?? '';
    $__initials = mb_substr($__fn, 0, 1) . ($__ln ? mb_substr($__ln, 0, 1) : '');
    $__fullName = trim($__fn . ' ' . $__ln);
@endphp

<div class="nv-dropdown-root" x-data="dropdown">
    <button @click="toggle()" class="nv-profile-btn"
            :class="open && 'nv-profile-btn--open'"
            aria-label="قائمة الحساب" aria-haspopup="true" :aria-expanded="open">
        <span class="nv-avatar">{{ $__initials }}</span>
        <span class="nv-profile-name">{{ $__fullName }}</span>
        <x-heroicon name="expand_more" class="nv-profile-chev" x-bindx-bind:class="open && 'nv-chev--open'" />
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="nv-dropdown nv-dropdown--profile"
         role="menu" aria-label="قائمة الحساب">

        {{-- Identity header --}}
        <div class="nv-pf-head">
            <span class="nv-pf-avatar">{{ $__initials }}</span>
            <div class="nv-pf-info">
                <p class="nv-pf-name">{{ $__fullName }}</p>
                <p class="nv-pf-email">{{ $__u->email ?? '—' }}</p>
                <span class="nv-pf-role">مدير النظام</span>
            </div>
        </div>

        {{-- Menu items --}}
        <div class="nv-pf-menu">
            <p class="nv-pf-section">الحساب</p>
            <a href="{{ route('admin.settings') }}#profile" class="nv-pf-item" role="menuitem">
                <x-heroicon name="person"  />
                الملف الشخصي
            </a>
            <a href="{{ route('admin.settings') }}" class="nv-pf-item" role="menuitem">
                <x-heroicon name="settings"  />
                الإعدادات
            </a>
            <a href="{{ route('admin.users', ['kyc_status' => 'submitted']) }}" class="nv-pf-item" role="menuitem">
                <x-heroicon name="verified_user"  />
                طلبات التحقق
            </a>
        </div>

        {{-- Logout --}}
        <div class="nv-pf-logout">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="nv-pf-item nv-pf-item--danger" role="menuitem">
                    <x-heroicon name="logout"  />
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
</div>
