{{-- ═══════════════════════════════════════════════════════════════
     F · ATTENTION REQUIRED + G · LATEST KYC QUEUE
     Combined KYC queue component — alerts + pending requests.
     ═══════════════════════════════════════════════════════════════ --}}
<div x-data="pendingKyc">
    {{-- ── Attention Required (يحتاج انتباهك) ── --}}
    <div class="atn-card">
        <div class="atn-title">
            <x-heroicon name="priority_high" />
            <span>يحتاج انتباهك</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:0.3rem;">
            {{-- Pending KYC Verification --}}
            <a href="{{ route('admin.users', ['kyc_status' => 'submitted']) }}"
               class="atn-item">
                <div class="atn-icon" style="background:{{ ($st['pending_kyc'] ?? 0) > 0 ? '#FDE68A' : '#F3F4F6' }};">
                    <x-heroicon name="verified_user" style="font-size:1.1rem;color:{{ ($st['pending_kyc'] ?? 0) > 0 ? '#D97706' : '#9CA3AF' }};" />
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:.82rem;font-weight:700;margin:0;color:var(--text-primary);">طلبات تحقق معلقة</p>
                    <p style="font-size:.62rem;color:var(--text-muted);margin:0;">
                        {{ ($st['pending_kyc'] ?? 0) > 0 ? 'تحتاج مراجعة الآن' : 'لا شيء — ممتاز' }}
                    </p>
                </div>
                <span class="atn-count" style="background:{{ ($st['pending_kyc'] ?? 0) > 0 ? '#D97706' : '#E5E7EB' }};color:{{ ($st['pending_kyc'] ?? 0) > 0 ? '#fff' : 'var(--text-muted)' }};">
                    {{ $st['pending_kyc'] ?? 0 }}
                </span>
            </a>

            {{-- Partners summary --}}
            <div class="atn-item" style="cursor:default;">
                <div class="atn-icon" style="background:#F3F4F6;">
                    <x-heroicon name="groups" style="font-size:1.1rem;color:var(--text-muted);" />
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:.82rem;font-weight:700;margin:0;color:var(--text-primary);">الشركاء</p>
                    <p style="font-size:.62rem;color:var(--text-muted);margin:0;">
                        {{ number_format($st['agents'] ?? 0) }} وكيل ·
                        {{ number_format($st['merchants'] ?? 0) }} تاجر ·
                        {{ number_format($st['companies'] ?? 0) }} شركة
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Latest KYC Requests (آخر طلبات التحقق) ── --}}
    <div class="card-sukk-main" style="margin-top:var(--space-md);">
        <div class="card-header">
            <div class="card-title" style="font-size:.82rem;">
                <x-heroicon name="verified_user" style="color:var(--sukk-primary);font-size:1.1rem;" />
                <span>آخر طلبات التحقق</span>
            </div>
            @if(($st['pending_kyc'] ?? 0) > 0)
            <span class="kyc-badge kyc-badge-pending">
                {{ $st['pending_kyc'] }} معلق
            </span>
            @endif
        </div>
        <div style="padding:var(--space-xs) var(--space-sm);">
            @forelse($latestKyc ?? [] as $kyc)
                @php
                    $kStatus = $kyc->status ?? 'pending';
                    $statusLabels = [
                        'pending'  => ['معلق', 'kyc-badge-pending'],
                        'approved' => ['مقبول', 'kyc-badge-approved'],
                        'rejected' => ['مرفوض', 'kyc-badge-rejected'],
                    ];
                    $sl = $statusLabels[$kStatus] ?? [$kStatus, 'kyc-badge-pending'];
                    $userName = optional($kyc->user)->first_name . ' ' . optional($kyc->user)->last_name;
                    $userName = trim($userName) ?: 'مستخدم';
                    $initial = mb_substr(optional($kyc->user)->first_name ?? 'ص', 0, 1);
                @endphp
                <a href="{{ route('admin.users.show', $kyc->user_id) }}" class="kyc-item">
                    <span class="kyc-avatar">{{ $initial }}</span>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:.82rem;font-weight:700;margin:0;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $userName }}
                        </p>
                        <p style="font-size:.64rem;color:var(--text-muted);margin:0;">
                            المستوى {{ $kyc->level ?? 1 }} ·
                            {{ optional($kyc->created_at)->diffForHumans() }}
                            @if($kyc->created_at)
                                @php
                                    $h = (int) Carbon\Carbon::parse($kyc->created_at)->diffInHours();
                                @endphp
                                (منذ {{ $h }} ساعة)
                            @endif
                        </p>
                    </div>
                    <div style="text-align:left;flex-shrink:0;">
                        <span class="kyc-badge {{ $sl[1] }}">{{ $sl[0] }}</span>
                    </div>
                </a>
            @empty
                <div class="kyc-empty">
                    <x-heroicon name="inbox" />
                    <p>لا توجد طلبات تحقق</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
