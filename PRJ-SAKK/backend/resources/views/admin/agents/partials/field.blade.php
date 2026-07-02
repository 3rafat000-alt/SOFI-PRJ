{{-- Reusable label/value pair for agent detail pages.
     Props: label (string), value (string), bold (bool), mono (bool), ltr (bool), raw (bool)
     Set raw=true when $value already contains safe pre-escaped HTML (e.g. Money::format output). --}}
@php
    $bold = $bold ?? false;
    $mono = $mono ?? false;
    $ltr = $ltr ?? false;
    $raw = $raw ?? false;
@endphp
<div>
    <p class="label">{{ $label }}</p>
    <p class="text-sm {{ $mono ? 'font-mono' : '' }} {{ $bold ? 'font-bold' : '' }}"
       style="color: var(--text-primary);"
       @if($ltr) dir="ltr" @endif>@if($raw){!! $value !!}@else{{ $value }}@endif</p>
</div>
