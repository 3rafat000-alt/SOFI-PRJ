{{--
    SAKK · صك — System Alerts & Health Banner
    ────────────────────────────────────────────────
    Slim banners for urgent notifications.
    Alpine dismiss with local dismissed[] array.
    Soft brand hues · transparent background.
--}}
@php
    $pendingKyc = $st['pending_kyc'] ?? 0;
    $pendingTix = $pendingTickets ?? 0;
    $hasAlerts  = ($pendingKyc > 0 || $pendingTix > 0);
@endphp

<div x-data="{ dismissed: [] }" style="display:flex;flex-direction:column;gap:var(--space-sm);">

    {{-- Pending KYC --}}
    @if($pendingKyc > 0)
    <div class="dash-alert-banner" x-show="!dismissed.includes('kyc')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>
            <strong>{{ $pendingKyc }} طلب تحقق (KYC)</strong> معلّق ويحتاج مراجعة —
            <a href="{{ route('admin.users', ['kyc_status' => 'submitted']) }}">مراجعة الآن</a>
        </span>
        <button class="dash-alert-dismiss" @click="dismissed.push('kyc')" aria-label="تجاهل">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    @endif

    {{-- Pending tickets --}}
    @if($pendingTix > 0)
    <div class="dash-alert-banner alert-danger" x-show="!dismissed.includes('tickets')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <span>
            <strong>{{ $pendingTix }} تذكرة دعم</strong> مفتوحة تنتظر رد —
            <a href="{{ route('admin.support.index') }}">عرض التذاكر</a>
        </span>
        <button class="dash-alert-dismiss" @click="dismissed.push('tickets')" aria-label="تجاهل">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    @endif

    {{-- All clear --}}
    @unless($hasAlerts)
    <div class="dash-alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span>كل شيء على ما يُرام — لا توجد إنذارات عاجلة.</span>
    </div>
    @endunless

</div>
