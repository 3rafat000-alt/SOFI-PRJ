@php
    $__u = auth()->user();
    $__fn = $__u->first_name ?? 'مدير';
    $__ln = $__u->last_name ?? '';
    $__initials = mb_substr($__fn, 0, 1) . ($__ln ? mb_substr($__ln, 0, 1) : '');
    $__fullName = trim($__fn . ' ' . $__ln);
@endphp

{{-- Mobile backdrop --}}
<div x-show="sidebarOpen && !isDesktop" x-cloak
     class="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm lg:hidden"
     @click="sidebarOpen = false"
     x-transition.opacity></div>

<aside x-data="sidebarMenu"
       @sidebar:toggle.window="toggle()"
       x-bind:class="{
           'sd-open': !isDesktop && sidebarOpen,
           'sd-rail': isDesktop && !sidebarOpen
       }"
       class="sd-aside">
  <div class="sd-shell">

    {{-- ═══ BRAND ═══ --}}
    <div class="sd-brand"
         :class="(isDesktop && !sidebarOpen) ? 'sd-brand--center' : ''">
        <a href="{{ route('admin.dashboard') }}" class="sd-brand-link">
            <span class="sd-brand-mark">
                <span class="sd-brand-logo">ص</span>
            </span>
            <span x-show="sidebarOpen" x-transition.opacity class="sd-brand-text">
                <span class="sd-brand-name">صك</span>
                <span class="sd-brand-sub">لوحة الإدارة</span>
            </span>
        </a>
    </div>

    {{-- ═══ NAV ═══ --}}
    <nav class="sd-nav" aria-label="القائمة الرئيسية">

        {{-- الرئيسية --}}
        <x-admin.sidebar.section label="الرئيسية" />
        <x-admin.sidebar.nav-item href="{{ route('admin.dashboard') }}"
                                   icon="dashboard"
                                   label="لوحة التحكم"
                                   route="admin.dashboard" />

        {{-- المستخدمون --}}
        <x-admin.sidebar.section label="المستخدمون" />
        <x-admin.sidebar.nav-item href="{{ route('admin.users') }}"
                                   icon="people"
                                   label="جميع المستخدمين"
                                   route="admin.users*" />
        <x-admin.sidebar.nav-item href="{{ route('admin.users', ['kyc_status' => 'submitted']) }}"
                                   icon="verified_user"
                                   label="التحقق KYC"
                                   route="admin.users*" />

        {{-- المالية --}}
        <x-admin.sidebar.section label="المالية" />
        <x-admin.sidebar.nav-item href="{{ route('admin.transactions') }}"
                                   icon="receipt_long"
                                   label="المعاملات"
                                   route="admin.transactions*" />
        <x-admin.sidebar.nav-item href="{{ route('admin.withdrawals') }}"
                                   icon="account_balance_wallet"
                                   label="السحوبات"
                                   route="admin.withdrawals*" />
        <x-admin.sidebar.nav-item href="{{ route('admin.fees') }}"
                                   icon="percent"
                                   label="الرسوم"
                                   route="admin.fees*" />
        <x-admin.sidebar.nav-group icon="monetization_on"
                                    label="الذهب"
                                    route="admin.gold*"
                                    :initialOpen="request()->routeIs('admin.gold*')">
            <x-admin.sidebar.nav-item href="{{ route('admin.gold.prices') }}"
                                       icon="price_change"
                                       label="أسعار الذهب"
                                       route="admin.gold.prices" />
            <x-admin.sidebar.nav-item href="{{ route('admin.gold.transactions') }}"
                                       icon="receipt"
                                       label="المعاملات"
                                       route="admin.gold.transactions" />
        </x-admin.sidebar.nav-group>

        {{-- شركاء الأعمال --}}
        <x-admin.sidebar.section label="شركاء الأعمال" />
        <x-admin.sidebar.nav-group icon="storefront"
                                    label="الوكلاء"
                                    route="admin.agents*"
                                    :initialOpen="request()->routeIs('admin.agents*')">
            <x-admin.sidebar.nav-item href="{{ route('admin.agents.index') }}"
                                       icon="list"
                                       label="جميع الوكلاء"
                                       route="admin.agents.index" />
            <x-admin.sidebar.nav-item href="{{ route('admin.agents.documents') }}"
                                       icon="description"
                                       label="المستندات"
                                       route="admin.agents.documents*" />
        </x-admin.sidebar.nav-group>
        <x-admin.sidebar.nav-group icon="store"
                                    label="التجار"
                                    route="admin.merchants*"
                                    :initialOpen="request()->routeIs('admin.merchants*')">
            <x-admin.sidebar.nav-item href="{{ route('admin.merchants.index') }}"
                                       icon="list"
                                       label="جميع التجار"
                                       route="admin.merchants.index" />
            <x-admin.sidebar.nav-item href="{{ route('admin.merchants.documents') }}"
                                       icon="description"
                                       label="المستندات"
                                       route="admin.merchants.documents*" />
        </x-admin.sidebar.nav-group>
        <x-admin.sidebar.nav-group icon="apartment"
                                    label="الشركات"
                                    route="admin.companies*"
                                    :initialOpen="request()->routeIs('admin.companies*')">
            <x-admin.sidebar.nav-item href="{{ route('admin.companies.index') }}"
                                       icon="list"
                                       label="جميع الشركات"
                                       route="admin.companies.index" />
            <x-admin.sidebar.nav-item href="{{ route('admin.companies.documents') }}"
                                       icon="description"
                                       label="المستندات"
                                       route="admin.companies.documents*" />
        </x-admin.sidebar.nav-group>

        {{-- الدعم والاتصالات --}}
        <x-admin.sidebar.section label="الدعم والاتصالات" />
        <x-admin.sidebar.nav-item href="{{ route('admin.support.index') }}"
                                   icon="support_agent"
                                   label="تذاكر الدعم"
                                   route="admin.support.*" />
        <x-admin.sidebar.nav-item href="{{ route('admin.chat.index') }}"
                                   icon="forum"
                                   label="الدردشة الحية"
                                   route="admin.chat*" />
        <x-admin.sidebar.nav-item href="{{ route('admin.notifications.index') }}"
                                   icon="campaign"
                                   label="الإشعارات"
                                   route="admin.notifications*" />

        {{-- النظام --}}
        <x-admin.sidebar.section label="النظام" />
        <x-admin.sidebar.nav-item href="{{ route('admin.settings') }}"
                                   icon="settings"
                                   label="الإعدادات"
                                   route="admin.settings*" />
        <x-admin.sidebar.nav-item href="{{ route('admin.integrations.overview') }}"
                                   icon="extension"
                                   label="التكاملات"
                                   route="admin.integrations*" />
        <x-admin.sidebar.nav-item href="{{ route('admin.audit.index') }}"
                                   icon="history"
                                   label="سجل النشاطات"
                                   route="admin.audit*" />
        <x-admin.sidebar.nav-group icon="tune"
                                    label="إعدادات النظام"
                                    route="admin.system*"
                                    :initialOpen="request()->routeIs('admin.system*')">
            <x-admin.sidebar.nav-item href="{{ route('admin.system.channels') }}"
                                       icon="notifications_active"
                                       label="قنوات الإشعارات"
                                       route="admin.system.channels*" />
            <x-admin.sidebar.nav-item href="{{ route('admin.system.health') }}"
                                       icon="monitor_heart"
                                       label="صحة النظام"
                                       route="admin.system.health*" />
            <x-admin.sidebar.nav-item href="{{ route('admin.system.backup') }}"
                                       icon="backup"
                                       label="النسخ الاحتياطي"
                                       route="admin.system.backup*" />
        </x-admin.sidebar.nav-group>
    </nav>

    {{-- ═══ PROFILE CARD (clickable wrapper → admin/profile, logout independent) ═══ --}}
    <div class="sd-footer" x-show="sidebarOpen">
        <a href="{{ route('admin.profile') }}" class="sd-footer-link">
            <span class="sd-footer-overlay"></span>
            <span class="sd-footer-avatar">
                <span>{{ $__initials }}</span>
            </span>
            <span class="sd-footer-info">
                <span class="sd-footer-name">{{ $__fullName }}</span>
                <span class="sd-footer-role">مدير النظام</span>
            </span>
        </a>
        <form method="POST" action="{{ route('admin.logout') }}" class="sd-footer-logout" @click.stop>
            @csrf
            <button type="submit" class="sd-footer-btn" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                <x-heroicon name="logout"  />
            </button>
        </form>
    </div>
  </div>
</aside>
