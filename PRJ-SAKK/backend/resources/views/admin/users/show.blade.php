@extends('layouts.admin')

@section('title', $user->first_name . ' ' . $user->last_name)

@section('breadcrumbs')
<a href="{{ route('admin.users') }}" class="breadcrumb-item">المستخدمون</a>
<span class="breadcrumb-item">{{ $user->first_name }} {{ $user->last_name }}</span>
@endsection

@section('content')
@php
    $statusVal    = $user->status instanceof \App\Enums\UserStatus ? $user->status->value : $user->status;
    $kycStatusVal = $user->kyc_status instanceof \App\Enums\KycStatus ? $user->kyc_status->value : $user->kyc_status;
    $initials     = mb_strtoupper(mb_substr($user->first_name, 0, 1)) . mb_strtoupper(mb_substr($user->last_name, 0, 1));
    $docTypeMap   = [
        'national_id'      => 'بطاقة هوية',
        'passport'         => 'جواز سفر',
        'drivers_license'  => 'رخصة قيادة',
        'selfie'           => 'صورة شخصية',
        'selfie_with_id'   => 'صورة مع هوية',
        'proof_of_address' => 'إثبات العنوان',
        'residence_permit' => 'إقامة',
    ];
    $genderMap  = ['male' => 'ذكر', 'female' => 'أنثى'];
    $txTypeMap  = ['deposit' => 'إيداع', 'withdrawal' => 'سحب', 'transfer' => 'تحويل', 'refund' => 'استرداد'];
@endphp

