@extends('layouts.admin')

@section('title', $agent->name)
@section('breadcrumbs')
<a href="{{ route('admin.agents.index') }}" class="breadcrumb-item">الوكلاء</a>
<span class="breadcrumb-item">{{ $agent->name }}</span>
@endsection

@section('content')
@php
    $initials = mb_strtoupper(mb_substr($agent->name, 0, 1));
    $accentColors = [
        'active'   => ['bar' => 'var(--success)', 'bg' => '#fff', 'ring' => 'rgba(22,163,74,0.12)'],
        'disabled' => ['bar' => 'var(--danger)',  'bg' => '#fff', 'ring' => 'rgba(220,38,38,0.10)'],
    ];
    $accent = $agent->is_active ? $accentColors['active'] : $accentColors['disabled'];
@endphp

<div class="space-y-5" x-data="agentShowPage">

    {{-- ================================================================
         IDENTITY HEADER — SAKK premium profile
         ================================================================ --}}
    <div class="sakk-identity">
        <div class="sakk-identity-bar" style="background:{{ $accent['bar'] }};"></div>

        <div class="sakk-identity-body">
            <div class="sakk-identity-main">
                {{-- Avatar --}}
                @if($agent->avatar)
                    <img src="{{ $agent->avatar }}" alt="صورة {{ $agent->name }}" class="sakk-identity-avatar" style="width:52px;height:52px;border-radius:50%;object-fit:cover;">
                @else
                    <div class="sakk-identity-avatar" aria-hidden="true">
                        <span>{{ $initials }}</span>
                    </div>
                @endif

                <div class="sakk-identity-info">
                    {{-- Name row + badges --}}
                    <div class="sakk-identity-top">
                        <h2 class="sakk-identity-name">{{ $agent->name }}</h2>

                        {{-- Status pill --}}
                        @if($agent->is_active)
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

                        {{-- Featured badge --}}
                        @if($agent->is_featured)
                            <span class="sakk-pill-gold">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                مميز
                            </span>
                        @endif

                        {{-- Verified badge --}}
                        @if($agent->is_verified)
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                موثق
                            </span>
                        @endif

                        {{-- KYC status pill --}}
                        @if($agent->kyc_status)
                            <span class="sakk-pill-{{ $agent->kyc_status_color }}">{{ $agent->kyc_status_label }}</span>
                        @else
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-muted);">غير محدد KYC</span>
                        @endif
                    </div>

                    {{-- Contact lines --}}
                    <div class="sakk-identity-contacts">
                        @if($agent->phone)
                        <div class="sakk-identity-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                            <span dir="ltr">{{ $agent->phone }}</span>
                            <button type="button" @click="copyToClipboard('{{ $agent->phone }}', 'phone')" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--text-muted);display:inline-flex;align-items:center;border-radius:4px;" aria-label="نسخ الهاتف">
                                <template x-if="copiedField !== 'phone'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                </template>
                                <template x-if="copiedField === 'phone'">
                                    <span style="font-size:0.6rem;font-weight:700;color:var(--success);white-space:nowrap;">تم</span>
                                </template>
                            </button>
                        </div>
                        @endif
                        @if($agent->email)
                        <div class="sakk-identity-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span dir="ltr">{{ $agent->email }}</span>
                            <button type="button" @click="copyToClipboard('{{ $agent->email }}', 'email')" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--text-muted);display:inline-flex;align-items:center;border-radius:4px;" aria-label="نسخ البريد">
                                <template x-if="copiedField !== 'email'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                </template>
                                <template x-if="copiedField === 'email'">
                                    <span style="font-size:0.6rem;font-weight:700;color:var(--success);white-space:nowrap;">تم</span>
                                </template>
                            </button>
                        </div>
                        @endif
                    </div>

                    {{-- Badge strip --}}
                    <div class="sakk-identity-badges">
                        @if($agent->kyc_status)
                            @php
                                $kycLevelColors = [
                                    'pending' => 'var(--gold)',
                                    'submitted' => 'var(--gold)',
                                    'basic' => '#6366f1',
                                    'intermediate' => '#8b5cf6',
                                    'advanced' => 'var(--success)',
                                ];
                                $kycLevelColor = $kycLevelColors[$agent->kyc_status] ?? 'var(--text-muted)';
                            @endphp
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:700;border-radius:99px;background:{{ $kycLevelColor }};color:#fff;">
                                {{ $agent->kyc_status_label }}
                            </span>
                        @endif
                        @if($agent->agent_code)
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-muted);font-family:monospace;" dir="ltr">
                                {{ $agent->agent_code }}
                            </span>
                        @endif
                    </div>

                    {{-- Timestamps --}}
                    <div class="sakk-identity-time">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            أُنشئ في <span dir="ltr">{{ $agent->created_at?->format('Y/m/d H:i') ?? '—' }}</span>
                        </span>
                        @if($agent->updated_at)
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            آخر تحديث <span dir="ltr">{{ $agent->updated_at->format('Y/m/d H:i') }}</span>
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action buttons — desktop --}}
            <div class="sakk-identity-action">
                @if($agent->is_active)
                <button type="button" aria-label="تعطيل {{ $agent->name }}"
                        style="background:rgba(239,68,68,0.1);color:var(--danger);"
                        onclick="fetch('{{ route('admin.agents.toggle-status', $agent) }}', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({is_active:false})}).then(()=>location.reload())">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    تعطيل
                </button>
                @else
                <button type="button" aria-label="تفعيل {{ $agent->name }}"
                        style="background:rgba(22,163,74,0.1);color:var(--success);"
                        onclick="fetch('{{ route('admin.agents.toggle-status', $agent) }}', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({is_active:true})}).then(()=>location.reload())">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg>
                    تفعيل
                </button>
                @endif
            </div>
        </div>

        {{-- Action buttons — mobile --}}
        <div class="sakk-identity-action-mobile sm-hidden">
            @if($agent->is_active)
            <button type="button" aria-label="تعطيل {{ $agent->name }}"
                    style="background:rgba(239,68,68,0.1);color:var(--danger);width:100%;justify-content:center;"
                    onclick="fetch('{{ route('admin.agents.toggle-status', $agent) }}', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({is_active:false})}).then(()=>location.reload())">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                تعطيل
            </button>
            @else
            <button type="button" aria-label="تفعيل {{ $agent->name }}"
                    style="background:rgba(22,163,74,0.1);color:var(--success);width:100%;justify-content:center;"
                    onclick="fetch('{{ route('admin.agents.toggle-status', $agent) }}', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({is_active:true})}).then(()=>location.reload())">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg>
                تفعيل
            </button>
            @endif
        </div>
    </div>

    {{-- Quick metrics row --}}
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.75rem 0;">
        <a href="{{ route('admin.agents.dashboard', $agent) }}" style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.35rem 0.75rem;font-size:0.72rem;font-weight:700;color:var(--primary);background:rgba(110,27,45,0.06);border-radius:var(--radius-sm);text-decoration:none;transition:all 0.12s;" onmouseover="this.style.background='rgba(110,27,45,0.12)'" onmouseout="this.style.background='rgba(110,27,45,0.06)'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
            لوحة الأداء
        </a>
    </div>

    <div class="sakk-kpi-grid" style="margin-top:0;">
        <div class="sakk-kpi">
            <div class="sakk-kpi-accent"></div>
            <svg class="sakk-kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <div class="sakk-kpi-value">{{ $agent->rating }} <span style="font-size:0.65rem;font-weight:600;color:var(--text-muted);">/ 5</span></div>
            <div class="sakk-kpi-label">التقييم</div>
        </div>
        <div class="sakk-kpi">
            <div class="sakk-kpi-accent"></div>
            <svg class="sakk-kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6"/><path d="M23 11h-6"/></svg>
            <div class="sakk-kpi-value">{{ number_format($agent->reviews_count) }}</div>
            <div class="sakk-kpi-label">عدد التقييمات</div>
        </div>
        <div class="sakk-kpi">
            <div class="sakk-kpi-accent"></div>
            <svg class="sakk-kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            <div class="sakk-kpi-value">{{ $agent->commission_rate }}%</div>
            <div class="sakk-kpi-label">نسبة العمولة</div>
        </div>
        <div class="sakk-kpi">
            <div class="sakk-kpi-accent"></div>
            <svg class="sakk-kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            <div class="sakk-kpi-value">{{ count($agent->services ?? []) }}</div>
            <div class="sakk-kpi-label">الخدمات</div>
        </div>
    </div>

    {{-- ===== Detail grid ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Contact & location --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        معلومات الوكيل
                    </h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        @include('admin.agents.partials.field', ['label' => 'اسم الوكيل', 'value' => $agent->name, 'bold' => true])
                        @include('admin.agents.partials.field', ['label' => 'المالك', 'value' => $agent->owner_name ?: '—'])
                        @include('admin.agents.partials.field', ['label' => 'الهاتف', 'value' => $agent->phone ?: '—', 'ltr' => true])
                        @include('admin.agents.partials.field', ['label' => 'كود الوكيل', 'value' => $agent->agent_code, 'mono' => true, 'ltr' => true])
                        <div class="md:col-span-2">
                            @include('admin.agents.partials.field', ['label' => 'العنوان', 'value' => $agent->address])
                        </div>
                        @include('admin.agents.partials.field', ['label' => 'المدينة', 'value' => $agent->city, 'bold' => true])
                        @include('admin.agents.partials.field', ['label' => 'المحافظة', 'value' => $agent->governorate ?: '—'])
                        <div class="md:col-span-2">
                            @include('admin.agents.partials.field', ['label' => 'الإحداثيات', 'value' => $agent->latitude . ', ' . $agent->longitude, 'mono' => true, 'ltr' => true])
                        </div>
                    </div>
                </div>
            </div>

            {{-- Services & financial --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                        الخدمات والإمكانيات المالية
                    </h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div class="md:col-span-2">
                            <p class="label">الخدمات المتاحة</p>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                @if(in_array('cash_in', $agent->services ?? []))<span class="badge badge-success">إيداع نقدي</span>@endif
                                @if(in_array('cash_out', $agent->services ?? []))<span class="badge badge-primary">سحب نقدي</span>@endif
                                @if(empty($agent->services))<span class="text-sm" style="color: var(--text-muted);">لا توجد خدمات مفعّلة</span>@endif
                            </div>
                        </div>
                        @include('admin.agents.partials.field', ['label' => 'ساعات العمل', 'value' => $agent->working_hours ?: 'غير محدد'])
                        @include('admin.agents.partials.field', ['label' => 'نسبة العمولة', 'value' => $agent->commission_rate . '%', 'bold' => true])
                        @include('admin.agents.partials.field', ['label' => 'الحد الأدنى', 'value' => \App\Support\Money::format((float) $agent->min_amount, 'USD'), 'raw' => true, 'ltr' => true])
                        @include('admin.agents.partials.field', ['label' => 'الحد الأقصى', 'value' => $agent->max_amount ? \App\Support\Money::format((float) $agent->max_amount, 'USD') : 'بدون حد', 'raw' => (bool) $agent->max_amount, 'ltr' => true])
                    </div>
                </div>
            </div>

            {{-- KYC --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                        حالة التحقق (KYC)
                    </h3>
                    <span class="badge badge-{{ $agent->kyc_status_color }}">{{ $agent->kyc_status ? $agent->kyc_status_label : 'غير محدد' }}</span>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        @include('admin.agents.partials.field', ['label' => 'تاريخ التقديم', 'value' => $agent->kyc_submitted_at ? $agent->kyc_submitted_at->format('Y/m/d H:i') : '—', 'ltr' => true])
                        @include('admin.agents.partials.field', ['label' => 'تاريخ الاعتماد', 'value' => $agent->kyc_approved_at ? $agent->kyc_approved_at->format('Y/m/d H:i') : '—', 'ltr' => true])
                        @if($agent->kyc_rejection_reason)
                        <div class="md:col-span-2 p-3 rounded-xl" style="background: var(--danger-light);">
                            <p class="text-xs font-bold" style="color: var(--danger);">سبب الرفض</p>
                            <p class="text-sm mt-1" style="color: var(--danger);">{{ $agent->kyc_rejection_reason }}</p>
                        </div>
                        @endif
                    </div>
                    <a href="{{ route('admin.agents.documents.show', $agent) }}" class="btn btn-secondary btn-sm mt-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        عرض المستندات
                    </a>
                </div>
            </div>

            {{-- Notes --}}
            @if($agent->notes)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        ملاحظات
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-sm whitespace-pre-line" style="color: var(--text-secondary);">{{ $agent->notes }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Side column --}}
        <div class="space-y-6">

            {{-- Status --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        الحالة
                    </h3>
                </div>
                <div class="card-body space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold" style="color: var(--text-secondary);">نشط</span>
                        <span class="badge {{ $agent->is_active ? 'badge-success' : 'badge-danger' }}">{{ $agent->is_active ? 'نشط' : 'معطل' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold" style="color: var(--text-secondary);">مميز</span>
                        <span class="badge {{ $agent->is_featured ? 'badge-warning' : 'badge-secondary' }}">{{ $agent->is_featured ? 'مميز' : 'عادي' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold" style="color: var(--text-secondary);">موثق</span>
                        <span class="badge {{ $agent->is_verified ? 'badge-success' : 'badge-secondary' }}">{{ $agent->is_verified ? 'موثق' : 'غير موثق' }}</span>
                    </div>
                </div>
            </div>

            {{-- Rating --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        التقييم
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div class="text-4xl font-extrabold" style="color: var(--accent);">{{ $agent->rating }}</div>
                    <div class="flex items-center justify-center gap-1 mt-2">
                        @for($i = 1; $i <= 5; $i++)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;{{ $i <= round($agent->rating) ? 'color:#f59e0b;fill:#f59e0b;' : 'color:var(--border-strong);' }}"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        @endfor
                    </div>
                    <p class="text-sm mt-2" style="color: var(--text-muted);">{{ number_format($agent->reviews_count) }} تقييم</p>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        إجراءات سريعة
                    </h3>
                </div>
                <div class="card-body space-y-2.5">
                    <a href="https://www.google.com/maps?q={{ $agent->latitude }},{{ $agent->longitude }}" target="_blank" rel="noopener" class="btn btn-secondary w-full" style="justify-content:flex-start;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        عرض على الخريطة
                    </a>
                    @if($agent->phone)
                    <a href="tel:{{ $agent->phone }}" class="btn btn-secondary w-full" style="justify-content:flex-start;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                        اتصال بالوكيل
                    </a>
                    @endif
                </div>
            </div>

            {{-- Meta --}}
            <div class="card">
                <div class="card-body space-y-2 text-xs" style="color: var(--text-muted);">
                    <div class="flex items-center justify-between">
                        <span>أُنشئ في</span>
                        <span dir="ltr" style="color: var(--text-secondary);">{{ $agent->created_at?->format('Y/m/d H:i') ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>آخر تحديث</span>
                        <span dir="ltr" style="color: var(--text-secondary);">{{ $agent->updated_at?->format('Y/m/d H:i') ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>المعرّف</span>
                        <span dir="ltr" class="font-mono truncate max-w-[140px]" style="color: var(--text-secondary);" title="{{ $agent->uuid }}">{{ $agent->uuid }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('agentShowPage', () => ({
            copiedField: '',

            copyToClipboard(text, field) {
                navigator.clipboard.writeText(text).then(() => {
                    this.copiedField = field;
                    setTimeout(() => { this.copiedField = ''; }, 1500);
                }).catch(() => {});
            }
        }));
    });
</script>
@endpush
@endsection
