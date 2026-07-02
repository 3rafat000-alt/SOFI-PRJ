<!DOCTYPE html>
<html lang="ar" dir="rtl" x-data="sidebarLayout">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') - صكك</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.svg">
    {{-- SAKK Design System — external CSS files with cache-busting --}}
    <link href="{{ asset('sakk-assets/sakk-tokens.css') }}?v={{ filemtime(public_path('sakk-assets/sakk-tokens.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/tokens.css') }}?v={{ filemtime(public_path('css/admin/tokens.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/base.css') }}?v={{ filemtime(public_path('css/admin/base.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/navbar.css') }}?v={{ filemtime(public_path('css/admin/navbar.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/sidebar.css') }}?v={{ filemtime(public_path('css/admin/sidebar.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/utilities.css') }}?v={{ filemtime(public_path('css/admin/utilities.css')) }}" rel="stylesheet">
    {{-- material-icons.css removed — all icons migrated to inline SVGs or x-heroicon --}}
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" integrity="sha384-vsrfeLOOY6KuIYKDlmVH5UiBmgIdB1oEf7p01YgWHuqmOHfZr374+odEv96n9tNC" crossorigin="anonymous"></script>
    <script defer src="{{ asset('vendor/alpine/alpine-collapse-3.14.8.min.js') }}"></script>
    <script defer src="{{ asset('vendor/alpine/alpine-focus-3.14.8.min.js') }}"></script>
    <script defer src="{{ asset('vendor/alpine/alpine-3.14.8.min.js') }}"></script>
    @stack('styles')
</head>
<body class="min-h-screen" dir="rtl" style="font-family:var(--font);background:#F7F3EE">

<!-- Toast Container -->
<div class="fixed top-5 left-5 z-50 flex flex-col gap-2" id="toastContainer" x-data="toastSystem" @toast.window="addToast($event.detail)">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="toast flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg min-w-[320px] max-w-[420px]"
             :class="{
                'bg-green-50 text-green-700 border border-green-200': toast.type === 'success',
                'bg-red-50 text-red-700 border border-red-200': toast.type === 'error',
                'bg-amber-50 text-amber-700 border border-amber-200': toast.type === 'warning',
                'bg-gray-900 text-white border border-gray-700': toast.type === 'info'
             }">
            <x-heroicon name="check_circle" class="text-lg" x-show="toast.type === 'success'" />
            <x-heroicon name="error" class="text-lg" x-show="toast.type === 'error'" />
            <x-heroicon name="warning" class="text-lg" x-show="toast.type === 'warning'" />
            <x-heroicon name="info" class="text-lg" x-show="toast.type !== 'success' && toast.type !== 'error' && toast.type !== 'warning'" />
            <span x-text="toast.message" class="text-sm font-bold"></span>
        </div>
    </template>
</div>

<!-- Confirmation Modal -->
<div x-data="confirmModal" @confirm-modal.window="show = true; title = $event.detail.title; message = $event.detail.message; onConfirm = $event.detail.onConfirm; triggerEl = $event.target; $nextTick(() => { const btn = $el.querySelector('.btn-secondary'); if(btn) btn.focus(); })" @keydown.escape.window="show = false; setTimeout(() => triggerEl?.focus(), 0)" x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" :inert="!show">
    <div class="modal-overlay absolute inset-0" @click="show = false"></div>
    <div class="bg-white p-6 max-w-md w-full mx-4 relative shadow-2xl" dir="rtl" style="border-radius: var(--radius-xl);" @keydown.tab="$event.preventDefault(); focusNextInModal($event, $el)">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-full flex items-center justify-center" style="background: var(--warning-light);">
                <x-heroicon name="warning" style="color: var(--accent-dark);" />
            </div>
            <h3 class="text-lg font-extrabold" style="color: var(--text-primary);" x-text="title"></h3>
        </div>
        <p class="mb-6" style="color: var(--text-secondary);" x-text="message"></p>
        <div class="flex items-center gap-3 justify-end">
            <button @click="show = false; setTimeout(() => triggerEl?.focus(), 0)" class="btn btn-secondary">إلغاء</button>
            <button @click="show = false; if(onConfirm) onConfirm(); setTimeout(() => triggerEl?.focus(), 0)" class="btn btn-danger">تأكيد</button>
        </div>
    </div>
</div>

