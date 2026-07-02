@extends('layouts.admin')

@section('title', 'الإشعارات والتسويق')
@section('breadcrumbs')
<span class="breadcrumb-item">العمليات</span>
<span class="breadcrumb-item">الإشعارات والتسويق</span>
@endsection

@php
    $audMeta = [
        'all'          => ['الجميع', 'كل المستخدمين النشطين', 'groups'],
        'active'       => ['النشطون', 'الحسابات الفعّالة', 'how_to_reg'],
        'kyc_verified' => ['موثّقون (KYC)', 'مكتملو التحقّق', 'verified'],
        'inactive'     => ['غير النشطين', 'حسابات معطّلة', 'person_off'],
        'specific'     => ['مستخدمون محدّدون', 'بالمُعرّفات يدويًا', 'pin'],
    ];
    $statusMeta = [
        'sent'      => ['أُرسل',     'badge-success'],
        'scheduled' => ['مجدول',    'badge-warning'],
        'pending'   => ['قيد الإرسال', 'badge-secondary'],
        'failed'    => ['فشل',      'badge-danger'],
    ];
@endphp

@push('styles')
<style>
/* ============================================================
   SAKK NOTIFICATIONS — إشعارات وتسويق
   Clean · Sophisticated · Unified (v2)
   ============================================================ */

/* ── Composer layout ── */
.notif-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);
}
@media (min-width: 1024px) {
  .notif-grid { grid-template-columns: 1fr 320px; }
}

/* ── Cards inside composer ── */
.notif-card {
  background: var(--surface);
  border-radius: var(--radius-main);
  overflow: hidden;
}
.notif-card-hdr {
  padding: var(--space-md) var(--space-lg);
  border-bottom: 1px solid var(--border-light);
}
.notif-card-hdr h3 {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}
.notif-card-bd {
  padding: var(--space-md) var(--space-lg);
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.notif-card-bd .label {
  font-size: var(--font-size-xs);
  font-weight: 700;
  color: var(--text-secondary);
  display: block;
  margin-bottom: 0.3rem;
}
.notif-card-bd .label-required::after {
  content: ' *';
  color: var(--danger);
}
.notif-card-bd .input {
  width: 100%;
  padding: 0 0.85rem;
  height: 40px;
  font-size: var(--font-size-sm);
  font-family: inherit;
  color: var(--text-primary);
  background: var(--input-bg);
  border: none;
  border-radius: var(--radius-sm);
  outline: none;
  transition: box-shadow var(--transition-fast);
}
.notif-card-bd .input:focus {
  box-shadow: var(--shadow-focus);
  background: var(--surface);
}
.notif-card-bd textarea.input {
  padding: 0.65rem 0.85rem;
  height: auto;
  resize: vertical;
  min-height: 72px;
}

/* ── Audience cards ── */
.aud-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-sm);
}
@media (min-width: 640px) { .aud-grid { grid-template-columns: repeat(5, 1fr); } }

.aud {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
  padding: var(--space-md) var(--space-sm);
  border-radius: var(--radius-sm);
  background: var(--bg);
  cursor: pointer;
  transition: all var(--transition-fast);
  border: 1px solid transparent;
  text-align: center;
}
.aud:hover { background: var(--surface-hover); }
.aud.is-on {
  background: var(--sukk-primary-soft);
  border-color: var(--sukk-primary);
}
.aud-ico {
  width: 32px; height: 32px;
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text-secondary);
  display: grid; place-items: center;
}
.aud.is-on .aud-ico { color: var(--sukk-primary); }
.aud-ico svg[data-slot="icon"] { width: 16px; height: 16px; }
.aud-meta .t {
  display: block;
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}
.aud-meta .c {
  display: block;
  font-size: 0.6rem;
  color: var(--text-muted);
  margin-top: 1px;
}
.aud-count {
  font-size: 0.7rem;
  font-weight: 800;
  color: var(--sukk-primary);
  font-variant-numeric: tabular-nums;
}

