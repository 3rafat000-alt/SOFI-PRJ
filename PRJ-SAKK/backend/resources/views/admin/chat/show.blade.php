@extends('layouts.admin')

@section('title', 'محادثة')

@php
    // User model exposes first_name/last_name (no `name` column) → full_name accessor.
    $custName = trim((string) ($conversation->user?->full_name ?? ''));
    $custName = $custName !== '' ? $custName : ('#' . $conversation->user_id);
    $custInitial = mb_substr(trim((string) ($conversation->user?->first_name ?? $custName)), 0, 1) ?: 'U';
@endphp

@section('breadcrumbs')
<a href="{{ route('admin.chat.index') }}" class="breadcrumb-item">الدردشة الحية</a>
<span class="breadcrumb-item">{{ $custName }}</span>
@endsection

@push('styles')
<style>
/* ── Chat thread — SAKK clean ── */
.chat-thread {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 190px);
  overflow: hidden;
  background: var(--surface);
  border-radius: var(--radius-main);
}
.chat-thread-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-md) var(--space-lg);
  border-bottom: 1px solid var(--border-light);
  flex: none;
}
.chat-thread-user {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}
.chat-thread-avatar {
  width: 40px; height: 40px;
  border-radius: 50%;
  background: var(--sukk-primary-soft);
  color: var(--sukk-primary);
  display: grid; place-items: center;
  font-size: 1rem;
  font-weight: 700;
  flex: none;
}
.chat-thread-name {
  font-weight: 700;
  color: var(--text-primary);
  font-size: 0.9rem;
}
.chat-thread-phone {
  font-size: var(--font-size-sm);
  color: var(--text-muted);
  direction: ltr;
  text-align: right;
  margin-top: 1px;
}
.chat-thread-actions {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  flex: none;
}
.chat-thread-status {
  display: inline-flex;
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-sm);
  font-size: 0.7rem;
  font-weight: 700;
}
.chat-thread-status.open { background: var(--success-light); color: var(--success); }
.chat-thread-status.closed { background: var(--bg); color: var(--text-muted); }

/* ── Messages ── */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: var(--space-lg);
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  background: var(--surface-hover);
}
.chat-bubble-wrap {
  max-width: 75%;
  display: flex;
  flex-direction: column;
}
.chat-bubble-wrap.agent { align-self: flex-end; }
.chat-bubble-wrap.customer { align-self: flex-start; }
.chat-bubble-wrap.system { align-self: center; }
.chat-bubble {
  padding: 0.55rem 0.85rem;
  border-radius: 14px;
  font-size: 0.85rem;
  line-height: 1.6;
  word-wrap: break-word;
}
.chat-bubble.agent {
  background: var(--sukk-primary);
  color: #fff;
  border-bottom-right-radius: 4px;
}
.chat-bubble.customer {
  background: var(--surface);
  color: var(--text-primary);
  border-bottom-left-radius: 4px;
  border: 1px solid var(--border-light);
}
.chat-bubble.system {
  background: var(--surface);
  color: var(--text-muted);
  font-size: 0.7rem;
  padding: 0.25rem 0.7rem;
  border-radius: var(--radius-full);
}
.chat-bubble-time {
  font-size: 0.6rem;
  color: var(--text-muted);
  margin-top: 0.15rem;
  padding: 0 0.25rem;
}
.chat-bubble-wrap.agent .chat-bubble-time { text-align: left; }
.chat-bubble-wrap.customer .chat-bubble-time { text-align: right; }

/* ── Composer ── */
.chat-composer {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--border-light);
  flex: none;
}
.chat-composer input {
  flex: 1;
  height: 40px;
  padding: 0 var(--space-md);
  font-size: var(--font-size-sm);
  font-family: inherit;
  color: var(--text-primary);
  background: var(--input-bg);
  border: none;
  border-radius: var(--radius-sm);
  outline: none;
  transition: box-shadow var(--transition-fast);
}
.chat-composer input:focus {
  box-shadow: var(--shadow-focus);
  background: var(--surface);
}
.chat-composer button {
  width: 40px; height: 40px;
  border-radius: var(--radius-sm);
  background: var(--sukk-primary);
  color: #fff;
  border: none;
  display: grid; place-items: center;
  cursor: pointer;
  transition: opacity var(--transition-fast);
  flex: none;
}
.chat-composer button:hover { opacity: 0.9; }
.chat-composer button svg[data-slot="icon"] { width: 18px; height: 18px; }
</style>
@endpush

