{{--
    SAKK · صک — Quick Actions Bar (v4 · horizontal pill buttons)
    ────────────────────────────────────────────────────────
    Inline action buttons with icons · Alpine loading state
--}}
<div class="dash4-qa" x-data="{ qaLoading: null }">
    <span class="dash4-qa-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-inline-end:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        إجراءات سريعة
    </span>

    <a href="{{ route('admin.users') }}" class="dash4-qa-btn"
       @click="qaLoading = 'users'; setTimeout(() => qaLoading = null, 1200)"
       :class="qaLoading === 'users' && 'loading'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
        المستخدمون
    </a>

    <a href="{{ route('admin.gold.prices') }}" class="dash4-qa-btn"
       @click="qaLoading = 'gold'; setTimeout(() => qaLoading = null, 1200)"
       :class="qaLoading === 'gold' && 'loading'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        أسعار الذهب
    </a>

    <a href="{{ route('admin.support.index') }}" class="dash4-qa-btn"
       @click="qaLoading = 'support'; setTimeout(() => qaLoading = null, 1200)"
       :class="qaLoading === 'support' && 'loading'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        تذاكر الدعم
    </a>

    <a href="{{ route('admin.transactions', ['export' => 'csv']) }}" class="dash4-qa-btn"
       @click="qaLoading = 'tx'; setTimeout(() => qaLoading = null, 1200)"
       :class="qaLoading === 'tx' && 'loading'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        تصدير
    </a>

    <a href="{{ route('admin.agents.index') }}" class="dash4-qa-btn"
       @click="qaLoading = 'agents'; setTimeout(() => qaLoading = null, 1200)"
       :class="qaLoading === 'agents' && 'loading'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        الوكلاء
    </a>

    <a href="{{ route('admin.settings') }}" class="dash4-qa-btn"
       @click="qaLoading = 'settings'; setTimeout(() => qaLoading = null, 1200)"
       :class="qaLoading === 'settings' && 'loading'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        الإعدادات
    </a>
</div>