/* ── Schedule toggle ── */
.notif-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  cursor: pointer;
}
.notif-toggle .t {
  font-size: var(--font-size-sm);
  font-weight: 700;
  color: var(--text-primary);
  display: block;
}
.notif-toggle .d {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  display: block;
  margin-top: 1px;
}
.notif-toggle .switch {
  position: relative;
  display: inline-flex;
  align-items: center;
  flex: none;
}
.notif-toggle .switch input {
  position: absolute;
  opacity: 0;
  width: 0; height: 0;
}
.notif-toggle .switch .trk {
  width: 38px; height: 20px;
  border-radius: var(--radius-full);
  background: var(--border-strong);
  transition: background var(--transition-fast);
  cursor: pointer;
}
.notif-toggle .switch .trk::after {
  content: '';
  position: absolute;
  top: 3px;
  inset-inline-start: 3px;
  width: 14px; height: 14px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
  transition: transform var(--transition-fast);
}
.notif-toggle .switch input:checked + .trk { background: var(--sukk-primary); }
.notif-toggle .switch input:checked + .trk::after { transform: translateX(-18px); }
[dir="rtl"] .notif-toggle .switch input:checked + .trk::after { transform: translateX(18px); }

/* ── Phone preview ── */
.preview-wrap {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
.phone {
  width: 100%;
  max-width: 280px;
  margin: 0 auto;
  background: #000;
  border-radius: 36px;
  padding: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.phone-screen {
  background: #fff;
  border-radius: 28px;
  padding: 14px;
  min-height: 200px;
}
.phone-time {
  text-align: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 12px;
}
.notif-badge {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 14px;
}
.notif-badge-ico {
  width: 28px; height: 28px;
  border-radius: 8px;
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.notif-badge-ico svg[data-slot="icon"] { width: 16px; height: 16px; }
.notif-badge-bd { flex: 1; min-width: 0; }
.notif-badge-app {
  font-size: 0.6rem;
  font-weight: 600;
  color: var(--text-muted);
  display: flex;
  justify-content: space-between;
}
.notif-badge-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-top: 2px;
}
.notif-badge-body {
  font-size: 0.68rem;
  color: #666;
  margin-top: 2px;
  line-height: 1.3;
}
.reach-strip {
  text-align: center;
}
.reach-pill {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem 1.2rem;
  background: var(--surface);
  border-radius: var(--radius-main);
}
.reach-pill .n {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--sukk-primary);
  font-variant-numeric: tabular-nums;
}
.reach-pill .l {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  font-weight: 600;
}

/* ── Save bar ── */
.notif-save {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-md) var(--space-lg);
  background: var(--surface);
  border-radius: var(--radius-main);
}
.notif-save .hint {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
}
.notif-save .btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.5rem 1.25rem;
  font-size: var(--font-size-sm);
  font-weight: 700;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-family: inherit;
  transition: opacity var(--transition-fast);
}
.notif-save .btn-primary {
  background: var(--sukk-primary);
  color: #fff;
}
.notif-save .btn-primary:hover { opacity: 0.9; }
.notif-save .btn-primary svg[data-slot="icon"] { width: 18px; height: 18px; }

