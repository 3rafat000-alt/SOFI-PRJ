@props(['name' => ''])

@php
$map = config('heroicons-map', []);
$heroName = $map[$name] ?? 'question-mark-circle';
$component = 'heroicon-o-' . $heroName;

// Inject the default size ONLY when the caller didn't pass one, so a custom
// w-*/h-*/size-* wins instead of conflicting with a hardcoded "w-5 h-5".
// NOTE: the echo MUST stay literally `$attributes->merge(...)` — Blade's tag
// compiler only recognises attribute-bag spreads written on `$attributes`.
$hasSize = preg_match('/(?:^|\s)(?:w|h|size)-/', (string) $attributes->get('class', '')) === 1;
$defaults = $hasSize ? [] : ['class' => 'w-5 h-5'];
@endphp

<x-dynamic-component :component="$component" {{ $attributes->merge($defaults) }} />
