@forelse($conversations as $c)
<a href="{{ $c['url'] }}" class="chat-row">
    <span class="chat-row-dot" style="background:{{ $c['status']==='open' ? 'var(--success)' : 'var(--text-muted)' }};"></span>
    <div class="chat-row-body">
        <div class="chat-row-name">{{ $c['user_name'] }}
            <span class="chat-row-phone">{{ $c['user_phone'] ?? '' }}</span>
        </div>
        <div class="chat-row-preview">{{ $c['last_body'] ?? '' }}</div>
    </div>
    <div class="chat-row-meta">
        <div class="chat-row-time">{{ $c['last_at'] ?? '' }}</div>
        @if($c['unread'] > 0)
            <span class="chat-row-unread">{{ $c['unread'] }}</span>
        @endif
    </div>
</a>
@empty
<div class="chat-empty">لا توجد محادثات.</div>
@endforelse