<div class="space-y-5" x-data="userShow">

    {{-- ================================================================
         IDENTITY HEADER — redesigned v2 (SAKK premium profile)
         ================================================================ --}}
    @php
        // Status-derived accent colors
        $accentColors = [
            'active'    => ['bar' => 'var(--success)',  'bg' => '#fff',       'ring' => 'rgba(22,163,74,0.12)'],
            'suspended' => ['bar' => 'var(--danger)',   'bg' => '#fff',       'ring' => 'rgba(220,38,38,0.10)'],
            'banned'    => ['bar' => '#b91c1c',         'bg' => '#fff',       'ring' => 'rgba(185,28,28,0.10)'],
            'pending'   => ['bar' => 'var(--gold)',     'bg' => '#fff',       'ring' => 'rgba(181,138,60,0.12)'],
        ];
        $accent = $accentColors[$statusVal] ?? $accentColors['pending'];
    @endphp
    <div class="sakk-identity">
        {{-- Left status bar — color-coded --}}
        <div class="sakk-identity-bar" style="background:{{ $accent['bar'] }};"></div>

        <div class="sakk-identity-body">
            {{-- Avatar + identity --}}
            <div class="sakk-identity-main">
                {{-- Avatar --}}
                @if($user->avatar)
                    <img src="{{ $user->avatar }}" alt="صورة {{ $user->first_name }}" class="sakk-identity-avatar" style="width:52px;height:52px;border-radius:50%;object-fit:cover;">
                @else
                    <div class="sakk-identity-avatar" aria-hidden="true">
                        <span>{{ $initials }}</span>
                    </div>
                @endif

                <div class="sakk-identity-info">
                    {{-- Name row + badges --}}
                    <div class="sakk-identity-top">
                        <h2 class="sakk-identity-name">{{ $user->first_name }} {{ $user->last_name }}</h2>

                        {{-- Status badge --}}
                        @if($statusVal === 'active')
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                نشط
                            </span>
                        @elseif($statusVal === 'suspended')
                            <span class="sakk-pill-danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                موقوف
                            </span>
                        @elseif($statusVal === 'banned')
                            <span class="sakk-pill-danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                محظور
                            </span>
                        @else
                            <span class="sakk-pill-gold">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                قيد الانتظار
                            </span>
                        @endif
                    </div>

                    {{-- Contact lines --}}
                    <div class="sakk-identity-contacts">
                        <div class="sakk-identity-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span dir="ltr">{{ $user->email }}</span>
                            <button type="button" @click="copyToClipboard('{{ $user->email }}', 'email')" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--text-muted);display:inline-flex;align-items:center;border-radius:4px;" aria-label="نسخ البريد">
                                <template x-if="copiedField !== 'email'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                </template>
                                <template x-if="copiedField === 'email'">
                                    <span style="font-size:0.6rem;font-weight:700;color:var(--success);white-space:nowrap;">تم</span>
                                </template>
                            </button>
                        </div>
                        @if($user->phone)
                        <div class="sakk-identity-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                            <span dir="ltr">{{ $user->phone }}</span>
                            <button type="button" @click="copyToClipboard('{{ $user->phone }}', 'phone')" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--text-muted);display:inline-flex;align-items:center;border-radius:4px;" aria-label="نسخ الجوال">
                                <template x-if="copiedField !== 'phone'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                </template>
                                <template x-if="copiedField === 'phone'">
                                    <span style="font-size:0.6rem;font-weight:700;color:var(--success);white-space:nowrap;">تم</span>
                                </template>
                            </button>
                        </div>
                        @endif
                    </div>

                    {{-- Badge strip --}}
                    <div class="sakk-identity-badges">
                        <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:700;border-radius:99px;background:var(--text-primary);color:#fff;">المستوى {{ $user->kyc_level ?? 0 }}</span>

                        @if($kycStatusVal === 'verified')
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                موثّق
                            </span>
                        @elseif($kycStatusVal === 'submitted')
                            <span class="sakk-pill-gold">مقدّم</span>
                        @elseif($kycStatusVal === 'rejected')
                            <span class="sakk-pill-danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                مرفوض
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-muted);">معلّق KYC</span>
                        @endif

                        @if($user->two_factor_enabled)
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                2FA فعّال
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-muted);">2FA معطّل</span>
                        @endif
                    </div>

                    {{-- Timestamps --}}
                    <div class="sakk-identity-time">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            عضو منذ <span dir="ltr">{{ $user->created_at->format('Y/m/d') }}</span>
                        </span>
                        @if($user->last_login_at)
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            آخر دخول <span dir="ltr">{{ $user->last_login_at->format('Y/m/d H:i') }}</span>
                            @if($user->last_login_ip)
                                من <span dir="ltr">{{ $user->last_login_ip }}</span>
                            @endif
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action button — desktop --}}
            <div class="sakk-identity-action">
                @if($statusVal === 'active')
                    <button type="button" @click="openStatusModal()" aria-label="إيقاف حساب {{ $user->first_name }}"
                            style="background:rgba(239,68,68,0.1);color:var(--danger);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        إيقاف
                    </button>
                @else
                    <button type="button" @click="openStatusModal()" aria-label="تفعيل حساب {{ $user->first_name }}"
                            style="background:rgba(16,185,129,0.1);color:var(--success);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        تفعيل
                    </button>
                @endif
            </div>
        </div>

        {{-- Action button — mobile --}}
        <div class="sakk-identity-action-mobile sm-hidden">
            @if($statusVal === 'active')
                <button type="button" @click="openStatusModal()" aria-label="إيقاف حساب {{ $user->first_name }}"
                        style="background:rgba(239,68,68,0.1);color:var(--danger);width:100%;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    إيقاف
                </button>
            @else
                <button type="button" @click="openStatusModal()" aria-label="تفعيل حساب {{ $user->first_name }}"
                        style="background:rgba(16,185,129,0.1);color:var(--success);width:100%;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    تفعيل
                </button>
            @endif
        </div>
    </div>

    {{-- Quick action links --}}
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.75rem 0;">
        <a href="{{ route('admin.transactions', ['user_id' => $user->id]) }}" style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.35rem 0.75rem;font-size:0.72rem;font-weight:700;color:var(--primary);background:rgba(110,27,45,0.06);border-radius:var(--radius-sm);text-decoration:none;transition:all 0.12s;" onmouseover="this.style.background='rgba(110,27,45,0.12)'" onmouseout="this.style.background='rgba(110,27,45,0.06)'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            المعاملات
        </a>
        <a href="{{ route('admin.users.show', $user->id) }}" style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.35rem 0.75rem;font-size:0.72rem;font-weight:700;color:var(--primary);background:rgba(110,27,45,0.06);border-radius:var(--radius-sm);text-decoration:none;transition:all 0.12s;" onmouseover="this.style.background='rgba(110,27,45,0.12)'" onmouseout="this.style.background='rgba(110,27,45,0.06)'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            المحافظ
        </a>
        <a href="{{ route('admin.audit.index', ['user_id' => $user->id]) }}" style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.35rem 0.75rem;font-size:0.72rem;font-weight:700;color:var(--primary);background:rgba(110,27,45,0.06);border-radius:var(--radius-sm);text-decoration:none;transition:all 0.12s;" onmouseover="this.style.background='rgba(110,27,45,0.12)'" onmouseout="this.style.background='rgba(110,27,45,0.06)'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            السجل الأمني
        </a>
    </div>

    {{-- ================================================================
         TAB NAV — SAKK flat design
         Borderless · wine+gold+marble · no shadows
         ================================================================ --}}
    <div style="background:var(--surface);border-radius:var(--radius-main);overflow:hidden;">
        <div class="sakk-tabbar" role="tablist" aria-label="أقسام ملف المستخدم">
            <button class="sakk-tab" role="tab" :aria-selected="activeTab === 'overview' ? 'true' : 'false'" id="tab-0" aria-controls="tab-panel-0"
                    @click="activeTab = 'overview'" @keydown.arrow-right.prevent="focusTab(-1)" @keydown.arrow-left.prevent="focusTab(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                نظرة عامة
            </button>
            <button class="sakk-tab" role="tab" :aria-selected="activeTab === 'wallets' ? 'true' : 'false'" id="tab-1" aria-controls="tab-panel-1"
                    @click="activeTab = 'wallets'" @keydown.arrow-right.prevent="focusTab(-1)" @keydown.arrow-left.prevent="focusTab(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/><circle cx="18" cy="14" r="1"/></svg>
                المحافظ والبطاقات
            </button>
            <button class="sakk-tab" role="tab" :aria-selected="activeTab === 'transactions' ? 'true' : 'false'" id="tab-2" aria-controls="tab-panel-2"
                    @click="activeTab = 'transactions'" @keydown.arrow-right.prevent="focusTab(-1)" @keydown.arrow-left.prevent="focusTab(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
                المعاملات
            </button>
            <button class="sakk-tab" role="tab" :aria-selected="activeTab === 'kyc' ? 'true' : 'false'" id="tab-3" aria-controls="tab-panel-3"
                    @click="activeTab = 'kyc'" @keydown.arrow-right.prevent="focusTab(-1)" @keydown.arrow-left.prevent="focusTab(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                KYC
            </button>
            <button class="sakk-tab" role="tab" :aria-selected="activeTab === 'security' ? 'true' : 'false'" id="tab-4" aria-controls="tab-panel-4"
                    @click="activeTab = 'security'" @keydown.arrow-right.prevent="focusTab(-1)" @keydown.arrow-left.prevent="focusTab(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                الأمان والمخاطر
            </button>
            <button class="sakk-tab" role="tab" :aria-selected="activeTab === 'referrals' ? 'true' : 'false'" id="tab-5" aria-controls="tab-panel-5"
                    @click="activeTab = 'referrals'" @keydown.arrow-right.prevent="focusTab(-1)" @keydown.arrow-left.prevent="focusTab(1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                الإحالات
            </button>
        </div>

        {{-- TAB 1 — نظرة عامة --}}
        <div class="sakk-tabpanel" id="tab-panel-0" role="tabpanel" aria-labelledby="tab-0" x-show="activeTab === 'overview'">
            <div class="sakk-kpi-grid">
                <div class="sakk-kpi">
                    <div class="sakk-kpi-accent"></div>
                    <svg class="sakk-kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/><circle cx="18" cy="14" r="1"/></svg>
                    <div class="sakk-kpi-value">
                        @php $totalBal = $user->wallets->groupBy('currency')->map(fn($g) => $g->sum('balance')); @endphp
                        @foreach($totalBal as $cur => $bal)
                            {!! \App\Support\Money::format($bal, $cur) !!}<br>
                        @endforeach
                        @if($totalBal->isEmpty()) &lrm;$0.00 @endif
                    </div>
                    <div class="sakk-kpi-label">إجمالي الرصيد</div>
                </div>
                <div class="sakk-kpi">
                    <div class="sakk-kpi-accent"></div>
                    <svg class="sakk-kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/><line x1="7" y1="16" x2="11" y2="16"/></svg>
                    <div class="sakk-kpi-value">{{ $user->cards->count() }}</div>
                    <div class="sakk-kpi-label">
                        {{ $user->cards->count() }} بطاقة
                        @if($user->cards->count() > 0)
                            ({{ $user->cards->where('status', 'active')->count() }} نشطة)
                        @endif
                    </div>
                </div>
                <div class="sakk-kpi">
                    <div class="sakk-kpi-accent"></div>
                    <svg class="sakk-kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
                    <div class="sakk-kpi-value">{{ number_format($txCount) }}</div>
                    <div class="sakk-kpi-label">عدد المعاملات</div>
                </div>
                <div class="sakk-kpi">
                    <div class="sakk-kpi-accent"></div>
                    <svg class="sakk-kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    <div class="sakk-kpi-value">${{ number_format($txVolume, 2) }}</div>
                    <div class="sakk-kpi-label">حجم المعاملات الكلي</div>
                </div>
            </div>

            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    المعلومات الشخصية
                </h3>
                <div class="sakk-info-grid">
                    @php
                        $fields = [
                            ['label' => 'الاسم الكامل',        'value' => $user->first_name . ' ' . $user->last_name, 'ltr' => false],
                            ['label' => 'البريد الإلكتروني',    'value' => $user->email,                               'ltr' => true],
                            ['label' => 'رقم الهاتف',           'value' => $user->phone,                               'ltr' => true],
                            ['label' => 'تاريخ الميلاد',        'value' => $user->date_of_birth?->format('Y/m/d'),     'ltr' => true],
                            ['label' => 'الجنس',                'value' => $user->gender ? ($genderMap[$user->gender] ?? $user->gender) : null, 'ltr' => false],
                            ['label' => 'الدولة',               'value' => $user->country_code,                        'ltr' => true],
                            ['label' => 'اللغة',                'value' => $user->language === 'ar' ? 'العربية' : ($user->language === 'en' ? 'English' : $user->language), 'ltr' => false],
                            ['label' => 'المنطقة الزمنية',      'value' => $user->timezone,                            'ltr' => true],
                            ['label' => 'تأكيد البريد',         'value' => $user->email_verified_at?->format('Y/m/d H:i'), 'ltr' => true],
                            ['label' => 'تأكيد الهاتف',         'value' => $user->phone_verified_at?->format('Y/m/d H:i'), 'ltr' => true],
                            ['label' => 'رمز الإحالة',          'value' => $user->referral_code,                       'ltr' => true],
                        ];
                    @endphp
                    @foreach($fields as $field)
                    <div class="sakk-info-row">
                        <span class="sakk-info-label">{{ $field['label'] }}</span>
                        <span class="sakk-info-value" @if($field['ltr']) dir="ltr" @endif>
                            {{ $field['value'] ?? '—' }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- TAB 2 — المحافظ والبطاقات --}}
        <div class="sakk-tabpanel" id="tab-panel-1" role="tabpanel" aria-labelledby="tab-1" x-show="activeTab === 'wallets'" style="display:none;">

            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/><circle cx="18" cy="14" r="1"/></svg>
                    المحافظ
                </h3>
                @if($user->wallets->isEmpty())
                    <div class="sakk-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/><circle cx="18" cy="14" r="1"/></svg>
                        <p>لا توجد محافظ لهذا المستخدم</p>
                    </div>
                @else
                    @foreach($user->wallets as $wallet)
                    <div class="sakk-wallet-row">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                            <div>
                                <span style="font-weight:700;color:var(--text-primary);">{{ $wallet->currency }}</span>
                                @if($wallet->is_default)
                                    <span class="sakk-pill-gold" style="margin-inline-start:0.4rem;">افتراضي</span>
                                @endif
                            </div>
                        </div>
                        <span style="font-weight:800;color:var(--text-primary);direction:ltr;font-variant-numeric:tabular-nums;">{!! \App\Support\Money::format($wallet->balance, $wallet->currency) !!}</span>
                    </div>
                    @endforeach
                @endif
            </div>

            <div class="sakk-divider"></div>

            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/><line x1="7" y1="16" x2="11" y2="16"/></svg>
                    البطاقات
                </h3>
                @if($user->cards->isEmpty())
                    <div class="sakk-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/><line x1="7" y1="16" x2="11" y2="16"/><line x1="18" y1="14" x2="22" y2="18"/><line x1="22" y1="14" x2="18" y2="18"/></svg>
                        <p>لا توجد بطاقات</p>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="sakk-table">
                            <thead>
                                <tr>
                                    <th>رقم البطاقة</th>
                                    <th>النوع</th>
                                    <th>الرصيد</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->cards as $card)
                                @php
                                    $cardType   = $card->card_type instanceof \BackedEnum ? $card->card_type->value : $card->card_type;
                                    $cardStatus = $card->status instanceof \BackedEnum ? $card->status->value : $card->status;
                                @endphp
                                <tr>
                                    <td>
                                        <span style="font-weight:600;color:var(--text-primary);font-family:monospace;" dir="ltr">{{ $card->card_number_masked }}</span>
                                        <span style="display:block;font-size:var(--font-size-xs);color:var(--text-muted);" dir="ltr">{{ $card->expiry }}</span>
                                    </td>
                                    <td>
                                        <span class="{{ $cardType === 'virtual' ? 'sakk-pill-gold' : 'sakk-pill-ghost' }}">
                                            @if($cardType === 'virtual')
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/><line x1="7" y1="16" x2="11" y2="16"/></svg>
                                            @else
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><path d="M4 20h16a2 2 0 002-2V8a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/><line x1="14" y1="6" x2="14" y2="18"/></svg>
                                            @endif
                                            {{ $cardType === 'virtual' ? 'افتراضية' : 'فيزيائية' }}
                                        </span>
                                    </td>
                                    <td style="font-weight:700;direction:ltr;">${{ number_format($card->balance, 2) }}</td>
                                    <td>
                                        @if($cardStatus === 'active')
                                            <span class="sakk-pill-success">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                                نشطة
                                            </span>
                                        @elseif($cardStatus === 'frozen')
                                            <span class="sakk-pill-warning">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                مجمدة
                                            </span>
                                        @else
                                            <span class="sakk-pill-danger">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                                ملغاة
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- TAB 3 — المعاملات --}}
        <div class="sakk-tabpanel" id="tab-panel-2" role="tabpanel" aria-labelledby="tab-2" x-show="activeTab === 'transactions'" style="display:none;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-md);">
                <h3 class="sakk-section-title" style="margin-bottom:0;" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
                    آخر المعاملات
                </h3>
                <a href="{{ route('admin.transactions', ['user_id' => $user->uuid]) }}"
                   style="display:inline-flex;align-items:center;gap:0.3rem;font-size:var(--font-size-sm);font-weight:600;color:var(--primary);text-decoration:none;"
                   aria-label="عرض كل معاملات {{ $user->first_name }} {{ $user->last_name }}">
                    عرض الكل
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
            </div>
            @if($user->transactions->isEmpty())
                <div class="sakk-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
                    <p>لا توجد معاملات</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="sakk-table">
                        <thead>
                            <tr>
                                <th>المرجع</th>
                                <th>النوع</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->transactions as $tx)
                            @php
                                $txType   = $tx->type instanceof \BackedEnum ? $tx->type->value : $tx->type;
                                $txStatus = $tx->status instanceof \BackedEnum ? $tx->status->value : $tx->status;
                                $isCredit = in_array($txType, ['deposit', 'refund']);
                            @endphp
                            <tr>
                                <td style="font-family:monospace;font-weight:600;color:var(--text-primary);direction:ltr;">{{ $tx->reference }}</td>
                                <td style="color:var(--text-secondary);">{{ $txTypeMap[$txType] ?? $txType }}</td>
                                <td style="direction:ltr;">
                                    <span style="font-weight:700;color:{{ $isCredit ? 'var(--success)' : 'var(--error)' }};">
                                        {{ $isCredit ? '+' : '-' }}{!! \App\Support\Money::format($tx->amount, $tx->currency) !!}
                                    </span>
                                </td>
                                <td>
                                    @if($txStatus === 'completed')
                                        <span class="sakk-pill-success">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                            مكتمل
                                        </span>
                                    @elseif($txStatus === 'pending')
                                        <span class="sakk-pill-warning">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            معلق
                                        </span>
                                    @else
                                        <span class="sakk-pill-danger">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                            فاشل
                                        </span>
                                    @endif
                                </td>
                                <td style="color:var(--text-muted);direction:ltr;">{{ $tx->created_at->format('Y/m/d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- TAB 4 — KYC --}}
        <div class="sakk-tabpanel" id="tab-panel-3" role="tabpanel" aria-labelledby="tab-3" x-show="activeTab === 'kyc'" style="display:none;">
            <div class="sakk-kyc-zone" aria-label="معلومات مستوى KYC">
                <div style="display:flex;align-items:center;gap:0.4rem;color:var(--warning-dark);font-size:var(--font-size-sm);font-weight:700;margin-bottom:0.65rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                    <span>مستوى KYC الحالي — للاطلاع فقط</span>
                </div>
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;">
                    <span style="font-size:var(--font-size-sm);color:var(--text-secondary);">المستوى الحالي</span>
                    <span style="display:inline-flex;align-items:center;padding:0.2rem 0.65rem;font-size:var(--font-size-xs);font-weight:700;border-radius:var(--radius-full);background:var(--text-primary);color:#fff;">المستوى {{ $user->kyc_level ?? 0 }}</span>

                    @if($kycStatusVal === 'verified')
                        <span class="sakk-pill-success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                            موثّق
                        </span>
                    @elseif($kycStatusVal === 'submitted')
                        <span class="sakk-pill-gold">مقدّم</span>
                    @elseif($kycStatusVal === 'rejected')
                        <span class="sakk-pill-danger">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                            مرفوض
                        </span>
                    @else
                        <span class="sakk-pill-ghost">معلّق</span>
                    @endif

                    @if($user->kyc_verified_at)
                        <span style="font-size:var(--font-size-xs);color:var(--text-muted);direction:ltr;">{{ $user->kyc_verified_at->format('Y/m/d H:i') }}</span>
                    @endif
                </div>
            </div>

            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                    وثائق KYC
                </h3>
                @if($user->kycDocuments->isEmpty())
                    <div class="sakk-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <p>لم يرفع المستخدم أي وثائق بعد</p>
                    </div>
                @else
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        @foreach($user->kycDocuments as $doc)
                        @php
                            $docStatus       = $doc->status instanceof \BackedEnum ? $doc->status->value : $doc->status;
                            $docTypeLabel    = $docTypeMap[$doc->document_type] ?? $doc->document_type;
                            $alreadyReviewed = in_array($docStatus, ['approved', 'rejected']);
                        @endphp
                        <div style="background:var(--surface);border-radius:var(--radius-sm);overflow:hidden;" id="doc-{{ $doc->id }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.85rem 1rem;">
                                <div>
                                    <h4 style="font-size:var(--font-size-sm);font-weight:700;color:var(--text-primary);">{{ $docTypeLabel }}</h4>
                                    @if($doc->document_number)
                                        <p style="font-size:var(--font-size-xs);font-family:monospace;color:var(--text-muted);margin-top:0.1rem;" dir="ltr">{{ $doc->document_number }}</p>
                                    @endif
                                </div>
                                <div style="display:flex;align-items:center;gap:0.4rem;">
                                    @if($docStatus === 'approved')
                                        <span class="sakk-pill-success">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                            مقبول
                                        </span>
                                    @elseif($docStatus === 'rejected')
                                        <span class="sakk-pill-danger">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                            مرفوض
                                        </span>
                                    @else
                                        <span class="sakk-pill-gold">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            قيد المراجعة
                                        </span>
                                    @endif

                                    <a href="{{ route('admin.secure-file', ['path' => encrypt($doc->file_path)]) }}" target="_blank" rel="noopener"
                                       style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.3rem 0.6rem;font-size:var(--font-size-xs);font-weight:600;border-radius:var(--radius-sm);color:var(--text-secondary);text-decoration:none;background:var(--input-bg);transition:background var(--transition-fast);"
                                       aria-label="معاينة {{ $docTypeLabel }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        معاينة
                                    </a>

                                    <button type="button" @click="openKycApproveModal({{ $user->id }}, {{ $doc->id }}, '{{ addslashes($docTypeLabel) }}', '{{ $doc->document_number }}')"
                                            style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.3rem 0.6rem;font-size:var(--font-size-xs);font-weight:600;border:none;border-radius:var(--radius-sm);background:var(--success-soft);color:var(--success);cursor:pointer;font-family:inherit;transition:opacity var(--transition-fast);"
                                            @if($alreadyReviewed) aria-disabled="true" disabled style="opacity:0.4;pointer-events:none;" @endif
                                            aria-label="قبول {{ $docTypeLabel }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><polyline points="20 6 9 17 4 12"/></svg>
                                        قبول
                                    </button>

                                    <button type="button" @click="openKycRejectModal({{ $user->id }}, {{ $doc->id }}, '{{ addslashes($docTypeLabel) }}')"
                                            style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.3rem 0.6rem;font-size:var(--font-size-xs);font-weight:600;border:none;border-radius:var(--radius-sm);background:var(--error-soft);color:var(--error);cursor:pointer;font-family:inherit;transition:opacity var(--transition-fast);"
                                            @if($alreadyReviewed) aria-disabled="true" disabled @endif
                                            aria-label="رفض {{ $docTypeLabel }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        رفض
                                    </button>
                                </div>
                            </div>
                            <div style="padding:0 1rem 0.85rem;">
                                <div style="display:flex;flex-wrap:wrap;gap:1.5rem 2.5rem;">
                                    <div>
                                        <span style="font-size:var(--font-size-xs);color:var(--text-muted);">دولة الإصدار</span>
                                        <p style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-top:0.1rem;">{{ $doc->issuing_country ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span style="font-size:var(--font-size-xs);color:var(--text-muted);">تاريخ الانتهاء</span>
                                        <p style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-top:0.1rem;" dir="ltr">{{ $doc->expiry_date?->format('Y/m/d') ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span style="font-size:var(--font-size-xs);color:var(--text-muted);">الحجم</span>
                                        <p style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-top:0.1rem;">
                                            @if($doc->file_size)
                                                @if($doc->file_size >= 1048576)
                                                    {{ number_format($doc->file_size / 1048576, 2) }} MB
                                                @else
                                                    {{ number_format($doc->file_size / 1024, 1) }} KB
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                    @if($doc->verified_by || $doc->verified_at)
                                    <div>
                                        <span style="font-size:var(--font-size-xs);color:var(--text-muted);">راجعه</span>
                                        <p style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-top:0.1rem;">
                                            @if($doc->verified_by)
                                                {{ \App\Models\User::find($doc->verified_by)?->first_name ?? 'مشرف' }}
                                            @else
                                                —
                                            @endif
                                            @if($doc->verified_at)
                                                <span dir="ltr" style="font-size:var(--font-size-xs);color:var(--text-muted);">{{ $doc->verified_at->format('Y/m/d') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    @endif
                                </div>
                                @if($docStatus === 'rejected' && $doc->rejection_reason)
                                    <div style="margin-top:0.75rem;padding:0.65rem 0.85rem;background:var(--warning-soft);border-radius:var(--radius-sm);">
                                        <p style="font-size:var(--font-size-xs);font-weight:700;color:var(--warning-dark);">سبب الرفض</p>
                                        <p style="font-size:var(--font-size-sm);margin-top:0.15rem;color:var(--warning-dark);">{{ $doc->rejection_reason }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- TAB 5 — الأمان والمخاطر --}}
        <div class="sakk-tabpanel" id="tab-panel-4" role="tabpanel" aria-labelledby="tab-4" x-show="activeTab === 'security'" style="display:none;">
            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    المصادقة الثنائية (2FA)
                </h3>
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.9rem 1rem;background:var(--surface);border-radius:var(--radius-sm);">
                    @if($user->two_factor_enabled)
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                        <div>
                            <p style="font-weight:700;color:var(--text-primary);">2FA مفعّل</p>
                            <p style="font-size:var(--font-size-xs);color:var(--text-muted);">لا يمكن تعطيل 2FA من لوحة الإدارة — يجب أن يتم من قِبل المستخدم.</p>
                        </div>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 019.9-1"/></svg>
                        <div>
                            <p style="font-weight:700;color:var(--text-primary);">2FA معطّل</p>
                            <p style="font-size:var(--font-size-xs);color:var(--text-muted);">المستخدم لم يفعّل المصادقة الثنائية.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    الأجهزة المسجّلة
                </h3>
                @if($user->devices->isEmpty())
                    <div class="sakk-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        <p>لا توجد أجهزة مسجّلة</p>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="sakk-table">
                            <thead>
                                <tr>
                                    <th>الجهاز</th>
                                    <th>الحالة</th>
                                    <th>موثوق</th>
                                    <th>آخر IP</th>
                                    <th>آخر نشاط</th>
                                    <th>مقيّد حتى</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->devices as $device)
                                @php $devStatus = $device->status instanceof \BackedEnum ? $device->status->value : $device->status; @endphp
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:0.5rem;">
                                            @if($device->device_type === 'phone')
                                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                            @else
                                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                            @endif
                                            <span style="font-weight:600;color:var(--text-primary);">{{ $device->device_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($devStatus === 'approved')
                                            <span class="sakk-pill-success">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                                مقبول
                                            </span>
                                        @elseif($devStatus === 'rejected')
                                            <span class="sakk-pill-danger">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                                مرفوض
                                            </span>
                                        @else
                                            <span class="sakk-pill-gold">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                معلّق
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($device->is_trusted)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;" aria-label="موثوق" role="img"><polyline points="20 6 9 17 4 12"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;" aria-label="غير موثوق" role="img"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        @endif
                                    </td>
                                    <td style="font-family:monospace;color:var(--text-secondary);direction:ltr;">{{ $device->last_ip ?? '—' }}</td>
                                    <td style="color:var(--text-muted);direction:ltr;">{{ $device->last_active_at?->format('Y/m/d H:i') ?? '—' }}</td>
                                    <td>
                                        @if($device->transactions_locked_until && $device->transactions_locked_until->isFuture())
                                            <span class="sakk-pill-gold" dir="ltr">{{ $device->transactions_locked_until->format('Y/m/d') }}</span>
                                        @else
                                            <span style="color:var(--text-muted);">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                    إشارات AML
                </h3>
                @if($user->amlFlags->isEmpty())
                    <div style="padding:1.25rem 1rem;text-align:center;background:var(--success-soft);border-radius:var(--radius-sm);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:2rem;height:2rem;margin:0 auto 0.5rem;display:block;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <p style="font-weight:700;color:var(--success);font-size:var(--font-size-sm);">لا توجد إشارات AML لهذا المستخدم</p>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="sakk-table">
                            <thead>
                                <tr>
                                    <th>القاعدة</th>
                                    <th>الخطورة</th>
                                    <th>وقت التنبيه</th>
                                    <th>الحالة</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->amlFlags as $flag)
                                @php $flagStatus = $flag->status instanceof \BackedEnum ? $flag->status->value : $flag->status; @endphp
                                <tr>
                                    <td style="font-family:monospace;font-weight:600;color:var(--text-primary);">{{ $flag->rule_name }}</td>
                                    <td>
                                        @php
                                            $severityColors = [
                                                'info' => ['bg' => '#EEF2FF', 'color' => '#6366f1'],
                                                'warning' => ['bg' => 'var(--warning-soft)', 'color' => 'var(--warning)'],
                                                'high' => ['bg' => 'var(--error-soft)', 'color' => 'var(--error)'],
                                                'critical' => ['bg' => '#FEF2F2', 'color' => '#b91c1c'],
                                            ];
                                            $sc = $severityColors[$flag->severity] ?? $severityColors['info'];
                                        @endphp
                                        <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.55rem;font-size:0.68rem;font-weight:600;border-radius:var(--radius-full);background:{{ $sc['bg'] }};color:{{ $sc['color'] }};line-height:1.4;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                            {{ $flag->severity }}
                                        </span>
                                    </td>
                                    <td style="color:var(--text-muted);direction:ltr;">{{ $flag->flagged_at?->format('Y/m/d H:i') ?? '—' }}</td>
                                    <td>
                                        @if($flagStatus === 'pending')
                                            <span class="sakk-pill-gold">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                معلّق
                                            </span>
                                        @elseif($flagStatus === 'approved')
                                            <span class="sakk-pill-success">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                                مراجَع
                                            </span>
                                        @elseif($flagStatus === 'rejected')
                                            <span class="sakk-pill-danger">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                                مرفوض
                                            </span>
                                        @else
                                            <span class="sakk-pill-ghost">{{ $flagStatus }}</span>
                                        @endif
                                    </td>
                                    <td style="color:var(--text-secondary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $flag->reviewer_notes ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    سجل النشاط
                </h3>
                @if($user->activityLogs->isEmpty())
                    <div class="sakk-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <p>لا توجد سجلات نشاط</p>
                    </div>
                @else
                    <div class="sakk-timeline">
                        @foreach($user->activityLogs as $log)
                        @php
                            $dotBg = 'var(--text-muted)';
                            $dotRing = 'var(--input-bg)';
                            if (str_contains($log->action, 'suspend') || str_contains($log->action, 'ban') || str_contains($log->action, 'reject')) {
                                $dotBg = 'var(--error)'; $dotRing = 'var(--error-soft)';
                            } elseif (str_contains($log->action, 'approve') || str_contains($log->action, 'activate') || str_contains($log->action, 'verified')) {
                                $dotBg = 'var(--success)'; $dotRing = 'var(--success-soft)';
                            }
                        @endphp
                        <div class="sakk-timeline-item">
                            <div class="sakk-timeline-dot" style="background:{{ $dotBg }};border:3px solid {{ $dotRing }};"></div>
                            <div class="sakk-timeline-content">
                                <div class="sakk-timeline-action">{{ $log->action }}</div>
                                @if($log->description)
                                    <div class="sakk-timeline-desc">{{ $log->description }}</div>
                                @endif
                                <div class="sakk-timeline-meta">
                                    @if($log->ip_address)
                                        <span style="font-family:monospace;" dir="ltr">{{ $log->ip_address }}</span>
                                    @endif
                                    <time dir="ltr">{{ $log->created_at->format('Y/m/d H:i') }}</time>
                                </div>
                                @if($log->admin_id)
                                    <div class="sakk-timeline-actor">
                                        قام به: {{ \App\Models\User::find($log->admin_id)?->first_name ?? 'النظام' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- TAB 6 — الإحالات --}}
        <div class="sakk-tabpanel" id="tab-panel-5" role="tabpanel" aria-labelledby="tab-5" x-show="activeTab === 'referrals'" style="display:none;">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:1.25rem;padding:1rem 1.1rem;background:var(--surface);border-radius:var(--radius-sm);">
                <div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-bottom:0.25rem;">رمز الإحالة</div>
                    <div style="display:flex;align-items:center;gap:0.35rem;">
                        <span style="font-family:monospace;font-size:var(--font-size-base);font-weight:800;color:var(--text-primary);direction:ltr;">{{ $user->referral_code ?? '—' }}</span>
                        @if($user->referral_code)
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $user->referral_code }}').then(() => window.dispatchEvent(new CustomEvent('toast',{detail:{type:'success',message:'تم نسخ الرمز'}})))"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border:none;border-radius:var(--radius-sm);background:var(--input-bg);color:var(--text-muted);cursor:pointer;transition:background var(--transition-fast);"
                                    aria-label="نسخ رمز الإحالة">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            </button>
                        @endif
                    </div>
                </div>
                <div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-bottom:0.25rem;">أُحيل بواسطة</div>
                    @if($user->referrer)
                        <a href="{{ route('admin.users.show', $user->referrer->id) }}" style="font-weight:600;color:var(--primary);text-decoration:none;">
                            {{ $user->referrer->first_name }} {{ $user->referrer->last_name }}
                        </a>
                    @else
                        <div style="font-weight:600;color:var(--text-muted);">مستخدم جديد</div>
                    @endif
                </div>
            </div>

            <div>
                <h3 class="sakk-section-title" role="heading" aria-level="3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    المُحالون ({{ $user->referrals->count() }})
                </h3>
                @if($user->referrals->isEmpty())
                    <div class="sakk-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        <p>لم يدعو هذا المستخدم أحداً بعد</p>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="sakk-table">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>البريد</th>
                                    <th>تاريخ الانضمام</th>
                                    <th>حالة الحساب</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->referrals as $ref)
                                @php $refStatus = $ref->status instanceof \App\Enums\UserStatus ? $ref->status->value : $ref->status; @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.users.show', $ref->id) }}" style="font-weight:600;color:var(--primary);text-decoration:none;">
                                            {{ $ref->first_name }} {{ $ref->last_name }}
                                        </a>
                                    </td>
                                    <td style="color:var(--text-muted);direction:ltr;">{{ $ref->email }}</td>
                                    <td style="color:var(--text-muted);direction:ltr;">{{ $ref->created_at->format('Y/m/d') }}</td>
                                    <td>
                                        @if($refStatus === 'active')
                                            <span class="sakk-pill-success">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                                نشط
                                            </span>
                                        @elseif($refStatus === 'suspended')
                                            <span class="sakk-pill-danger">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                                موقوف
                                            </span>
                                        @else
                                            <span class="sakk-pill-ghost">{{ $refStatus }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Modals (A, B, E, F only) --}}
