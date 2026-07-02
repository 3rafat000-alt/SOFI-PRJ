@extends('layouts.admin')

@section('title', $merchant->store_name)

@section('content')
@php
    $initials = '';
    $words = explode(' ', $merchant->store_name);
    foreach ($words as $word) {
        if (mb_strlen($initials) < 2) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
    }
    $initials = $initials ?: mb_strtoupper(mb_substr($merchant->store_name, 0, 2));

    $accentColors = [
        true  => ['bar' => 'var(--success)', 'bg' => '#fff', 'ring' => 'rgba(22,163,74,0.12)'],
        false => ['bar' => 'var(--danger)',  'bg' => '#fff', 'ring' => 'rgba(220,38,38,0.10)'],
    ];
    $accent = $accentColors[$merchant->is_active] ?? $accentColors[false];
@endphp

<div class="space-y-5" x-data="{ copiedField: null, copyToClipboard(text, field) { if (navigator.clipboard) { navigator.clipboard.writeText(text); this.copiedField = field; setTimeout(() => this.copiedField = null, 2000); } } }">

    {{-- ================================================================
         IDENTITY HEADER — SAKK premium profile (borderless)
         ================================================================ --}}
    <div class="sakk-identity">
        {{-- Left status bar — color-coded --}}
        <div class="sakk-identity-bar" style="background:{{ $accent['bar'] }};"></div>

        <div class="sakk-identity-body">
            {{-- Avatar + identity --}}
            <div class="sakk-identity-main">
                {{-- Avatar --}}
                <div class="sakk-identity-avatar" aria-hidden="true">
                    <span>{{ $initials }}</span>
                </div>

                <div class="sakk-identity-info">
                    {{-- Name row + badges --}}
                    <div class="sakk-identity-top">
                        <h2 class="sakk-identity-name">{{ $merchant->store_name }}</h2>

                        {{-- Status badge --}}
                        @if($merchant->is_active)
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                نشط
                            </span>
                        @else
                            <span class="sakk-pill-danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                معطل
                            </span>
                        @endif

                        {{-- Type badge --}}
                        @if($merchant->type === 'both')
                            <span class="sakk-pill-gold">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                {{ $merchant->typeLabel() }}
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-muted);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                                {{ $merchant->typeLabel() }}
                            </span>
                        @endif

                        {{-- KYC/verification badge --}}
                        @if($merchant->is_verified)
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                موثّق
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-muted);">غير موثّق</span>
                        @endif
                    </div>

                    {{-- Contact lines --}}
                    <div class="sakk-identity-contacts">
                        <div class="sakk-identity-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span dir="ltr">{{ $merchant->email ?? '—' }}</span>
                            @if($merchant->email)
                            <button type="button" @click="copyToClipboard('{{ $merchant->email }}', 'email')" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--text-muted);display:inline-flex;align-items:center;border-radius:4px;" aria-label="نسخ البريد">
                                <template x-if="copiedField !== 'email'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                </template>
                                <template x-if="copiedField === 'email'">
                                    <span style="font-size:0.6rem;font-weight:700;color:var(--success);white-space:nowrap;">تم</span>
                                </template>
                            </button>
                            @endif
                        </div>
                        @if($merchant->phone)
                        <div class="sakk-identity-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                            <span dir="ltr">{{ $merchant->phone }}</span>
                            <button type="button" @click="copyToClipboard('{{ $merchant->phone }}', 'phone')" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--text-muted);display:inline-flex;align-items:center;border-radius:4px;" aria-label="نسخ الجوال">
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
                        <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:700;border-radius:99px;background:var(--text-primary);color:#fff;font-family:monospace;">{{ $merchant->merchant_code }}</span>

                        @if($merchant->environment === 'production')
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                إنتاجي
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-muted);">تجريبي</span>
                        @endif
                    </div>

                    {{-- Timestamps --}}
                    <div class="sakk-identity-time">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            أضيف <span dir="ltr">{{ $merchant->created_at->format('Y/m/d') }}</span>
                        </span>
                        @if($merchant->verified_at)
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            وُثّق <span dir="ltr">{{ $merchant->verified_at->format('Y/m/d') }}</span>
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action buttons — desktop --}}
            <div class="sakk-identity-action">
                <a href="{{ route('admin.merchants.dashboard', $merchant) }}" aria-label="لوحة الأداء"
                   style="background:rgba(110,27,45,0.08);color:var(--primary);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    لوحة الأداء
                </a>
                @if($merchant->is_active)
                    <button type="button" @click="fetch('{{ route('admin.merchants.toggle-status', $merchant) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' } }).then(r => { if(r.ok) location.reload() })" aria-label="تعطيل"
                            style="background:rgba(239,68,68,0.1);color:var(--danger);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        تعطيل
                    </button>
                @else
                    <button type="button" @click="fetch('{{ route('admin.merchants.toggle-status', $merchant) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' } }).then(r => { if(r.ok) location.reload() })" aria-label="تفعيل"
                            style="background:rgba(22,163,74,0.1);color:var(--success);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        تفعيل
                    </button>
                @endif
            </div>
        </div>

        {{-- Action buttons — mobile --}}
        <div class="sakk-identity-action-mobile sm-hidden">
            <a href="{{ route('admin.merchants.dashboard', $merchant) }}" aria-label="لوحة الأداء"
               style="background:rgba(110,27,45,0.08);color:var(--primary);width:100%;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                لوحة الأداء
            </a>
            @if($merchant->is_active)
                <button type="button" @click="fetch('{{ route('admin.merchants.toggle-status', $merchant) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' } }).then(r => { if(r.ok) location.reload() })" aria-label="تعطيل"
                        style="background:rgba(239,68,68,0.1);color:var(--danger);width:100%;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    تعطيل
                </button>
            @else
                <button type="button" @click="fetch('{{ route('admin.merchants.toggle-status', $merchant) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' } }).then(r => { if(r.ok) location.reload() })" aria-label="تفعيل"
                        style="background:rgba(22,163,74,0.1);color:var(--success);width:100%;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    تفعيل
                </button>
            @endif
        </div>
    </div>

    {{-- ================================================================
         CONTENT BELOW — grid layout (unchanged)
         ================================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card" x-data="{ showKeys: false }">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                        مفاتيح API
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="showKeys = !showKeys" class="btn btn-secondary btn-sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;" x-show="showKeys"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;" x-show="!showKeys"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span x-text="showKeys ? 'إخفاء' : 'إظهار'">إظهار</span>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="display: flex; flex-direction: column; gap: var(--space-md);">
                    <div>
                        <p class="label">API Key</p>
                        <div class="flex items-center gap-2">
                            <code class="flex-1" style="background: var(--surface-hover); border: none; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm); padding: var(--space-sm) var(--space-md); font-size: var(--font-size-sm); font-family: monospace; color: var(--text-primary);" dir="ltr">
                                <span x-show="!showKeys">{{ substr($merchant->api_key, 0, 8) }}••••••••••••••••••••••••</span>
                                <span x-show="showKeys" x-cloak>{{ $merchant->api_key }}</span>
                            </code>
                        </div>
                    </div>
                    <div>
                        <p class="label">API Secret</p>
                        <div class="flex items-center gap-2">
                            <code class="flex-1" style="background: var(--surface-hover); border: none; border-radius: var(--radius-xl); box-shadow: var(--shadow-sm); padding: var(--space-sm) var(--space-md); font-size: var(--font-size-sm); font-family: monospace; color: var(--text-primary);" dir="ltr">
                                <span x-show="!showKeys">{{ substr($merchant->api_secret, 0, 8) }}••••••••••••••••••••••••••••••••••••••••••••••••••••</span>
                                <span x-show="showKeys" x-cloak>{{ $merchant->api_secret }}</span>
                            </code>
                        </div>
                    </div>
                    @if($merchant->webhook_url)
                    <div>
                        <p class="label">Webhook URL</p>
                        <p class="text-sm font-mono" style="color: var(--text-primary);" dir="ltr">{{ $merchant->webhook_url }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="label">البيئة</p>
                        <span class="badge {{ $merchant->environment === 'production' ? 'badge-success' : 'badge-secondary' }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 00-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 012-3.95"/><path d="M16 5h4v4"/><path d="M19 2l-4 4"/><circle cx="12" cy="12" r="10"/></svg>
                            {{ $merchant->environment === 'production' ? 'إنتاجي' : 'تجريبي' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        الحالة
                    </div>
                </div>
                <div class="table-container" style="border: none; border-radius: 0; box-shadow: none; border: none;">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 600;">حالة الاتصال</td>
                                <td>
                                    <span class="badge {{ $merchant->is_active ? 'badge-success' : 'badge-secondary' }}" style="gap: var(--space-xs);">
                                        <span class="w-2 h-2 rounded-full" style="display: inline-block; background: {{ $merchant->is_active ? 'var(--success)' : 'var(--border-strong)' }}; box-shadow: {{ $merchant->is_active ? 'var(--shadow-sm)' : 'none' }};"></span>
                                        {{ $merchant->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 600;">توثيق</td>
                                <td>
                                    <span class="badge {{ $merchant->is_verified ? 'badge-warning' : 'badge-secondary' }}" style="gap: var(--space-xs);">
                                        @if($merchant->is_verified)
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--accent);"><path d="M9 12l2 2 4-4"/><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                        موثق
                                        @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--text-muted);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                        غير موثق
                                        @endif
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 600;">API</td>
                                <td>
                                    <span class="badge {{ $merchant->has_api_access ? 'badge-success' : 'badge-secondary' }}" style="gap: var(--space-xs);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        {{ $merchant->has_api_access ? 'مفعل' : 'معطل' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="padding: 0 var(--space-lg);">
                        <div style="height:1px;background:var(--border-light);margin:0;border:none;"></div>
                    </div>
                    <table class="table">
                        <tbody>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 600;">الرصيد الحالي</td>
                                <td><span class="font-extrabold" style="color: var(--text-primary);" dir="ltr">&lrm;${{ number_format($merchant->balance, 2) }}</span></td>
                            </tr>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 600;">الإجمالي المكتسب</td>
                                <td><span class="font-extrabold" style="color: var(--success);" dir="ltr">&lrm;${{ number_format($merchant->total_earned, 2) }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="padding: 0 var(--space-lg);">
                        <div style="height:1px;background:var(--border-light);margin:0;border:none;"></div>
                    </div>
                    <table class="table">
                        <tbody>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 600;">نسبة العمولة</td>
                                <td><span class="font-extrabold" style="color: var(--text-primary);">{{ number_format($merchant->commission_rate, 2) }}%</span></td>
                            </tr>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 600;">تاريخ الإضافة</td>
                                <td><span class="font-bold" style="color: var(--text-primary);" dir="ltr">{{ $merchant->created_at->format('Y/m/d') }}</span></td>
                            </tr>
                            @if($merchant->verified_at)
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 600;">تاريخ التوثيق</td>
                                <td><span class="font-bold" style="color: var(--text-primary);" dir="ltr">{{ $merchant->verified_at->format('Y/m/d') }}</span></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if($merchant->notes)
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        ملاحظات
                    </div>
                </div>
                <div class="card-body">
                    <p style="color: var(--text-primary); font-size: var(--font-size-sm);">{{ $merchant->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
