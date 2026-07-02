@extends('layouts.admin')

@section('title', 'تذكرة ' . $ticket->ticket_number)

@section('breadcrumbs')
<a href="{{ route('admin.support.index') }}" class="breadcrumb-item">الدعم الفني</a>
<span class="breadcrumb-item">{{ $ticket->ticket_number }}</span>
@endsection

@php
    $statusLabels = [
        'open' => ['مفتوحة', '#2563eb', '#eff6ff'],
        'in_progress' => ['قيد المعالجة', '#b45309', '#fffbeb'],
        'waiting_customer' => ['بانتظار العميل', '#7c3aed', '#f5f3ff'],
        'resolved' => ['تم الحل', '#15803d', '#f0fdf4'],
        'closed' => ['مغلقة', '#64748b', '#f1f5f9'],
    ];
    $statusAr = ['open'=>'مفتوحة','in_progress'=>'قيد المعالجة','waiting_customer'=>'بانتظار العميل','resolved'=>'تم الحل','closed'=>'مغلقة'];
    $priorityLabels = ['urgent'=>['عاجلة','#dc2626'],'high'=>['مرتفعة','#ea580c'],'medium'=>['متوسطة','#ca8a04'],'low'=>['منخفضة','#64748b']];
    $categoryLabels = ['general'=>'عام','transaction'=>'معاملة','card'=>'بطاقة','kyc'=>'تحقق','technical'=>'تقني','billing'=>'فواتير'];
    $st = $statusLabels[$ticket->status] ?? [$ticket->status,'#64748b','#f1f5f9'];
    $pr = $priorityLabels[$ticket->priority] ?? [$ticket->priority,'#64748b'];
@endphp

@push('styles')
<style>
/* ── Ticket show — SAKK clean ── */
.tshow {
  max-width: 920px;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
.tshow-card {
  background: var(--surface);
  border-radius: var(--radius-main);
  overflow: hidden;
}
.tshow-hdr {
  padding: var(--space-lg);
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-md);
}
.tshow-hdr-main { min-width: 0; flex: 1; }
.tshow-hdr-meta {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: 0.35rem;
  flex-wrap: wrap;
}
.tshow-number {
  font-family: monospace;
  font-weight: 800;
  font-size: 1.05rem;
  color: var(--text-primary);
  direction: ltr;
}
.tshow-badge {
  display: inline-flex;
  padding: 0.2rem 0.7rem;
  border-radius: var(--radius-full);
  font-size: 0.72rem;
  font-weight: 700;
}
.tshow-priority {
  font-weight: 700;
  font-size: 0.78rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}
.tshow-priority .dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.tshow-subject {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
}
.tshow-info {
  font-size: var(--font-size-sm);
  color: var(--text-muted);
  margin-top: 0.25rem;
}
.tshow-info strong { color: var(--text-primary); }
.tshow-info .ltr { direction: ltr; display: inline-block; }
.tshow-actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  flex: none;
}
.tshow-actions .act-row {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

/* ── Messages ── */
.tshow-thread {
  padding: var(--space-lg);
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
.tshow-msg {
  padding: var(--space-md);
  border-radius: var(--radius-main);
  line-height: 1.7;
  max-width: 85%;
}
.tshow-msg.customer {
  align-self: flex-start;
  background: var(--bg);
  border: 1px solid var(--border-light);
  margin-inline-end: 2rem;
}
.tshow-msg.agent {
  align-self: flex-end;
  background: var(--sukk-primary-soft);
  border: 1px solid var(--border-light);
  margin-inline-start: 2rem;
}
.tshow-msg.agent .tshow-msg-author { color: var(--sukk-primary); }
.tshow-msg.internal {
  align-self: stretch;
  background: #fffbeb;
  border: 1px solid #fde68a;
  margin: 0;
}
.tshow-msg-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.35rem;
  gap: var(--space-md);
}
.tshow-msg-author {
  font-size: var(--font-size-sm);
  font-weight: 700;
  color: var(--text-primary);
}
.tshow-msg-time {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  white-space: nowrap;
}
.tshow-msg-body {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  white-space: pre-wrap;
}
.tshow-msg-body:empty { display: none; }
.tshow-msg-internal-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.65rem;
  font-weight: 700;
  color: #b45309;
  background: #fef3c7;
  padding: 0.1rem 0.4rem;
  border-radius: var(--radius-sm);
  margin-top: 0.35rem;
}

/* ── Reply form ── */
.tshow-reply {
  padding: var(--space-lg);
}
.tshow-reply textarea {
  width: 100%;
  padding: var(--space-md);
  font-size: var(--font-size-sm);
  font-family: inherit;
  color: var(--text-primary);
  background: var(--input-bg);
  border: none;
  border-radius: var(--radius-sm);
  outline: none;
  resize: vertical;
  min-height: 90px;
  transition: box-shadow var(--transition-fast);
}
.tshow-reply textarea:focus {
  box-shadow: var(--shadow-focus);
  background: var(--surface);
}
.tshow-reply-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: var(--space-md);
  gap: var(--space-md);
}
.tshow-reply-footer .internal-label {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  font-size: var(--font-size-sm);
  color: var(--text-muted);
  cursor: pointer;
}
.tshow-reply-footer .internal-label input[type="checkbox"] {
  accent-color: var(--sukk-primary);
}
</style>
@endpush

