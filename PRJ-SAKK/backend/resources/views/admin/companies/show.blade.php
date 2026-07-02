@extends('layouts.admin')

@section('title', $company->name)

@section('content')
@php
    $initials = mb_strtoupper(mb_substr($company->name, 0, 1));
    $kycStatusVal = $company->kyc_status;
    $accentColors = [
        'approved'           => ['bar' => 'var(--success)',     'bg' => '#fff', 'ring' => 'rgba(22,163,74,0.12)'],
        'pending'            => ['bar' => 'var(--gold)',        'bg' => '#fff', 'ring' => 'rgba(181,138,60,0.12)'],
        'documents_required' => ['bar' => 'var(--gold)',        'bg' => '#fff', 'ring' => 'rgba(181,138,60,0.12)'],
        'rejected'           => ['bar' => 'var(--danger)',      'bg' => '#fff', 'ring' => 'rgba(220,38,38,0.10)'],
        'suspended'          => ['bar' => 'var(--text-muted)',  'bg' => '#fff', 'ring' => 'rgba(100,116,139,0.10)'],
    ];
    $accent = $accentColors[$kycStatusVal] ?? $accentColors['pending'];
@endphp

<div class="space-y-5" x-data="companyShowPage">

    {{-- ================================================================
         IDENTITY CARD — SAKK premium borderless pattern
         ================================================================ --}}
    <div class="sakk-identity">
        <div class="sakk-identity-bar" style="background:{{ $accent['bar'] }};"></div>

        <div class="sakk-identity-body">
            <div class="sakk-identity-main">
                <div class="sakk-identity-avatar" aria-hidden="true">
                    <span>{{ $initials }}</span>
                </div>

                <div class="sakk-identity-info">
                    <div class="sakk-identity-top">
                        <h2 class="sakk-identity-name">{{ $company->name }}</h2>

                        @if($kycStatusVal === 'approved')
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $company->kyc_status_label }}
                            </span>
                        @elseif(in_array($kycStatusVal, ['pending', 'documents_required']))
                            <span class="sakk-pill-gold">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $company->kyc_status_label }}
                            </span>
                        @elseif(in_array($kycStatusVal, ['rejected', 'suspended']))
                            <span class="sakk-pill-danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                {{ $company->kyc_status_label }}
                            </span>
                        @else
                            <span class="sakk-pill-gold">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $company->kyc_status_label }}
                            </span>
                        @endif

                        @if($company->payroll_enabled)
                            <span class="sakk-pill-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;"><polyline points="20 6 9 17 4 12"/></svg>
                                الرواتب مفعّلة
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-muted);">الرواتب موقوفة</span>
                        @endif
                    </div>

                    <div class="sakk-identity-contacts">
                        @if($company->phone)
                        <div class="sakk-identity-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                            <span dir="ltr">{{ $company->phone }}</span>
                            <button type="button" @click="copyToClipboard('{{ $company->phone }}', 'phone')" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--text-muted);display:inline-flex;align-items:center;border-radius:4px;" aria-label="نسخ الهاتف">
                                <template x-if="copiedField !== 'phone'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                </template>
                                <template x-if="copiedField === 'phone'">
                                    <span style="font-size:0.6rem;font-weight:700;color:var(--success);white-space:nowrap;">تم</span>
                                </template>
                            </button>
                        </div>
                        @endif
                        @if($company->email)
                        <div class="sakk-identity-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span dir="ltr">{{ $company->email }}</span>
                            <button type="button" @click="copyToClipboard('{{ $company->email }}', 'email')" style="background:none;border:none;padding:2px;cursor:pointer;color:var(--text-muted);display:inline-flex;align-items:center;border-radius:4px;" aria-label="نسخ البريد">
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

                    <div class="sakk-identity-badges">
                        @if($company->company_code)
                        <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:700;border-radius:99px;background:var(--text-primary);color:#fff;">{{ $company->company_code }}</span>
                        @endif
                        @if($company->tax_id)
                        <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-secondary);font-family:monospace;" dir="ltr">ضريبي: {{ $company->tax_id }}</span>
                        @endif
                        @if($company->commercial_register)
                        <span style="display:inline-flex;align-items:center;padding:0.15rem 0.55rem;font-size:0.6rem;font-weight:600;border-radius:99px;background:var(--input-bg);color:var(--text-secondary);font-family:monospace;" dir="ltr">سجل: {{ $company->commercial_register }}</span>
                        @endif
                    </div>

                    <div class="sakk-identity-time">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            أُنشئت <span dir="ltr">{{ $company->created_at->format('Y/m/d') }}</span>
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            آخر تحديث <span dir="ltr">{{ $company->updated_at->format('Y/m/d H:i') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="sakk-identity-action">
                <a href="{{ route('admin.companies.documents.show', $company) }}" aria-label="المستندات"
                   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.45rem 0.85rem;font-size:0.72rem;font-weight:700;border-radius:var(--radius-sm);color:var(--text-secondary);background:var(--input-bg);text-decoration:none;transition:all 0.12s;white-space:nowrap;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    المستندات
                </a>
                @if($company->is_active)
                <button type="button" @click="toggleStatus('{{ route('admin.companies.toggle-status', $company) }}')" aria-label="تعطيل"
                   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.45rem 0.85rem;font-size:0.72rem;font-weight:700;border-radius:var(--radius-sm);color:#fff;background:var(--danger);border:none;cursor:pointer;transition:all 0.12s;white-space:nowrap;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    تعطيل
                </button>
                @else
                <button type="button" @click="toggleStatus('{{ route('admin.companies.toggle-status', $company) }}')" aria-label="تفعيل"
                   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.45rem 0.85rem;font-size:0.72rem;font-weight:700;border-radius:var(--radius-sm);color:#fff;background:var(--success);border:none;cursor:pointer;transition:all 0.12s;white-space:nowrap;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="20 6 9 17 4 12"/></svg>
                    تفعيل
                </button>
                @endif
            </div>
        </div>

        <div class="sakk-identity-action-mobile sm-hidden">
            <a href="{{ route('admin.companies.documents.show', $company) }}" aria-label="المستندات"
               style="display:flex;align-items:center;justify-content:center;gap:0.4rem;padding:0.5rem 0;font-size:0.75rem;font-weight:700;border-radius:var(--radius-sm);color:var(--text-secondary);background:var(--input-bg);text-decoration:none;width:100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                المستندات
            </a>
            @if($company->is_active)
            <button type="button" @click="toggleStatus('{{ route('admin.companies.toggle-status', $company) }}')" aria-label="تعطيل"
               style="display:flex;align-items:center;justify-content:center;gap:0.4rem;padding:0.5rem 0;font-size:0.75rem;font-weight:700;border-radius:var(--radius-sm);color:#fff;background:var(--danger);border:none;cursor:pointer;width:100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                تعطيل
            </button>
            @else
            <button type="button" @click="toggleStatus('{{ route('admin.companies.toggle-status', $company) }}')" aria-label="تفعيل"
               style="display:flex;align-items:center;justify-content:center;gap:0.4rem;padding:0.5rem 0;font-size:0.75rem;font-weight:700;border-radius:var(--radius-sm);color:#fff;background:var(--success);border:none;cursor:pointer;width:100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="20 6 9 17 4 12"/></svg>
                تفعيل
            </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    بيانات الشركة
                </h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="stat-label">المالك</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary)">{{ $company->owner_name ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="stat-label">الهاتف</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary)" dir="ltr">{{ $company->phone ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="stat-label">البريد</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary)">{{ $company->email ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="stat-label">الرقم الضريبي</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary)">{{ $company->tax_id ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="stat-label">السجل التجاري</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary)">{{ $company->commercial_register ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="stat-label">المدينة</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary)">{{ $company->city ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="stat-label">الموظفون</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary)">{{ $company->employees_count }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/><circle cx="18" cy="14" r="1"/></svg>
                    المحافظ
                </h3>
            </div>
            <div class="card-body space-y-4">
                @foreach(['USD', 'SYP'] as $cur)
                @php $w = $wallets->get($cur); @endphp
                <div class="flex justify-between py-1">
                    <span class="text-sm font-bold" style="color: var(--text-primary)">{{ $cur }}</span>
                    <span class="text-sm font-bold" style="color: var(--text-primary)" dir="ltr">
                        {{ number_format((float) optional($w)->balance, $cur === 'USD' ? 2 : 0) }}
                        <span style="color: var(--text-muted)">(محجوز {{ number_format((float) optional($w)->pending_balance, $cur === 'USD' ? 2 : 0) }})</span>
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                آخر دفعات الرواتب
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-container" style="border: none; border-radius: 0; box-shadow: none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العملة</th>
                            <th>الإجمالي</th>
                            <th>مدفوع/محجوز/فشل</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $b)
                        <tr>
                            <td style="color: var(--text-primary); font-weight: 600;">{{ $b->id }}</td>
                            <td>{{ $b->currency }}</td>
                            <td dir="ltr">{{ number_format((float) $b->total_amount, $b->currency === 'USD' ? 2 : 0) }}</td>
                            <td>{{ $b->paid_count }}/{{ $b->held_count }}/{{ $b->failed_count }}</td>
                            <td>{{ $b->status_label }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="table-empty">
                                <div class="table-empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                </div>
                                لا دفعات.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('companyShowPage', () => ({
            copiedField: '',

            copyToClipboard(text, field) {
                navigator.clipboard.writeText(text).then(() => {
                    this.copiedField = field;
                    setTimeout(() => { this.copiedField = ''; }, 1500);
                }).catch(() => {});
            },

            toggleStatus(url) {
                fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
                    .then(r => { if (r.ok) location.reload(); });
            }
        }));
    });
</script>
@endpush
