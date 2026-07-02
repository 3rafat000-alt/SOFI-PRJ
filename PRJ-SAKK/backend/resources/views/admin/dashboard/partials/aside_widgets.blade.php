{{--
    SAKK · صک — Dashboard Sidebar (v4)
    ──────────────────────────────────
    Quick Access · System stats · KYC queue · Support tickets
--}}

{{-- Card 1: Quick Access — sensitive & important actions --}}
<div class="dash4-side-card">
    <div class="dash4-side-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        <span>وصول سريع</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:4px;">
        <a href="{{ route('admin.gold.prices') }}" style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:var(--radius-sm);font-size:0.75rem;font-weight:600;color:var(--text-primary);text-decoration:none;background:var(--accent-soft);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;color:var(--gold-deep);"><circle cx="12" cy="12" r="0"/><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <span style="flex:1;">الذهب والأسعار</span>
        </a>
        <a href="{{ route('admin.kyc.index') }}" style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:var(--radius-sm);font-size:0.75rem;font-weight:600;color:var(--text-primary);text-decoration:none;background:var(--warning-soft);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;color:var(--gold);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
            <span style="flex:1;">مراجعة KYC</span>
            <span style="font-size:0.6rem;font-weight:700;background:var(--warning-soft);color:var(--warning-dark);padding:1px 7px;border-radius:var(--radius-full);">{{ $st['pending_kyc'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.support.index') }}" style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:var(--radius-sm);font-size:0.75rem;font-weight:600;color:var(--text-primary);text-decoration:none;background:rgba(110,27,45,0.06);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;color:var(--sukk-primary);"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            <span style="flex:1;">تذاكر الدعم</span>
            <span style="font-size:0.6rem;font-weight:700;background:var(--primary-soft);color:var(--primary);padding:1px 7px;border-radius:var(--radius-full);">{{ $pendingTickets ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.settings') }}" style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:var(--radius-sm);font-size:0.75rem;font-weight:600;color:var(--text-primary);text-decoration:none;background:var(--input-bg);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;color:var(--text-muted);"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            <span>الإعدادات</span>
        </a>
    </div>
</div>

{{-- Card 2: System Stats --}}
<div class="dash4-side-card">
    <div class="dash4-side-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>إحصائيات المنصة</span>
    </div>
    <div class="dash4-side-stat">
        <span class="dash4-side-stat-label">المستخدمون</span>
        <span class="dash4-side-stat-value">{{ number_format($st['total_users'] ?? 0) }}</span>
    </div>
    <div class="dash4-side-stat">
        <span class="dash4-side-stat-label">جديد اليوم</span>
        <span class="dash4-side-stat-value" style="color:var(--success);">+{{ number_format($st['new_users_today'] ?? 0) }}</span>
    </div>
    <div class="dash4-side-stat">
        <span class="dash4-side-stat-label">البطاقات النشطة</span>
        <span class="dash4-side-stat-value">{{ number_format($activeCards ?? 0) }}</span>
    </div>
    <div class="dash4-side-stat">
        <span class="dash4-side-stat-label">التجار</span>
        <span class="dash4-side-stat-value">{{ number_format($st['merchants'] ?? 0) }}</span>
    </div>
    <div class="dash4-side-stat">
        <span class="dash4-side-stat-label">الوكلاء</span>
        <span class="dash4-side-stat-value">{{ number_format($st['agents'] ?? 0) }}</span>
    </div>
    <div class="dash4-side-stat">
        <span class="dash4-side-stat-label">الشركات</span>
        <span class="dash4-side-stat-value">{{ number_format($st['companies'] ?? 0) }}</span>
    </div>
    <div class="dash4-side-stat">
        <span class="dash4-side-stat-label">سعر الصرف</span>
        <span class="dash4-side-stat-value" style="direction:ltr;display:flex;align-items:center;gap:2px;">
            🇸🇾 {{ number_format($st['usd_rate'] ?? 13000) }}
        </span>
    </div>
</div>

{{-- Card 2: KYC Queue --}}
<div class="dash4-side-card">
    <div class="dash4-side-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span>طلبات KYC</span>
        @if(($st['pending_kyc'] ?? 0) > 0)
            <span style="margin-inline-start:auto;font-size:0.65rem;font-weight:700;background:#FDE68A;color:#92400E;padding:0.1rem 0.5rem;border-radius:var(--radius-full);">{{ $st['pending_kyc'] }}</span>
        @endif
    </div>

    <div class="dash4-kyc-stats" style="display:flex;gap:var(--space-sm);margin-bottom:var(--space-md);">
        <div style="flex:1;text-align:center;padding:0.4rem;border-radius:var(--radius-sm);background:rgba(251,191,36,0.1);">
            <div style="font-size:0.9rem;font-weight:800;color:#92400E;">{{ $kycStats['pending'] ?? 0 }}</div>
            <div style="font-size:0.55rem;font-weight:600;color:var(--text-muted);">معلق</div>
        </div>
        <div style="flex:1;text-align:center;padding:0.4rem;border-radius:var(--radius-sm);background:var(--success-light);">
            <div style="font-size:0.9rem;font-weight:800;color:var(--success-dark);">{{ $kycStats['approved'] ?? 0 }}</div>
            <div style="font-size:0.55rem;font-weight:600;color:var(--text-muted);">مقبول</div>
        </div>
        <div style="flex:1;text-align:center;padding:0.4rem;border-radius:var(--radius-sm);background:var(--danger-light);">
            <div style="font-size:0.9rem;font-weight:800;color:var(--danger-dark);">{{ $kycStats['rejected'] ?? 0 }}</div>
            <div style="font-size:0.55rem;font-weight:600;color:var(--text-muted);">مرفوض</div>
        </div>
    </div>

    @if(count($pendingKyc ?? []) > 0)
        @foreach($pendingKyc as $kyc)
            @php
                $name = trim((optional($kyc->user)->first_name ?? '') . ' ' . (optional($kyc->user)->last_name ?? '')) ?: 'مستخدم';
                $initial = mb_substr($name, 0, 1);
            @endphp
            <a href="{{ route('admin.users', ['kyc_status' => 'submitted']) }}" class="dash4-kyc-item">
                <div class="dash4-kyc-avatar">{{ $initial }}</div>
                <div class="dash4-kyc-info">
                    <div class="dash4-kyc-name">{{ $name }}</div>
                    <div class="dash4-kyc-date">{{ optional($kyc->created_at)->diffForHumans() }}</div>
                </div>
                <span class="dash4-kyc-pill">قيد المراجعة</span>
            </a>
        @endforeach
    @else
        <div class="dash4-kyc-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;margin-bottom:0.3rem;opacity:.3;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <p>لا توجد طلبات KYC معلقة</p>
        </div>
    @endif
</div>

{{-- Card 3: Quick Info --}}
<div class="dash4-side-card">
    <div class="dash4-side-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        <span>تذاكر الدعم</span>
    </div>
    @if(($pendingTickets ?? 0) > 0)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.4rem 0;">
            <span style="font-size:0.72rem;font-weight:600;color:var(--text-secondary);">في انتظار الرد</span>
            <span style="font-size:0.82rem;font-weight:700;color:var(--text-primary);">{{ $pendingTickets }}</span>
        </div>
        <a href="{{ route('admin.support.index') }}" style="display:inline-flex;align-items:center;gap:0.25rem;margin-top:0.3rem;font-size:0.7rem;font-weight:700;color:var(--sukk-primary);text-decoration:none;">
            عرض التذاكر
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    @else
        <div style="text-align:center;padding:var(--space-sm);color:var(--text-muted);font-size:0.72rem;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;margin-bottom:0.25rem;opacity:.3;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <p>لا توجد تذاكر معلقة</p>
        </div>
    @endif
</div>
