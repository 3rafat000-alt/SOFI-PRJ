@php
    $isActive = ($sortField ?? '') === $key;
    $nextDir = ($isActive && ($sortDir ?? 'desc') === 'asc') ? 'desc' : 'asc';
@endphp
<a href="{{ route('admin.merchants.index', array_merge(request()->query(), ['sort' => $key, 'dir' => $nextDir])) }}"
   class="sort-header {{ $isActive ? 'is-active' : '' }}">
    {{ $label }}
    @if($isActive)
    <x-heroicon name="arrow_upward" aria-hidden="true" x-show="($sortDir ?? 'desc') === 'asc'" />
<x-heroicon name="arrow_downward" aria-hidden="true" x-show="!(($sortDir ?? 'desc') === 'asc')" />
    @endif
</a>
