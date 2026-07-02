<header class="nv-bar">
    {{-- ZONE 1: Toggle + Breadcrumb (right in RTL) --}}
    <div class="nv-zone nv-zone-start">
        <button @click="$dispatch('sidebar:toggle')" class="nv-toggle" aria-label="تبديل القائمة">
            <x-heroicon name="menu" />
        </button>
        <div class="nv-bread">
            <span class="nv-bread-current">@yield('title', 'لوحة التحكم')</span>
            <nav class="nv-bread-path" aria-label="مسار الصفحة">
                <a href="{{ route('admin.dashboard') }}">الرئيسية</a>
                @yield('breadcrumbs')
            </nav>
        </div>
    </div>

    {{-- ZONE 2: Global Search (center) --}}
    <div class="nv-zone nv-zone-center">
        <div class="hidden md:flex w-full justify-center px-2 max-w-md mx-auto">
            <x-admin.navbar.search />
        </div>
    </div>

    {{-- ZONE 3: Actions (left in RTL) --}}
    <div class="nv-zone nv-zone-end">
        @hasSection('topbar-actions')
            <div class="nv-actions">
                @yield('topbar-actions')
            </div>
            <div class="nv-sep"></div>
        @endif

        {{-- Keyboard shortcuts --}}
        <button @click="$dispatch('keyboard-help')" class="nv-icon-btn" aria-label="اختصارات لوحة المفاتيح">
            <x-heroicon name="keyboard" />
        </button>

        {{-- Notifications --}}
        <x-admin.navbar.notifications />
    </div>
</header>