@include('admin.users.partials._modals')

@endsection

@push('scripts')
{{-- Component classes (kpi-strip-card, tab-nav, privilege-zone, timeline, risk-badge, modal)
     live in the unified layout <style>. No page-level duplicates. --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('userShow', () => ({
            activeTab: 'overview',
            userId: {{ $user->id }},
            copiedField: '',

            copyToClipboard(text, field) {
                navigator.clipboard.writeText(text).then(() => {
                    this.copiedField = field;
                    setTimeout(() => { this.copiedField = ''; }, 1500);
                }).catch(() => {});
            },

            focusTab(direction) {
                const tabs = Array.from(document.querySelectorAll('[role=tab]'));
                const current = tabs.findIndex(t => t === document.activeElement);
                const next = tabs[(current + direction + tabs.length) % tabs.length];
                if (next) next.focus();
            },

            openStatusModal() {
                window.dispatchEvent(new CustomEvent('open-status-modal', {
                    detail: {
                        userId:        this.userId,
                        userName:      '{{ addslashes($user->first_name . ' ' . $user->last_name) }}',
                        userInitials:  '{{ $initials }}',
                        currentStatus: '{{ $statusVal }}',
                        updateUrl:     '{{ route('admin.users.update-status', $user->id) }}'
                    }
                }));
            },

            openKycApproveModal(userId, docId, docType, docNumber) {
                window.dispatchEvent(new CustomEvent('open-kyc-approve-modal', {
                    detail: {
                        userId,
                        docId,
                        docType,
                        docNumber,
                        approveUrl: `/admin/users/${userId}/kyc/${docId}/approve`
                    }
                }));
            },

            openKycRejectModal(userId, docId, docType) {
                window.dispatchEvent(new CustomEvent('open-kyc-reject-modal', {
                    detail: {
                        userId,
                        docId,
                        docType,
                        rejectUrl: `/admin/users/${userId}/kyc/${docId}/reject`
                    }
                }));
            }
        }));

        // ── Modal A — Status (active|suspended only) ─────────────
        Alpine.data('statusModal', () => ({
            show: false, loading: false,
            userId: null, userName: '', userInitials: '', currentStatus: '',
            newStatus: 'active', reason: '', errors: {}, errorMsg: '', updateUrl: '',

            get currentStatusLabel() {
                return { active: 'نشط', suspended: 'موقوف' }[this.currentStatus] || this.currentStatus;
            },
            get currentStatusClass() {
                return { active: 'badge-success', suspended: 'badge-danger' }[this.currentStatus] || 'badge-secondary';
            },

            open(d) {
                Object.assign(this, {
                    userId:        d.userId,
                    userName:      d.userName,
                    userInitials:  d.userInitials,
                    currentStatus: d.currentStatus,
                    newStatus:     d.currentStatus === 'active' ? 'suspended' : 'active',
                    updateUrl:     d.updateUrl,
                    reason:        '',
                    errors:        {},
                    errorMsg:      '',
                    show:          true
                });
                this.$nextTick(() => this.$refs.firstFocus?.focus());
            },

            close() { this.show = false; },

            async submit() {
                this.errors  = {};
                this.errorMsg = '';
                if (!this.reason || this.reason.length < 3) {
                    this.errors.reason = 'أدخل سبباً لا يقل عن 3 أحرف.';
                    return;
                }
                this.loading = true;
                try {
                    const r = await fetch(this.updateUrl, {
                        method:  'POST',
                        headers: {
                            'Content-Type':     'application/json',
                            'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]').content,
                            'Accept':           'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ status: this.newStatus, reason: this.reason })
                    });
                    if (!r.ok) { window.location.reload(); return; }
                    const d = await r.json();
                    this.close();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: d.message || 'تم التحديث' } }));
                    setTimeout(() => window.location.reload(), 800);
                } catch (e) {
                    this.errorMsg = 'حدث خطأ — يرجى المحاولة مجدداً.';
                } finally {
                    this.loading = false;
                }
            }
        }));

        // ============================================================
        // Modal E — KYC Doc Approve
        // ============================================================
        Alpine.data('kycApproveModal', () => ({
            show: false, loading: false,
            userId: null, docId: null, docType: '', docNumber: '', approveUrl: '', errorMsg: '',

            open(d) {
                Object.assign(this, {
                    userId:     d.userId,
                    docId:      d.docId,
                    docType:    d.docType,
                    docNumber:  d.docNumber,
                    approveUrl: d.approveUrl,
                    errorMsg:   '',
                    show:       true
                });
                this.$nextTick(() => this.$refs.firstFocus?.focus());
            },
            close() { this.show = false; },

            async submit() {
                this.loading  = true;
                this.errorMsg = '';
                try {
                    const r = await fetch(this.approveUrl, {
                        method:  'POST',
                        headers: {
                            'Content-Type':     'application/json',
                            'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]').content,
                            'Accept':           'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: '{}'
                    });
                    if (!r.ok) { window.location.reload(); return; }
                    const d = await r.json();
                    this.close();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: d.message || 'تم القبول' } }));
                    setTimeout(() => window.location.reload(), 800);
                } catch (e) {
                    this.errorMsg = 'حدث خطأ.';
                } finally {
                    this.loading = false;
                }
            }
        }));

        // ============================================================
        // Modal F — KYC Doc Reject
        // ============================================================
        Alpine.data('kycRejectModal', () => ({
            show: false, loading: false,
            userId: null, docId: null, docType: '', rejectUrl: '', reason: '', errors: {}, errorMsg: '',

            open(d) {
                Object.assign(this, {
                    userId:    d.userId,
                    docId:     d.docId,
                    docType:   d.docType,
                    rejectUrl: d.rejectUrl,
                    reason:    '',
                    errors:    {},
                    errorMsg:  '',
                    show:      true
                });
                this.$nextTick(() => this.$refs.firstFocus?.focus());
            },
            close() { this.show = false; },

            async submit() {
                this.errors   = {};
                this.errorMsg = '';
                if (!this.reason || this.reason.length < 3) {
                    this.errors.reason = 'أدخل سبباً لا يقل عن 3 أحرف.';
                    return;
                }
                this.loading = true;
                try {
                    const r = await fetch(this.rejectUrl, {
                        method:  'POST',
                        headers: {
                            'Content-Type':     'application/json',
                            'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]').content,
                            'Accept':           'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ reason: this.reason })
                    });
                    if (!r.ok) { window.location.reload(); return; }
                    const d = await r.json();
                    this.close();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: d.message || 'تم الرفض' } }));
                    setTimeout(() => window.location.reload(), 800);
                } catch (e) {
                    this.errorMsg = 'حدث خطأ.';
                } finally {
                    this.loading = false;
                }
            }
        }));
    });
</script>
@endpush