<!-- Keyboard Shortcuts Modal -->
<div x-data="keyboardHelpModal" @keyboard-help.window="show = true; triggerEl = $event.target; $nextTick(() => { const btn = $el.querySelector('.btn-ghost'); if(btn) btn.focus(); })" @keydown.escape.window="show = false; setTimeout(() => triggerEl?.focus(), 0)" x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" :inert="!show">
    <div class="modal-overlay absolute inset-0" @click="show = false"></div>
    <div class="bg-white p-6 max-w-lg w-full mx-4 relative shadow-2xl" dir="rtl" style="border-radius: var(--radius-xl);" @keydown.tab="$event.preventDefault(); focusNextInModal($event, $el)">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-extrabold" style="color: var(--text-primary);">اختصارات لوحة المفاتيح</h3>
            <button @click="show = false; setTimeout(() => triggerEl?.focus(), 0)" class="btn btn-ghost btn-icon">
                <x-heroicon name="close" />
            </button>
        </div>
        <div class="space-y-2">
            <div class="flex items-center justify-between p-3 rounded-xl" style="background: var(--surface-hover);"><span class="font-bold" style="color: var(--text-primary);">الأوامر السريعة</span><div class="flex gap-1"><kbd class="kbd">Ctrl</kbd><kbd class="kbd">K</kbd></div></div>
            <div class="flex items-center justify-between p-3 rounded-xl" style="background: var(--surface-hover);"><span class="font-bold" style="color: var(--text-primary);">الصفحة الرئيسية</span><div class="flex gap-1"><kbd class="kbd">Ctrl</kbd><kbd class="kbd">H</kbd></div></div>
            <div class="flex items-center justify-between p-3 rounded-xl" style="background: var(--surface-hover);"><span class="font-bold" style="color: var(--text-primary);">المستخدمون</span><div class="flex gap-1"><kbd class="kbd">Ctrl</kbd><kbd class="kbd">U</kbd></div></div>
            <div class="flex items-center justify-between p-3 rounded-xl" style="background: var(--surface-hover);"><span class="font-bold" style="color: var(--text-primary);">المعاملات</span><div class="flex gap-1"><kbd class="kbd">Ctrl</kbd><kbd class="kbd">T</kbd></div></div>
            <div class="flex items-center justify-between p-3 rounded-xl" style="background: var(--surface-hover);"><span class="font-bold" style="color: var(--text-primary);">الإعدادات</span><div class="flex gap-1"><kbd class="kbd">Ctrl</kbd><kbd class="kbd">S</kbd></div></div>
            <div class="flex items-center justify-between p-3 rounded-xl" style="background: var(--surface-hover);"><span class="font-bold" style="color: var(--text-primary);">المساعدة</span><div class="flex gap-1"><kbd class="kbd">?</kbd></div></div>
        </div>
    </div>
</div>

<x-admin.cmdk />

