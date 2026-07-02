@php($agent = ($m['sender_type'] ?? '') === 'agent')
@php($sys = ($m['sender_type'] ?? '') === 'system')
@if($sys)
<div class="chat-bubble-wrap system">
    <div class="chat-bubble system">{{ $m['body'] ?? '' }}</div>
</div>
@else
<div class="chat-bubble-wrap {{ $agent ? 'agent' : 'customer' }}">
    <div class="chat-bubble {{ $agent ? 'agent' : 'customer' }}">{{ $m['body'] ?? '' }}</div>
    <div class="chat-bubble-time">{{ $m['time'] ?? '' }}</div>
</div>
@endif