@section('content')
<div class="page-content">

  <div class="tshow">

    {{-- Success --}}
    @if(session('success'))
      <div style="padding:0.7rem 1rem;border-radius:var(--radius-sm);font-size:var(--font-size-sm);background:var(--success-light);color:var(--success);border:1px solid var(--success);">
        {{ session('success') }}
      </div>
    @endif

    {{-- Ticket header --}}
    <div class="tshow-card">
      <div class="tshow-hdr">
        <div class="tshow-hdr-main">
          <div class="tshow-hdr-meta">
            <span class="tshow-number">{{ $ticket->ticket_number }}</span>
            <span class="tshow-badge" style="background:{{ $st[2] }};color:{{ $st[1] }};">{{ $st[0] }}</span>
            <span class="tshow-priority">
              <span class="dot" style="background:{{ $pr[1] }};"></span>
              {{ $pr[0] }}
            </span>
          </div>
          <h1 class="tshow-subject">{{ $ticket->subject }}</h1>
          <div class="tshow-info">
            {{ $categoryLabels[$ticket->category] ?? $ticket->category }} ·
            من <strong>{{ $ticket->user?->first_name }} {{ $ticket->user?->last_name }}</strong>
            @if($ticket->user?->email) <span class="ltr">({{ $ticket->user->email }})</span> @endif
            · {{ $ticket->created_at?->diffForHumans() }}
          </div>
          @if($ticket->assignedTo)
            <div class="tshow-info" style="margin-top:0.15rem;">
              مُسندة إلى: <strong>{{ $ticket->assignedTo->first_name }} {{ $ticket->assignedTo->last_name }}</strong>
            </div>
          @endif
        </div>

        <div class="tshow-actions">
          <div class="act-row">
            <form method="POST" action="{{ route('admin.support.status', $ticket) }}" style="display:flex;gap:var(--space-sm);align-items:center;">
              @csrf
              <select name="status" class="input"
                style="height:34px;padding:0 0.6rem;font-size:var(--font-size-sm);background:var(--input-bg);border:none;border-radius:var(--radius-sm);outline:none;font-family:inherit;color:var(--text-primary);">
                @foreach($statuses as $s)
                  <option value="{{ $s }}" @selected($ticket->status === $s)>{{ $statusAr[$s] ?? $s }}</option>
                @endforeach
              </select>
              <button type="submit" class="btn btn-primary btn-sm" style="height:34px;">تحديث</button>
            </form>
          </div>
          <div class="act-row">
            <form method="POST" action="{{ route('admin.support.assign', $ticket) }}">
              @csrf
              @if($ticket->assigned_to === auth()->id())
                <input type="hidden" name="release" value="1">
                <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;">إلغاء الإسناد</button>
              @else
                <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;">إسناد إليّ</button>
              @endif
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- Message thread --}}
    <div class="tshow-card">
      <div class="tshow-thread">
        @foreach($ticket->messages->sortBy('created_at') as $m)
          @php
            $isCustomer = $m->user_id === $ticket->user_id;
            $isInternal = $m->is_internal;
          @endphp
          <div class="tshow-msg {{ $isInternal ? 'internal' : ($isCustomer ? 'customer' : 'agent') }}">
            <div class="tshow-msg-hdr">
              <span class="tshow-msg-author">
                {{ $isCustomer ? ($ticket->user?->first_name . ' ' . $ticket->user?->last_name . ' (العميل)') : ($m->user?->first_name . ' (الدعم)') }}
              </span>
              <span class="tshow-msg-time">{{ $m->created_at?->diffForHumans() }}</span>
            </div>
            <div class="tshow-msg-body">{{ $m->message }}</div>
            @if($isInternal)
              <div class="tshow-msg-internal-tag">
                <x-heroicon name="lock" style="width:11px;height:11px;" />
                ملاحظة داخلية
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    {{-- Reply form --}}
    <div class="tshow-card">
      @if($errors->any())
        <div style="padding:0.7rem 1rem;font-size:var(--font-size-sm);background:var(--danger-light);color:var(--danger);border-bottom:1px solid var(--danger);">
          {{ $errors->first() }}
        </div>
      @endif
      <div class="tshow-reply">
        <form method="POST" action="{{ route('admin.support.reply', $ticket) }}">
          @csrf
          <textarea name="message" rows="4" required placeholder="اكتب ردك للعميل...">{{ old('message') }}</textarea>
          <div class="tshow-reply-footer">
            <label class="internal-label">
              <input type="checkbox" name="is_internal" value="1">
              <x-heroicon name="lock" style="width:14px;height:14px;" />
              ملاحظة داخلية — لا يراها العميل
            </label>
            <button type="submit" class="btn btn-primary">
              <x-heroicon name="send" style="width:16px;height:16px;" />
              إرسال
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>

</div>
@endsection
