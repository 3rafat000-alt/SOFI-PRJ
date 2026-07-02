@extends('layouts.admin')

@section('title', 'الدردشة الحية')
@section('breadcrumbs')
<span class="breadcrumb-item">الدردشة الحية</span>
@endsection

@push('styles')
<style>
/* ── Chat inbox — SAKK clean ── */
.chat-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
  padding: var(--space-lg);
  background: var(--surface);
  border-radius: var(--radius-main);
}
.chat-hdr-info {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}
.chat-hdr-icon {
  width: 44px; height: 44px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  flex: none;
}
.chat-hdr-icon svg[data-slot="icon"] { width: 22px; height: 22px; }
.chat-hdr-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}
.chat-hdr-sub {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  margin-top: 2px;
}
.chat-hdr-unread {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.4rem 1.2rem;
  background: var(--bg);
  border-radius: var(--radius-main);
}
.chat-hdr-unread .unread-val {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--sukk-primary);
  line-height: 1.1;
  font-variant-numeric: tabular-nums;
}
.chat-hdr-unread .unread-label {
  font-size: var(--font-size-xs);
  color: var(--text-muted);
  font-weight: 600;
}

/* ── Filter tabs ── */
.chat-tabs {
  display: flex;
  gap: var(--space-sm);
  flex-wrap: wrap;
}
.chat-tab {
  padding: 0.4rem 1rem;
  font-size: var(--font-size-sm);
  font-weight: 600;
  border-radius: var(--radius-sm);
  transition: all var(--transition-fast);
  cursor: pointer;
  font-family: inherit;
  text-decoration: none;
  color: var(--text-secondary);
  background: var(--surface);
}
.chat-tab:hover { background: var(--surface-hover); color: var(--text-primary); }
.chat-tab--active {
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  font-weight: 700;
}

/* ── Conversation list ── */
.chat-list-wrap {
  background: var(--surface);
  border-radius: var(--radius-main);
  overflow: hidden;
}
.chat-row {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--border-light);
  text-decoration: none;
  color: inherit;
  transition: background var(--transition-fast);
}
.chat-row:last-child { border-bottom: none; }
.chat-row:hover { background: var(--surface-hover); }
.chat-row-dot {
  width: 9px; height: 9px;
  border-radius: 50%;
  flex: none;
}
.chat-row-body {
  flex: 1;
  min-width: 0;
}
.chat-row-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.85rem;
}
.chat-row-phone {
  font-weight: 400;
  color: var(--text-muted);
  font-size: 0.72rem;
}
.chat-row-preview {
  color: var(--text-muted);
  font-size: 0.75rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 1px;
}
.chat-row-meta {
  text-align: left;
  flex: none;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}
.chat-row-time {
  font-size: 0.65rem;
  color: var(--text-muted);
  white-space: nowrap;
}
.chat-row-unread {
  min-width: 20px;
  text-align: center;
  padding: 0.1rem 0.4rem;
  font-size: 0.65rem;
  font-weight: 700;
  color: #fff;
  background: var(--danger);
  border-radius: var(--radius-sm);
  line-height: 1.3;
}
.chat-empty {
  padding: 2rem;
  text-align: center;
  color: var(--text-muted);
}
</style>
@endpush

@section('content')
<div class="page-content">

  {{-- Header --}}
  <div class="chat-hdr">
    <div class="chat-hdr-info">
      <div class="chat-hdr-icon">
        <x-heroicon name="forum" />
      </div>
      <div>
        <h1 class="chat-hdr-title">الدردشة الحية</h1>
        <p class="chat-hdr-sub">محادثات الدعم المباشرة مع المستخدمين — تتحدّث تلقائياً</p>
      </div>
    </div>
    <div class="chat-hdr-unread">
      <span class="unread-val" id="unread-total">{{ $unreadTotal }}</span>
      <span class="unread-label">غير مقروء</span>
    </div>
  </div>

  {{-- Filter tabs --}}
  <div class="chat-tabs">
    @foreach(['all'=>'الكل','open'=>'مفتوحة','unread'=>'غير مقروءة','closed'=>'مغلقة'] as $k => $lbl)
      <a href="{{ route('admin.chat.index', ['filter' => $k]) }}"
         class="chat-tab {{ $filter === $k ? 'chat-tab--active' : '' }}">{{ $lbl }}</a>
    @endforeach
  </div>

  {{-- Conversation list --}}
  <div class="chat-list-wrap">
    <div id="chat-list">
      @include('admin.chat._rows', ['conversations' => $conversations])
    </div>
  </div>

</div>

@push('scripts')
<script>
(function(){
    const feed = "{{ route('admin.chat.feed', ['filter' => $filter]) }}";
    const list = document.getElementById('chat-list');
    const totalEl = document.getElementById('unread-total');
    const navBadge = document.getElementById('chat-nav-unread');

    function rowHtml(c){
        const dotColor = c.status === 'open' ? 'var(--success)' : 'var(--text-muted)';
        const unread = c.unread > 0 ? `<span class="chat-row-unread">${c.unread}</span>` : '';
        return `<a href="${escAttr(c.url)}" class="chat-row">
            <span class="chat-row-dot" style="background:${dotColor};"></span>
            <div class="chat-row-body">
                <div class="chat-row-name">${esc(c.user_name)} <span class="chat-row-phone">${esc(c.user_phone||'')}</span></div>
                <div class="chat-row-preview">${esc(c.last_body||'')}</div>
            </div>
            <div class="chat-row-meta">
                <div class="chat-row-time">${esc(c.last_at||'')}</div>
                ${unread}
            </div>
        </a>`;
    }
    function esc(s){ return String(s||'').replace(/[&<>"]/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }
    function escAttr(s){ return String(s||'').replace(/"/g,'&quot;'); }

    async function refresh(){
        try{
            const r = await fetch(feed, {headers:{'Accept':'application/json'}});
            const d = await r.json();
            if(!d.success) return;
            totalEl.textContent = d.data.unread_total;
            if(navBadge){ navBadge.textContent = d.data.unread_total; navBadge.style.display = d.data.unread_total > 0 ? '' : 'none'; }
            list.innerHTML = d.data.conversations.length
                ? d.data.conversations.map(rowHtml).join('')
                : '<div class="chat-empty">لا توجد محادثات.</div>';
        }catch(e){}
    }
    let pollInt = setInterval(refresh, 4000);
    document.addEventListener('visibilitychange', function(){
        if(document.hidden){ clearInterval(pollInt); pollInt = null; }
        else if(!pollInt) pollInt = setInterval(refresh, 4000);
    });
})();
</script>
@endpush
@endsection