<div class="admin-shell">
    <!-- Mobile sidebar backdrop — only on <1024px when sidebar open -->
    <div x-show="sidebarOpen && !isDesktop" x-cloak class="sd-backdrop" @click="sidebarOpen = false" x-transition.opacity></div>

    <!-- Sidebar
         Mobile: overlay drawer with slide animation
         Desktop: sticky, width-collapsible (240px expanded / 72px rail)
    -->
    <aside x-bind:class="{
        'sd-open': !isDesktop && sidebarOpen,
        'sd-rail': isDesktop && !sidebarOpen
    }" class="sd-aside">
      <div class="sd-shell">
        <!-- Brand -->
        <div class="sd-brand flex items-center px-4 shrink-0" :class="(isDesktop && !sidebarOpen) ? 'justify-center' : 'justify-start'">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                <span class="flex items-center justify-center shrink-0 w-9 h-9">
                    <img src="/images/logo.svg" alt="صكك" class="w-full h-full object-contain">
                </span>
                <span x-show="sidebarOpen" x-transition.opacity class="flex flex-col min-w-0">
                    <span class="font-extrabold text-base leading-tight truncate" style="color: var(--sidebar-text-strong); letter-spacing:-0.03em;">صكك</span>
                    <span class="text-[9px] font-semibold tracking-[0.18em] truncate opacity-60" style="color: var(--sidebar-text);">لوحة الإدارة</span>
                </span>
            </a>
        </div>

        <!-- Nav -->
        <nav class="sd-nav flex-1" aria-label="القائمة الرئيسية">

          {{-- ===== الرئيسية ===== --}}
          <p class="sd-section-label" x-show="sidebarOpen">الرئيسية</p>
          <a href="{{ route('admin.dashboard') }}" class="sd-link {{ request()->routeIs('admin.dashboard') ? 'sd-link--active' : '' }}" aria-label="لوحة التحكم" title="لوحة التحكم" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
              <x-heroicon name="dashboard" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">لوحة التحكم</span>
          </a>

          {{-- ===== الإدارة ===== --}}
          <p class="sd-section-label" x-show="sidebarOpen">الإدارة</p>
          <a href="{{ route('admin.users') }}" class="sd-link {{ request()->routeIs('admin.users*') ? 'sd-link--active' : '' }}" aria-label="المستخدمون" title="المستخدمون" @if(request()->routeIs('admin.users*')) aria-current="page" @endif>
              <x-heroicon name="people" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">المستخدمون</span>
          </a>
          <a href="{{ route('admin.kyc.index') }}" class="sd-link {{ request()->routeIs('admin.kyc*') ? 'sd-link--active' : '' }}" aria-label="التحقق KYC" title="التحقق KYC" @if(request()->routeIs('admin.kyc*')) aria-current="page" @endif>
              <x-heroicon name="verified_user" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">التحقق KYC</span>
          </a>

          {{-- ===== المالية ===== --}}
          <p class="sd-section-label" x-show="sidebarOpen">المالية</p>
          <a href="{{ route('admin.transactions') }}" class="sd-link {{ request()->routeIs('admin.transactions*') ? 'sd-link--active' : '' }}" aria-label="المعاملات" title="المعاملات" @if(request()->routeIs('admin.transactions*')) aria-current="page" @endif>
              <x-heroicon name="receipt_long" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">المعاملات</span>
          </a>
          <a href="{{ route('admin.fees.index') }}" class="sd-link {{ request()->routeIs('admin.fees*') ? 'sd-link--active' : '' }}" aria-label="الرسوم" title="الرسوم" @if(request()->routeIs('admin.fees*')) aria-current="page" @endif>
              <x-heroicon name="percent" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">الرسوم</span>
          </a>
          <a href="{{ route('admin.exchange-rates.index') }}" class="sd-link {{ request()->routeIs('admin.exchange-rates*') ? 'sd-link--active' : '' }}" aria-label="أسعار الصرف" title="أسعار الصرف" @if(request()->routeIs('admin.exchange-rates*')) aria-current="page" @endif>
              <x-heroicon name="currency_exchange" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">أسعار الصرف</span>
          </a>
          {{-- الذهب — merged dashboard --}}
          <a href="{{ route('admin.gold.index') }}" class="sd-link {{ request()->routeIs('admin.gold*') ? 'sd-link--active' : '' }}" aria-label="الذهب" title="الذهب" @if(request()->routeIs('admin.gold*')) aria-current="page" @endif>
              <x-heroicon name="monetization_on" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen" class="flex-1 text-right">الذهب</span>
          </a>

          {{-- ===== شركاء الأعمال ===== --}}
          <p class="sd-section-label" x-show="sidebarOpen">شركاء الأعمال</p>
          {{-- الوكلاء --}}
          <x-admin.sidebar.nav-group icon="storefront" label="الوكلاء" routeIs="admin.agents*">
              <x-admin.sidebar.nav-item href="{{ route('admin.agents.index') }}" icon="list" label="جميع الوكلاء" routeIs="admin.agents.index" />
              <x-admin.sidebar.nav-item href="{{ route('admin.agents.documents') }}" icon="description" label="المستندات" routeIs="admin.agents.documents*" />
          </x-admin.sidebar.nav-group>
          {{-- التجار --}}
          <x-admin.sidebar.nav-group icon="store" label="التجار" routeIs="admin.merchants*">
              <x-admin.sidebar.nav-item href="{{ route('admin.merchants.index') }}" icon="list" label="جميع التجار" routeIs="admin.merchants.index" />
              <x-admin.sidebar.nav-item href="{{ route('admin.merchants.documents') }}" icon="description" label="المستندات" routeIs="admin.merchants.documents*" />
          </x-admin.sidebar.nav-group>
          {{-- الشركات --}}
          <x-admin.sidebar.nav-group icon="apartment" label="الشركات" routeIs="admin.companies*">
              <x-admin.sidebar.nav-item href="{{ route('admin.companies.index') }}" icon="list" label="جميع الشركات" routeIs="admin.companies.index" />
              <x-admin.sidebar.nav-item href="{{ route('admin.companies.documents') }}" icon="description" label="المستندات" routeIs="admin.companies.documents*" />
          </x-admin.sidebar.nav-group>

          {{-- ===== الدعم ===== --}}
          <p class="sd-section-label" x-show="sidebarOpen">الدعم</p>
          <a href="{{ route('admin.support.index') }}" class="sd-link {{ request()->routeIs('admin.support.*') ? 'sd-link--active' : '' }}" aria-label="تذاكر الدعم" title="تذاكر الدعم" @if(request()->routeIs('admin.support.*')) aria-current="page" @endif>
              <x-heroicon name="support_agent" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">تذاكر الدعم</span>
          </a>
          {{-- الدردشة الحية --}}
          <a href="{{ route('admin.chat.index') }}" class="sd-link {{ request()->routeIs('admin.chat*') ? 'sd-link--active' : '' }}" aria-label="الدردشة الحية" title="الدردشة الحية" @if(request()->routeIs('admin.chat*')) aria-current="page" @endif>
              <x-heroicon name="forum" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen" class="flex-1 text-right">الدردشة الحية</span>
              <span x-show="sidebarOpen" id="chat-nav-unread" class="badge badge-danger" style="display:none;min-width:18px;text-align:center"></span>
          </a>
          {{-- الإشعارات والتسويق --}}
          <a href="{{ route('admin.notifications.index') }}" class="sd-link {{ request()->routeIs('admin.notifications*') ? 'sd-link--active' : '' }}" aria-label="الإشعارات والتسويق" title="الإشعارات والتسويق" @if(request()->routeIs('admin.notifications*')) aria-current="page" @endif>
              <x-heroicon name="campaign" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">الإشعارات والتسويق</span>
          </a>

          {{-- ===== النظام ===== --}}
          <p class="sd-section-label" x-show="sidebarOpen">النظام</p>
          {{-- التكاملات --}}
          <a href="{{ route('admin.integrations.overview') }}" class="sd-link {{ request()->routeIs('admin.integrations*') ? 'sd-link--active' : '' }}" aria-label="التكاملات" title="التكاملات" @if(request()->routeIs('admin.integrations*')) aria-current="page" @endif>
              <x-heroicon name="extension" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">التكاملات</span>
          </a>
          {{-- إعدادات النظام --}}
          <x-admin.sidebar.nav-group icon="tune" label="إعدادات النظام" routeIs="admin.system.*">
              <x-admin.sidebar.nav-item href="{{ route('admin.system.channels') }}" icon="notifications_active" label="قنوات الإشعارات" routeIs="admin.system.channels*" />
              <x-admin.sidebar.nav-item href="{{ route('admin.system.messages') }}" icon="sms" label="قوالب الرسائل" routeIs="admin.system.messages*" />
              <x-admin.sidebar.nav-item href="{{ route('admin.system.app-update') }}" icon="system_update" label="تحديث التطبيق" routeIs="admin.system.app-update*" />
              <x-admin.sidebar.nav-item href="{{ route('admin.system.health') }}" icon="monitor_heart" label="صحة النظام" routeIs="admin.system.health*" />
              <x-admin.sidebar.nav-item href="{{ route('admin.system.backup') }}" icon="backup" label="النسخ الاحتياطي" routeIs="admin.system.backup*" />
              <x-admin.sidebar.nav-item href="{{ route('admin.system.support') }}" icon="support_agent" label="الدعم الفني" routeIs="admin.system.support*" />
          </x-admin.sidebar.nav-group>
          {{-- سجل النشاطات --}}
          <a href="{{ route('admin.audit.index') }}" class="sd-link {{ request()->routeIs('admin.audit*') ? 'sd-link--active' : '' }}" aria-label="سجل النشاطات" title="سجل النشاطات" @if(request()->routeIs('admin.audit*')) aria-current="page" @endif>
              <x-heroicon name="history" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">سجل النشاطات</span>
          </a>
          {{-- الإعدادات --}}
          <a href="{{ route('admin.settings') }}" class="sd-link {{ request()->routeIs('admin.settings*') ? 'sd-link--active' : '' }}" aria-label="الإعدادات" title="الإعدادات" @if(request()->routeIs('admin.settings*')) aria-current="page" @endif>
              <x-heroicon name="settings" class="w-5 h-5 shrink-0" />
              <span x-show="sidebarOpen">الإعدادات</span>
          </a>
        </nav>

        {{-- Account card — moved here from the topbar --}}
        @php
            $__su = auth()->user();
            $__sfn = $__su->first_name ?? 'مدير';
            $__sln = $__su->last_name ?? '';
            $__sInitials = mb_substr($__sfn, 0, 1) . ($__sln ? mb_substr($__sln, 0, 1) : '');
            $__sName = trim($__sfn . ' ' . $__sln) ?: 'مدير النظام';
        @endphp
        <div class="sd-footer" x-show="sidebarOpen" x-transition.opacity>
            <a href="{{ route('admin.profile') }}" class="sd-footer-link" title="الملف الشخصي">
                <span class="sd-footer-avatar">{{ $__sInitials }}</span>
                <div class="sd-footer-info">
                    <div class="sd-footer-name">{{ $__sName }}</div>
                    <div class="sd-footer-role">مدير النظام</div>
                </div>
            </a>
            <form method="POST" action="{{ route('admin.logout') }}" class="sd-footer-logout">
                @csrf
                <button type="submit" class="sd-footer-btn" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                    <x-heroicon name="logout" class="w-5 h-5 shrink-0" />
                </button>
            </form>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col min-w-0 flex-1 overflow-hidden sd-main">
        <!-- Header — recode: clean minimal topbar, gold accents, no red -->
        @php
            $__u = auth()->user();
            $__fn = $__u->first_name ?? 'مدير';
            $__ln = $__u->last_name ?? '';
            $__initials = mb_substr($__fn, 0, 1) . ($__ln ? mb_substr($__ln, 0, 1) : '');
            $__fullName = trim($__fn . ' ' . $__ln);
            $__alerts = \App\Models\AdminAlert::forAdmin()->latest()->limit(10)->get();
            $__unread = \App\Models\AdminAlert::forAdmin()->whereNull('read_at')->count();
            $__dotColor = ['error' => '#dc2626', 'warning' => '#d97706', 'success' => '#16a34a', 'info' => 'var(--accent)'];
        @endphp
        <header class="nv-bar flex items-center shrink-0 z-30" style="background:#fff;">
            {{-- Left: toggle + breadcrumb --}}
            <div class="flex items-center gap-2 min-w-0 shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="nv-toggle" aria-label="تبديل القائمة">
                    <x-heroicon name="menu" class="w-5 h-5 shrink-0" />
                </button>
                <div class="nv-bread">
                    <span class="nv-bread-current">@yield('title', 'لوحة التحكم')</span>
                    <nav class="nv-bread-path" aria-label="مسار الصفحة">
                        <a href="{{ route('admin.dashboard') }}">الرئيسية</a>
                        @yield('breadcrumbs')
                    </nav>
                </div>
            </div>

            {{-- Center: search (desktop only) --}}
            <div class="hidden lg:flex flex-1 justify-center px-3 min-w-0">
                <x-admin.navbar.search />
            </div>

            {{-- Right: actions --}}
            <div class="flex items-center gap-0.5 shrink-0">
                @hasSection('topbar-actions')
                    <div class="hidden sm:flex items-center gap-1.5 ml-1">@yield('topbar-actions')</div>
                    <div class="nv-sep"></div>
                @endif

                {{-- Notifications --}}
                <div class="relative" x-data="dropdown">
                    <button @click="toggle()" class="nv-icon-btn" aria-label="الإشعارات">
                        <x-heroicon name="notifications_none" />
                        @if ($__unread > 0)
                            <span class="nv-dot"></span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute left-0 mt-2 w-80 bg-white overflow-hidden z-50"
                         style="border-radius: var(--radius-lg); box-shadow: 0 20px 44px -14px rgba(16,18,22,0.24), 0 6px 16px -8px rgba(16,18,22,0.10);">
                        <div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid var(--border-light);">
                            <span class="text-sm font-extrabold" style="color: var(--text-primary);">الإشعارات</span>
                            @if ($__unread > 0)
                                <span class="text-[10px] font-bold px-1.5 py-0.5" style="color:var(--gold,#B58A3C);background:var(--accent-soft);border-radius:999px;">{{ $__unread }} جديد</span>
                            @endif
                        </div>
                        <div style="max-height:340px; overflow-y:auto;">
                            @forelse ($__alerts as $__a)
                                <div style="display:flex;gap:.6rem;align-items:flex-start;padding:.65rem 1rem;border-bottom:1px solid var(--border-light);{{ $__a->read_at ? '' : 'background:var(--accent-soft);' }}{{ $__a->link ? 'cursor:pointer;' : '' }}" data-id="{{ $__a->id }}" @if ($__a->link) onclick="window.location.href='{{ $__a->link }}'" @endif>
                                    <span style="flex-shrink:0;width:7px;height:7px;border-radius:50%;margin-top:5px;background:{{ $__dotColor[$__a->type] ?? 'var(--accent)' }};"></span>
                                    <div style="min-width:0;flex:1;">
                                        <div style="font-size:.82rem;font-weight:800;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $__a->title }}</div>
                                        <div style="font-size:.7rem;color:var(--text-secondary);margin-top:2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $__a->message }}</div>
                                        <div style="font-size:.6rem;color:var(--text-muted);margin-top:3px;">{{ $__a->created_at->diffForHumans() }}</div>
                                    </div>
                                    <button type="button" onclick="event.stopPropagation();dismissAdminAlert({{ $__a->id }},this)" style="flex-shrink:0;border:none;background:transparent;color:var(--text-muted);cursor:pointer;padding:2px;border-radius:4px;" title="حذف">
                                        <x-heroicon name="close" class="w-4 h-4" />
                                    </button>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-center">
                                    <x-heroicon name="notifications_off" class="w-8 h-8" style="color:var(--border-strong);" />
                                    <p style="margin-top:0.5rem;font-size:0.85rem;color:var(--text-muted);">لا إشعارات جديدة</p>
                                </div>
                            @endforelse
                        </div>
                        @if ($__alerts->isNotEmpty())
                            <div style="border-top:1px solid var(--border-light);padding:0.5rem;text-align:center;">
                                <button type="button" onclick="markAllAdminAlertsRead()" style="font-size:.7rem;font-weight:700;color:var(--gold,#B58A3C);background:none;border:none;cursor:pointer;font-family:inherit;">
                                    تحديد الكل كمقروء
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Help --}}
                <button @click="$dispatch('keyboard-help')" class="nv-icon-btn" aria-label="اختصارات لوحة المفاتيح">
                    <x-heroicon name="keyboard" class="w-5 h-5 shrink-0" />
                </button>

                {{-- Profile chip moved to the sidebar account card --}}
            </div>
        </header>

        <!-- Main -->
        <main class="flex-1 overflow-y-auto" id="mainContent" dir="rtl" style="padding:var(--space-lg); overscroll-behavior:contain;">
            <div dir="rtl">
            @if(session('success'))
                <script>document.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', message: '{{ session('success') }}'}})));</script>
            @endif
            @if(session('error'))
                <script>document.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'error', message: '{{ session('error') }}'}})));</script>
            @endif

            {{-- Permission Request Banners --}}
            <div x-data="permissionPrompt">
                {{-- Notification permission banner --}}
                <div x-show="showNotifBanner" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                     style="display:flex;align-items:center;gap:1rem;padding:0.85rem 1.25rem;margin-bottom:1rem;background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-lg);"
                     role="status" aria-live="polite">
                    <span style="flex-shrink:0;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--accent-soft);color:var(--gold);">
                        <x-heroicon name="notifications_none" class="w-5 h-5" />
                    </span>
                    <p style="flex:1;font-size:0.85rem;font-weight:600;color:var(--text-primary);margin:0;">
                        فعل الإشعارات ليصلك التنبيهات حتى خارج لوحة التحكم
                    </p>
                    <button @click="requestNotif()" class="btn btn-success btn-sm" style="white-space:nowrap;">تفعيل</button>
                    <button @click="dismissNotif()" class="btn btn-ghost btn-sm" style="white-space:nowrap;">لا الآن</button>
                </div>

                {{-- Geolocation permission banner --}}
                <div x-show="showGeoBanner" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                     style="display:flex;align-items:center;gap:1rem;padding:0.85rem 1.25rem;margin-bottom:1rem;background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-lg);"
                     role="status" aria-live="polite">
                    <span style="flex-shrink:0;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--accent-soft);color:var(--gold);">
                        <x-heroicon name="location_on" class="w-5 h-5" />
                    </span>
                    <p style="flex:1;font-size:0.85rem;font-weight:600;color:var(--text-primary);margin:0;">
                        فعل الموقع للاستفادة من الخدمات المكانية
                    </p>
                    <button @click="requestGeo()" class="btn btn-success btn-sm" style="white-space:nowrap;">تفعيل</button>
                    <button @click="dismissGeo()" class="btn btn-ghost btn-sm" style="white-space:nowrap;">لا الآن</button>
                </div>
            </div>

            @yield('content')
            </div>
        </main>

        {{-- Footer --}}
        <footer class="shrink-0 text-center py-4" style="padding-inline:var(--space-lg);" style="border-top:1px solid var(--border-light);">
            <p class="text-xs" style="color: var(--text-muted);">
                © {{ date('Y') }} صَكّ — جميع الحقوق المحفوظة
                <span class="mx-1.5" style="color: var(--border-strong);">·</span>
                <span>الإصدار {{ config('app.version', '1.0.0') }}</span>
                <span class="mx-1.5" style="color: var(--border-strong);">·</span>
                <a href="{{ route('admin.system.support') }}" style="color: var(--text-muted); text-decoration:none;">الدعم الفني</a>
                <span class="mx-1.5" style="color: var(--border-strong);">·</span>
                <span>بتقنية Lorka AI</span>
            </p>
        </footer>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        // ── Layout ──────────────────────────────────────────────────
        Alpine.data('sidebarLayout', () => ({
            sidebarOpen: (localStorage.getItem('sakk_sb') ?? '1') === '1',
            isDesktop: false,
            init() {
                this.isDesktop = window.innerWidth >= 1024;
                this.$watch('sidebarOpen', v => localStorage.setItem('sakk_sb', v ? '1' : '0'));
                window.addEventListener('resize', () => {
                    this.isDesktop = window.innerWidth >= 1024;
                });
            }
        }));

        // ── Modals ───────────────────────────────────────────────────
        // Global focus-trap utility for modals
        window.focusNextInModal = function(event, modalEl) {
            const focusableSelector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
            const focusable = Array.from(modalEl.querySelectorAll(focusableSelector));
            if (focusable.length === 0) return;

            const activeEl = document.activeElement;
            const activeIndex = focusable.indexOf(activeEl);
            const isShift = event.shiftKey;
            let nextIndex = isShift ? activeIndex - 1 : activeIndex + 1;

            if (nextIndex < 0) nextIndex = focusable.length - 1;
            if (nextIndex >= focusable.length) nextIndex = 0;

            focusable[nextIndex].focus();
        };

        Alpine.data('confirmModal', () => ({
            show: false, title: '', message: '', onConfirm: null, triggerEl: null
        }));
        Alpine.data('keyboardHelpModal', () => ({
            show: false, triggerEl: null
        }));
        Alpine.data('commandPalette', () => ({
            show: false,
            query: '',
            selectedIndex: 0,
            get filtered() {
                return this.commands.filter(c => {
                    if (!this.query) return true;
                    const q = this.query.trim().toLowerCase();
                    return c.label.toLowerCase().includes(q)
                        || c.id.toLowerCase().includes(q);
                });
            },
            init() {
                // Listen on window for toggle-cmdk event (search click, ⌘K shortcut, etc.)
                window.addEventListener('toggle-cmdk', (e) => this.toggle());
                // Close on Escape using event delegation
                window.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.show) this.close();
                });
            },
            toggle() {
                this.show = !this.show;
                if (this.show) {
                    this.query = '';
                    this.selectedIndex = 0;
                    this.$nextTick(() => {
                        if (this.$refs.cmdkInput) this.$refs.cmdkInput.focus();
                    });
                }
            },
            close() { this.show = false; },
            getSvg(n) {
                const p = {
                    dashboard:'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
                    people:'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                    verified_user:'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
                    receipt_long:'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
                    wallet:'M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3',
                    settings:'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z',
                    bell:'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
                    swap_horiz:'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
                };
                const d = p[n] || 'M8.25 4.5l7.5 7.5-7.5 7.5';
                return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="' + d + '"/></svg>';
            },
            next() {
                if (this.selectedIndex < this.filtered.length - 1) this.selectedIndex++;
            },
            prev() {
                if (this.selectedIndex > 0) this.selectedIndex--;
            },
            exec(i) {
                const item = this.filtered[i];
                if (!item) return;
                if (item.route) window.location.href = item.route;
            },
            get commands() {
                const s = this.getSvg.bind(this);
                return [
                    { id: 'dashboard',    svg: s('dashboard'),    label: 'لوحة التحكم',   route: '{{ route('admin.dashboard') }}',    shortcut: 'H' },
                    { id: 'users',        svg: s('people'),       label: 'المستخدمون',     route: '{{ route('admin.users') }}',        shortcut: 'U' },
                    { id: 'kyc',          svg: s('verified_user'),label: 'KYC',            route: '{{ route('admin.kyc.index') }}',   shortcut: 'K' },
                    { id: 'transactions', svg: s('receipt_long'), label: 'المعاملات',      route: '{{ route('admin.transactions') }}', shortcut: 'T' },
                    { id: 'wallets',      svg: s('wallet'),       label: 'المحافظ',        route: '{{ route('admin.users') }}',        shortcut: 'W' },
                    { id: 'gold',         svg: s('monetization_on'), label: 'الذهب',         route: '{{ route('admin.gold.index') }}',     shortcut: 'C' },
                    { id: 'settings',     svg: s('settings'),     label: 'الإعدادات',      route: '{{ route('admin.settings') }}',     shortcut: 'S' },
                    { id: 'notifications',svg: s('bell'),         label: 'الإشعارات',      route: '{{ route('admin.notifications.index') }}',shortcut: 'N' },
                ];
            }
        }));

        // ── Sidebar ──────────────────────────────────────────────────
        Alpine.data('sidebarNav', () => ({
            open: false,
            init() {
                if (this.$el.dataset.initialOpen === 'true') this.open = true;
            }
        }));

        // ── Dropdowns ────────────────────────────────────────────────
        Alpine.data('dropdown', () => ({
            open: false,
            toggle() { this.open = !this.open; }
        }));

        // ── Dashboard ────────────────────────────────────────────────
        Alpine.data('dashboardTabs', () => ({
            tab: 'transactions'
        }));
        Alpine.data('balancesCard', () => ({
            masked: false,
            toggleMask() { this.masked = !this.masked; }
        }));
        Alpine.data('pendingKyc', () => ({}));

        // ── Core toasts (legacy) ─────────────────────────────────────
        Alpine.data('toastSystem', () => ({
            toasts: [],
            addToast(toast) {
                const id = Date.now();
                this.toasts.push({ id, ...toast });
                setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 5000);
            }
        }));

        // ── Page-level toggles ───────────────────────────────────────
        Alpine.data('agentShow', () => ({ showDelete: false }));
        Alpine.data('merchantShow', () => ({ showDelete: false, showApiKeys: false }));
        Alpine.data('txShow', () => ({ showMetadata: false }));

        // ── Notifications page ───────────────────────────────────────
        Alpine.data('notificationsApp', () => ({
            audience: 'all', ntitle: '', nbody: '', schedule: false,
            counts: { all: 0, active: 0, kyc_verified: 0, inactive: 0 },
            init() {
                if (this.$el.dataset.counts) this.counts = JSON.parse(this.$el.dataset.counts);
            },
            get reach() { return this.audience === 'specific' ? null : (this.counts[this.audience] ?? 0); }
        }));

        // ── Agent documents ──────────────────────────────────────────
        Alpine.data('agentDocReject', () => ({
            rejectOpen: false, docId: '', agentName: '', docType: '',
            openReject(id, name, type) { this.docId = id; this.agentName = name; this.docType = type; this.rejectOpen = true; }
        }));

        // ── Fees ─────────────────────────────────────────────────────
        Alpine.data('feeTabs', () => ({ activeTab: 'deposit' }));
        Alpine.data('feeCard', () => ({
            feeType: 'percentage',
            init() { this.feeType = this.$el.dataset.feeType || 'percentage'; }
        }));

        // ── Gold prices ──────────────────────────────────────────────
        Alpine.data('goldAutoSync', () => ({
            auto: false,
            init() { this.auto = this.$el.dataset.auto === 'true'; }
        }));
        Alpine.data('karatCard', () => ({
            buy: 0, sell: 0,
            init() {
                this.buy = parseFloat(this.$el.dataset.buy) || 0;
                this.sell = parseFloat(this.$el.dataset.sell) || 0;
            },
            get spread() { return this.sell > 0 ? (((this.buy - this.sell) / this.sell) * 100) : 0; }
        }));

        // ── Permission prompts (notifications + geolocation) ─────────
        Alpine.data('permissionPrompt', () => ({
            notifState: 'prompt',
            geoState: 'prompt',
            init() {
                // Notification permission
                if (window.Notification) {
                    this.notifState = Notification.permission === 'granted' ? 'granted' : 
                                      Notification.permission === 'denied' ? 'denied' : 'prompt';
                } else {
                    this.notifState = 'denied';
                }
                // localStorage dismissal (7-day expiry)
                const notifDismissed = localStorage.getItem('sakk_notif_dismissed');
                if (notifDismissed && Date.now() < parseInt(notifDismissed)) {
                    this.notifState = 'dismissed';
                }
                const geoDismissed = localStorage.getItem('sakk_geo_dismissed');
                if (geoDismissed && Date.now() < parseInt(geoDismissed)) {
                    this.geoState = 'dismissed';
                }
                // Check geolocation permission via Permissions API
                if (navigator.permissions) {
                    navigator.permissions.query({name: 'geolocation'}).then(result => {
                        if (result.state === 'granted') this.geoState = 'granted';
                        else if (result.state === 'denied') this.geoState = 'denied';
                    }).catch(() => {});
                }
            },
            get showNotifBanner() {
                return this.notifState === 'prompt';
            },
            get showGeoBanner() {
                return this.geoState === 'prompt';
            },
            requestNotif() {
                Notification.requestPermission().then(perm => {
                    this.notifState = perm === 'granted' ? 'granted' : 'denied';
                });
            },
            requestGeo() {
                navigator.geolocation.getCurrentPosition(
                    () => { this.geoState = 'granted'; },
                    () => { this.geoState = 'denied'; },
                    { timeout: 5000 }
                );
            },
            dismissNotif() {
                localStorage.setItem('sakk_notif_dismissed', String(Date.now() + 7*24*60*60*1000));
                this.notifState = 'dismissed';
            },
            dismissGeo() {
                localStorage.setItem('sakk_geo_dismissed', String(Date.now() + 7*24*60*60*1000));
                this.geoState = 'dismissed';
            }
        }));
    });

    document.addEventListener('keydown', (e) => {
        // Use e.code (physical key position), NOT e.key — with an Arabic keyboard layout
        // e.key returns an Arabic letter so 'k'/'h'/… never match and the shortcuts die.
        if (e.ctrlKey || e.metaKey) {
            switch (e.code) {
                case 'KeyK': e.preventDefault(); window.dispatchEvent(new CustomEvent('toggle-cmdk')); return;
                case 'KeyH': e.preventDefault(); window.location.href = '{{ route('admin.dashboard') }}'; return;
                case 'KeyU': e.preventDefault(); window.location.href = '{{ route('admin.users') }}'; return;
                case 'KeyT': e.preventDefault(); window.location.href = '{{ route('admin.transactions') }}'; return;
                case 'KeyS': e.preventDefault(); window.location.href = '{{ route('admin.settings') }}'; return;
            }
            return;
        }
        // Escape closes any open modal.
        if (e.key === 'Escape') {
            // Belt-and-suspenders: direct DOM hide + Alpine reactivity.
            ['keyboardHelpModal','confirmModal'].forEach(function(name) {
                var el = document.querySelector('[x-data="'+name+'"]');
                if (!el) return;
                el.style.display = 'none';                     // immediate hide
                try { Alpine.$data(el).show = false; } catch(e) {}  // reactive
            });
            return;
        }
        // "?" (Shift+Slash) opens the shortcuts modal — only when not typing in a field.
        var _tag = (document.activeElement && document.activeElement.tagName) || '';
        if (_tag !== 'INPUT' && _tag !== 'TEXTAREA' && _tag !== 'SELECT'
            && (e.key === '?' || (e.shiftKey && e.code === 'Slash'))) {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('keyboard-help'));
        }
    });

    function confirmAction(title, message, callback) {
        window.dispatchEvent(new CustomEvent('confirm-modal', { detail: { title, message, onConfirm: callback } }));
    }

    function setLoading(btn, loading = true) {
        if (loading) {
            if (btn.classList.contains('sakk-btn')) btn.classList.add('sakk-btn--loading');
            else btn.classList.add('btn-loading');
            btn.disabled = true;
        } else {
            btn.classList.remove('btn-loading', 'sakk-btn--loading');
            btn.disabled = false;
        }
    }

    function exportToCSV(filename, headers, rows) {
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }

    function formatNumber(num) {
        if (num >= 1000000) return (num / 1000000).toFixed(2) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(2) + 'K';
        return num.toFixed(2);
    }

    function togglePassword(id) {
        const input = document.getElementById(id);
        if (input) {
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'تم النسخ!' } }));
        });
    }

    // ── Admin alert bell actions ──────────────────────────────────────────
    function _adminAlertCsrf() {
        return document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
    }
    function dismissAdminAlert(id, el) {
        fetch('{{ route('admin.alerts.dismiss', ['alert' => '__ID__']) }}'.replace('__ID__', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': _adminAlertCsrf(), 'Accept': 'application/json' },
        }).then(function (r) {
            if (r.ok) { var item = el.closest('.admin-alert-item'); if (item) item.remove(); }
        });
    }
    function markAllAdminAlertsRead() {
        fetch('{{ route('admin.alerts.read-all') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': _adminAlertCsrf(), 'Accept': 'application/json' },
        }).then(function (r) { if (r.ok) { window.location.reload(); } });
    }
</script>

@stack('scripts')
</body>
</html>