/* ── History table ── */
.notif-table-wrap {
  background: var(--surface);
  border-radius: var(--radius-main);
  overflow: hidden;
}
.notif-table {
  width: 100%;
  border-collapse: collapse;
}
.notif-table th {
  text-align: start;
  padding: 0.7rem 1rem;
  font-size: var(--font-size-xs);
  font-weight: 700;
  color: var(--text-secondary);
  background: var(--bg);
  border-bottom: 1px solid var(--border-light);
  white-space: nowrap;
}
.notif-table td {
  padding: 0.7rem 1rem;
  font-size: var(--font-size-sm);
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-light);
}
.notif-table tr:last-child td { border-bottom: none; }
.notif-table tr { transition: background var(--transition-fast); }
.notif-table tr:hover { background: var(--surface-hover); }
.notif-table .camp-title {
  font-weight: 600;
  color: var(--text-primary);
}
.notif-table .camp-body {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  max-width: 42ch;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  margin-top: 1px;
}
.notif-table .camp-aud {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--text-secondary);
}
.notif-table .camp-num {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
.notif-table .camp-num.ok { color: var(--success); }
.notif-table .camp-num.fail { color: var(--text-muted); }
.notif-table .camp-time {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  white-space: nowrap;
}
.notif-empty {
  text-align: center;
  padding: var(--space-xl);
  color: var(--text-muted);
}
.notif-empty svg[data-slot="icon"] {
  width: 36px; height: 36px;
  margin: 0 auto var(--space-sm);
  color: var(--border-strong);
  display: block;
}

/* ── Responsive ── */
@media (max-width: 767px) {
  .aud-grid { grid-template-columns: repeat(2, 1fr); }
  .notif-card-hdr { padding: var(--space-md); }
  .notif-card-bd { padding: var(--space-md); }
  .notif-save { padding: var(--space-md); }
}
</style>
@endpush

@section('content')
<div class="page-content" x-data="notificationsApp" data-counts='{{ json_encode($audiences) }}'>

  {{-- ===== Header ===== --}}
  <div class="fees-hdr">
    <div class="fees-hdr-info">
      <div class="fees-hdr-icon">
        <x-heroicon name="campaign" />
      </div>
      <div>
        <h1 class="fees-hdr-title">الإشعارات والتسويق</h1>
        <p class="fees-hdr-sub">أرسل إشعارًا فوريًا أو حملة تسويقية لجمهور مُستهدف — يصل فقط للمستخدمين الذين فعّلوا الإشعارات</p>
      </div>
    </div>
    <div style="display:flex;gap:var(--space-md);flex-wrap:wrap;">
      <div class="fees-summary-card" style="min-width:80px;padding:var(--space-sm) var(--space-md);gap:0;">
        <span class="sum-value" style="font-size:1.2rem;">{{ number_format($audiences['reachable']) }}</span>
        <span class="sum-label">جهاز متاح</span>
      </div>
      <div class="fees-summary-card" style="min-width:80px;padding:var(--space-sm) var(--space-md);gap:0;">
        <span class="sum-value" style="font-size:1.2rem;">{{ number_format($audiences['total']) }}</span>
        <span class="sum-label">إجمالي المستخدمين</span>
      </div>
    </div>
  </div>

  {{-- ===== Form Errors ===== --}}
  @if($errors->any())
    <div style="padding:var(--space-md) var(--space-lg);border-radius:var(--radius-main);background:var(--danger-light);color:var(--danger);border:1px solid var(--danger);font-size:var(--font-size-sm);">
      <strong>تعذّر الإرسال:</strong> {{ $errors->first() }}
    </div>
  @endif

  {{-- ===== Composer Form ===== --}}
  <form method="POST" action="{{ route('admin.notifications.send') }}">
    @csrf
    <input type="hidden" name="type" :value="audience">

    <div class="notif-grid">

      {{-- Left: fields --}}
      <div style="display:flex;flex-direction:column;gap:var(--space-md);">

        {{-- Message content --}}
        <div class="notif-card">
          <div class="notif-card-hdr"><h3>محتوى الرسالة</h3></div>
          <div class="notif-card-bd">
            <div>
              <label class="label label-required">العنوان</label>
              <input type="text" name="title" x-model="ntitle" value="{{ old('title') }}"
                     class="input" maxlength="255" required placeholder="مثال: عرض خاص لفترة محدودة">
            </div>
            <div>
              <label class="label label-required">النص</label>
              <textarea name="body" x-model="nbody" class="input" rows="3" maxlength="1000" required
                        placeholder="اكتب نص الإشعار الذي سيظهر للمستخدم…">{{ old('body') }}</textarea>
            </div>
          </div>
        </div>

        {{-- Audience --}}
        <div class="notif-card">
          <div class="notif-card-hdr"><h3>الجمهور المستهدف</h3></div>
          <div class="notif-card-bd">
            <div class="aud-grid">
              @foreach($audMeta as $key => $meta)
                <label class="aud" :class="audience==='{{ $key }}' && 'is-on'" @click="audience='{{ $key }}'">
                  <span class="aud-ico"><x-heroicon :name="$meta[2]" /></span>
                  <span class="aud-meta">
                    <span class="t">{{ $meta[0] }}</span>
                    <span class="c">{{ $meta[1] }}</span>
                  </span>
                  @if($key !== 'specific')
                    <span class="aud-count">{{ number_format($audiences[$key]) }}</span>
                  @endif
                </label>
              @endforeach
            </div>
            <div x-show="audience==='specific'" x-cloak style="margin-top:var(--space-md);">
              <label class="label">مُعرّفات المستخدمين (User IDs)</label>
              <textarea name="user_ids" class="input" style="font-family:monospace;font-size:var(--font-size-sm);" rows="2"
                        placeholder="13, 14, 21">{{ old('user_ids') }}</textarea>
              <p style="font-size:var(--font-size-xs);color:var(--text-muted);margin-top:0.35rem;">افصل بين المُعرّفات بفواصل.</p>
            </div>
          </div>
        </div>

        {{-- Schedule --}}
        <div class="notif-card">
          <div class="notif-card-bd">
            <label class="notif-toggle">
              <span>
                <span class="t">جدولة الإرسال</span>
                <span class="d">أرسل لاحقًا في وقت محدّد بدل الآن</span>
              </span>
              <span class="switch">
                <input type="checkbox" x-model="schedule">
                <span class="trk"></span>
              </span>
            </label>
            <div x-show="schedule" x-cloak>
              <label class="label">وقت الإرسال</label>
              <input type="datetime-local" name="scheduled_at" class="input" :required="schedule">
            </div>
          </div>
        </div>

      </div>

      {{-- Right: preview --}}
      <div class="preview-wrap">
        <div class="phone">
          <div class="phone-screen">
            <div class="phone-time">9:41</div>
            <div class="notif-badge">
              <span class="notif-badge-ico"><x-heroicon name="account_balance_wallet" /></span>
              <div class="notif-badge-bd">
                <div class="notif-badge-app">
                  <span>صكك</span>
                  <span>الآن</span>
                </div>
                <div class="notif-badge-title" x-text="ntitle || 'عنوان الإشعار'"></div>
                <div class="notif-badge-body" x-text="nbody || 'سيظهر نص الإشعار هنا أثناء الكتابة…'"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="reach-strip">
          <div class="reach-pill">
            <div class="n" x-text="reach === null ? '—' : reach"></div>
            <div class="l">سيصل إلى (جهاز)</div>
          </div>
        </div>
        <p style="font-size:var(--font-size-xs);color:var(--text-muted);display:flex;align-items:center;gap:0.35rem;">
          <x-heroicon name="info" style="width:14px;height:14px;flex:none;" />
          يصل الإشعار فقط للمستخدمين الذين فتحوا التطبيق وفعّلوا الإشعارات
        </p>
      </div>
    </div>

    {{-- Save bar --}}
    <div class="notif-save">
      <span class="hint" x-text="schedule ? 'سيُرسَل في الوقت المحدّد.' : 'سيُرسَل فورًا عند الضغط.'"></span>
      <button type="submit" class="btn btn-primary">
        <x-heroicon name="schedule_send" style="width:18px;height:18px;" x-show="schedule" />
        <x-heroicon name="send" style="width:18px;height:18px;" x-show="!schedule" />
        <span x-text="schedule ? 'جدولة الإرسال' : 'إرسال الآن'"></span>
      </button>
    </div>

  </form>

  {{-- ===== Campaign History ===== --}}
  <div class="notif-table-wrap">
    <div class="fees-section-header">
      <div class="fees-section-icon">
        <x-heroicon name="campaign" />
      </div>
      <span class="fees-section-title">سجلّ الحملات</span>
      <span class="fees-section-count">{{ $history->total() }} حملة</span>
    </div>

    @if($history->isNotEmpty())
      <table class="notif-table">
        <thead>
          <tr>
            <th>العنوان</th>
            <th>الجمهور</th>
            <th>الحالة</th>
            <th>وصل</th>
            <th>فشل</th>
            <th>التوقيت</th>
          </tr>
        </thead>
        <tbody>
          @foreach($history as $row)
            @php $st = $statusMeta[$row->status] ?? [$row->status, 'badge-secondary']; @endphp
            <tr>
              <td>
                <div class="camp-title">{{ $row->title }}</div>
                <div class="camp-body">{{ $row->body }}</div>
              </td>
              <td><span class="camp-aud">{{ $audMeta[$row->type][0] ?? $row->type }}</span></td>
              <td><span class="badge {{ $st[1] }}" style="font-size:0.7rem;">{{ $st[0] }}</span></td>
              <td><span class="camp-num ok">{{ number_format($row->sent_count) }}</span></td>
              <td><span class="camp-num fail">{{ number_format($row->failed_count) }}</span></td>
              <td><span class="camp-time">{{ optional($row->sent_at ?? $row->scheduled_at ?? $row->created_at)->format('Y-m-d · H:i') }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
      @if($history->hasPages())
        <div class="supp-paginate">
          {{ $history->links() }}
        </div>
      @endif
    @else
      <div class="notif-empty">
        <x-heroicon name="campaign" />
        <p>لم تُرسل أي حملة بعد. اكتب رسالتك الأولى بالأعلى.</p>
      </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  const el = document.querySelector('[x-data="notificationsApp"]');
  const raw = el?.dataset?.counts || '{}';
  let counts = {};
  try { counts = JSON.parse(raw); } catch(e) {}

  Alpine.data('notificationsApp', () => ({
    ntitle: '{{ old("title", "") }}',
    nbody: '{{ old("body", "") }}',
    audience: 'all',
    schedule: false,
    counts: counts,

    get reach() {
      return this.audience === 'specific' ? null : (this.counts[this.audience] ?? this.counts.all ?? 0);
    }
  }));
});
</script>
@endpush