@section('content')
<div class="page-content">

  <div class="chat-thread" id="chat-thread"
       data-poll-url="{{ route('admin.chat.poll', $conversation) }}"
       data-reply-url="{{ route('admin.chat.reply', $conversation) }}">

    {{-- Header --}}
    <div class="chat-thread-hdr">
      <div class="chat-thread-user">
        <div class="chat-thread-avatar">
          {{ $custInitial }}
        </div>
        <div>
          <div class="chat-thread-name">{{ $custName }}</div>
          <div class="chat-thread-phone">{{ $conversation->user?->phone ?? '' }}</div>
        </div>
      </div>
      <div class="chat-thread-actions">
        <span class="chat-thread-status {{ $conversation->status === 'open' ? 'open' : 'closed' }}">
          {{ $conversation->status === 'open' ? 'مفتوحة' : 'مغلقة' }}
        </span>
        <form method="POST" action="{{ route('admin.chat.status', $conversation) }}" style="display:inline">
          @csrf
          <input type="hidden" name="status" value="{{ $conversation->status === 'open' ? 'closed' : 'open' }}">
          <button class="btn btn-secondary btn-sm" style="height:34px;">
            {{ $conversation->status === 'open' ? 'إغلاق' : 'إعادة فتح' }}
          </button>
        </form>
      </div>
    </div>

    {{-- Messages --}}
    <div class="chat-messages" id="chat-messages">
      @foreach($messages as $m)
        @include('admin.chat._bubble', ['m' => $m])
      @endforeach
    </div>

    {{-- Composer --}}
    <form class="chat-composer" id="chat-form">
      <input type="text" id="chat-input" autocomplete="off" placeholder="اكتب رسالة…">
      <button type="submit">
        <x-heroicon name="send" />
      </button>
    </form>

  </div>

</div>

@push('scripts')
<script>
(function(){
    const root = document.getElementById('chat-thread');
    const box = document.getElementById('chat-messages');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const pollUrl = root.dataset.pollUrl;
    const replyUrl = root.dataset.replyUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let lastId = {{ $messages->last()['id'] ?? 0 }};

    function esc(s){ return String(s||'').replace(/[&<>"]/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }
    function bubble(m){
        const agent = m.sender_type === 'agent';
        const sys = m.sender_type === 'system';
        if(sys){
            return `<div class="chat-bubble-wrap system"><div class="chat-bubble system">${esc(m.body)}</div></div>`;
        }
        return `<div class="chat-bubble-wrap ${agent ? 'agent' : 'customer'}">
            <div class="chat-bubble ${agent ? 'agent' : 'customer'}">${esc(m.body)}</div>
            <div class="chat-bubble-time">${esc(m.time||'')}</div>
        </div>`;
    }
    function append(m){
        box.insertAdjacentHTML('beforeend', bubble(m));
        lastId = Math.max(lastId, m.id);
        box.scrollTop = box.scrollHeight;
    }
    box.scrollTop = box.scrollHeight;

    async function poll(){
        try{
            const r = await fetch(pollUrl + '?after=' + lastId, {headers:{'Accept':'application/json'}});
            const d = await r.json();
            (d.data?.messages||[]).forEach(m => {
                if(m.sender_type !== 'agent') append(m);
                else if(m.id > lastId) lastId = m.id;
            });
        }catch(e){}
    }
    let pollInt = setInterval(poll, 3000);
    document.addEventListener('visibilitychange', function(){
        if(document.hidden){ clearInterval(pollInt); pollInt = null; }
        else if(!pollInt) pollInt = setInterval(poll, 3000);
    });

    form.addEventListener('submit', async function(e){
        e.preventDefault();
        const body = input.value.trim();
        if(!body) return;
        input.value = '';
        input.disabled = true;
        try{
            const r = await fetch(replyUrl, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json'},
                body: JSON.stringify({body})
            });
            const d = await r.json();
            if(d.success) append(d.data);
        }catch(e){}
        finally{ input.disabled = false; input.focus(); }
    });
})();
</script>
@endpush
@endsection
