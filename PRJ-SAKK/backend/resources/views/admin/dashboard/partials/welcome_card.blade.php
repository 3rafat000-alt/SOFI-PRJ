{{--
    SAKK · صک — Welcome Banner (v4 · Glass)
    Top greeting with date + key stat snapshot.
--}}
@php
    $now   = \Carbon\Carbon::now();
    $hour  = (int) $now->format('G');
    $dayAr = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
    $monthAr = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    if ($hour < 12)      $greeting = 'صباح الخير';
    elseif ($hour < 17)  $greeting = 'مساء الخير';
    else                 $greeting = 'مساء الخير';
    $dateStr = $dayAr[$now->dayOfWeek] . '، ' . $now->format('j') . ' ' . $monthAr[$now->month - 1] . ' ' . $now->format('Y');
@endphp

<div class="dash4-welcome">
    <div class="dash4-welcome-row">
        <div class="dash4-welcome-greeting">
            <h2>{{ $greeting }}، {{ optional(auth()->user())->first_name ?? 'المشرف' }} 👋</h2>
            <p>نظرة عامة على المنصة — آخر التحديثات والمؤشرات</p>
        </div>
        <div class="dash4-welcome-date">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>{{ $dateStr }}</span>
        </div>
    </div>

    <div class="dash4-welcome-stats">
        <div class="dash4-welcome-stat">
            <span class="dash4-welcome-stat-label">إجمالي المستخدمين</span>
            <span class="dash4-welcome-stat-value">{{ number_format($st['total_users'] ?? 0) }} <small>مستخدم</small></span>
        </div>
        <div class="dash4-welcome-stat">
            <span class="dash4-welcome-stat-label">المعاملات اليوم</span>
            <span class="dash4-welcome-stat-value">{{ number_format($st['transactions_today'] ?? 0) }} <small>عملية</small></span>
        </div>
        <div class="dash4-welcome-stat">
            <span class="dash4-welcome-stat-label">إجمالي الحجم</span>
            <span class="dash4-welcome-stat-value">&lrm;${{ number_format(($st['volume'] ?? 0) / 1000, 0) }}<small>ألف</small></span>
        </div>
        <div class="dash4-welcome-stat">
            <span class="dash4-welcome-stat-label">البطاقات النشطة</span>
            <span class="dash4-welcome-stat-value">{{ number_format($activeCards ?? 0) }} <small>بطاقة</small></span>
        </div>
        <div class="dash4-welcome-stat">
            <span class="dash4-welcome-stat-label">التجار</span>
            <span class="dash4-welcome-stat-value">{{ number_format($st['merchants'] ?? 0) }} <small>تاجر</small></span>
        </div>
    </div>
</div>
