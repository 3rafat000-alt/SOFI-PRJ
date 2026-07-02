{{--
    SAKK · صک — Transactions Table (v4 · full table)
    ────────────────────────────────────────────────
    Real table with status badges, user info, search.
    Uses $recentTransactions (not $recentLargeOps).
--}}
<div class="dash4-table-wrap">
    <div class="dash4-table-header">
        <div class="dash4-table-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <span>آخر المعاملات</span>
        </div>
        <div class="dash4-table-actions">
            <a href="{{ route('admin.transactions') }}" class="dash-ledger-all" style="font-size:0.7rem;font-weight:700;color:var(--sukk-primary);text-decoration:none;display:inline-flex;align-items:center;gap:0.25rem;">
                عرض الكل
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>
    </div>

    @if(count($recentTransactions ?? []) > 0)
        <table class="dash4-table">
            <thead>
                <tr>
                    <th>المستخدم</th>
                    <th>المبلغ</th>
                    <th>العملة</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentTransactions as $tx)
                    @php
                        $isSyp       = ($tx->currency ?? 'USD') === 'SYP';
                        $amount      = abs((float)($tx->amount ?? 0));
                        $formatted   = \App\Support\Money::format($amount, $tx->currency ?? 'USD');
                        $userName    = trim((optional($tx->user)->first_name ?? '') . ' ' . (optional($tx->user)->last_name ?? '')) ?: 'مستخدم';
                        $status      = $tx->status?->value ?? 'completed';
                        $statusBadge = match($status) {
                            'completed'            => ['dash4-table-badge--success', 'check'],
                            'processing', 'pending' => ['dash4-table-badge--processing', 'clock'],
                            'failed','cancelled','reversed','refunded' => ['dash4-table-badge--danger', 'x'],
                            default                => ['dash4-table-badge--success', 'check'],
                        };
                        $statusLabels = [
                            'completed' => 'ناجحة', 'processing' => 'قيد المعالجة',
                            'pending'   => 'معلّقة', 'failed' => 'فاشلة',
                            'cancelled' => 'ملغاة', 'reversed' => 'معكوسة', 'refunded' => 'مُستردة',
                        ];
                    @endphp
                    <tr>
                        <td>
                            <div class="dash4-table-user">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                {{ $userName }}
                            </div>
                        </td>
                        <td>
                            <span class="dash4-table-amount {{ $isSyp ? 'syp' : 'usd' }}">{!! $formatted !!}</span>
                        </td>
                        <td>
                            <span style="font-size:0.65rem;font-weight:600;color:var(--text-muted);">{{ $tx->currency ?? 'USD' }}</span>
                        </td>
                        <td>
                            <span class="dash4-table-badge {{ $statusBadge[0] }}">
                                @if($statusBadge[1] === 'check')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                @elseif($statusBadge[1] === 'clock')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                @endif
                                {{ $statusLabels[$status] ?? $status }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size:0.65rem;color:var(--text-muted);font-weight:500;">{{ optional($tx->created_at)->format('Y/m/d · H:i') }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="dash4-table-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <p>لا توجد معاملات بعد</p>
        </div>
    @endif
</div>
