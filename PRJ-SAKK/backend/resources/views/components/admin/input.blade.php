{{--
  Component: <x-admin.input>

  A raw input primitive — renders a single <input> (or <textarea>/<select>) element
  styled with .sakk-input classes from /sakk-admin/admin.css.
  This is the low-level atom; for a labelled field-group (label + input + error + hint)
  use <x-admin.form> instead.

  Props:
    name        (string)       — name + id attribute. Required for form binding.
    type        (string)       — HTML input type: text|email|password|number|tel|date|
                                 time|search|url|file|hidden|textarea|select. Default: text.
    value       (mixed)        — Current value (passed through old() by callers).
    placeholder (string)       — Placeholder text (Arabic).
    size        (string)       — sm | md (default) | lg.
    state       (string|null)  — null (default) | valid | invalid — adds .is-valid/.is-invalid.
    disabled    (bool)         — Disables the element.
    readonly    (bool)         — Renders as read-only.
    required    (bool)         — Adds the HTML required attribute.
    autocomplete(string)       — HTML autocomplete value. Auto-set for password fields.
    icon        (string|null)  — SVG <path d="…"> or full <svg>…</svg> prepended to input.
    iconStart   (string|null)  — Alias of icon. SVG <path d="…"> or full <svg>…</svg>.
    iconEnd     (string|null)  — SVG <path d="…"> or full <svg>…</svg> for inline-end icon.
    iconStartLabel (string)    — aria-label for the start icon span. Default: empty.
    iconEndLabel   (string)    — aria-label for the end icon span. Default: empty.
    error       (string|null)  — Inline error message. Falls back to $errors->first($name).
    hint        (string|null)  — Helper text rendered below the input.
    prefix      (string|null)  — Static text prepended inside input (e.g., "$").
    suffix      (string|null)  — Static text appended inside input (e.g., "USD").
    options     (array)        — For type=select: ['value' => 'label'] associative array.
    selectEmpty (string|null)  — Optional empty placeholder option label for type=select.
    rows        (int)          — For type=textarea. Default: 4.
    resize      (string)       — For type=textarea: vertical (default) | none | both.

  Passthrough:
    All other HTML attributes (class, data-*, aria-*, x-*, wire:*, @events…) are merged
    via $attributes->merge(). The caller's class value is appended after .sakk-input base.

  Usage examples:

    Basic text:
      <x-admin.input name="username" placeholder="اسم المستخدم" :required="true"/>

    Email with validation state:
      <x-admin.input name="email" type="email" :value="old('email')" state="invalid"/>

    Search with leading icon (magnifier path):
      <x-admin.input name="q" type="search" placeholder="بحث…"
          icon-start="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>

    Password (eye-toggle handled by admin.js data-toggle-password):
      <x-admin.input name="password" type="password" size="lg"/>

    Select:
      <x-admin.input name="status" type="select"
          :value="old('status', $user->status)"
          select-empty="— اختر —"
          :options="['active'=>'نشط','inactive'=>'غير نشط','frozen'=>'محظور']"/>

    Textarea:
      <x-admin.input name="notes" type="textarea" :rows="5" placeholder="ملاحظات…"/>

    Disabled / read-only:
      <x-admin.input name="ref" :value="$ref" :disabled="true"/>
      <x-admin.input name="created_at" :value="$date" :readonly="true"/>
--}}

@props([
    'name'           => '',
    'type'           => 'text',
    'value'          => null,
    'placeholder'    => '',
    'size'           => 'md',
    'state'          => null,
    'disabled'       => false,
    'readonly'       => false,
    'required'       => false,
    'autocomplete'   => null,
    'icon'           => null,
    'iconStart'      => null,
    'iconEnd'        => null,
    'iconStartLabel' => '',
    'iconEndLabel'   => '',
    'error'          => null,
    'hint'           => null,
    'prefix'         => null,
    'suffix'         => null,
    'options'        => [],
    'selectEmpty'    => null,
    'rows'           => 4,
    'resize'         => 'vertical',
])

@php
/*
 * ── State class ──────────────────────────────────────────────────────────────
 * Maps the $state prop to a CSS modifier class defined in admin.css.
 */
$stateClass = match($state) {
    'valid'   => 'is-valid',
    'invalid' => 'is-invalid',
    default   => '',
};

/*
 * ── Size inline style ────────────────────────────────────────────────────────
 * admin.css base .input handles md sizing; sm/lg are supplemented inline so
 * this component stays self-contained without requiring extra CSS rules.
 */
$sizeStyle = match($size) {
    'sm'    => 'font-size:.8125rem;padding:0.35rem 0.7rem;min-height:36px;',
    'lg'    => 'font-size:.9375rem;padding:0.75rem 1rem;min-height:48px;',
    default => '',   // md — governed by .input in admin.css (42px)
};

/*
 * ── Icon size in px, scales with size prop ───────────────────────────────────
 */
$iconPx = match($size) {
    'sm'    => '14',
    'lg'    => '20',
    default => '16',
};

/*
 * ── Icon alias ───────────────────────────────────────────────────────────────
 * $icon is public API; falls back to iconStart for backward compat.
 */
$iconStart = $iconStart ?? $icon;

/*
 * ── Padding adjustment when icons / affixes present ─────────────────────────
 * admin.css defines .input-icon-start / .input-icon-end modifier classes
 * that add the correct logical-property padding (2.75rem).
 */
$iconStartClass = ($iconStart || $prefix) ? 'input-icon-start' : '';
$iconEndClass   = ($iconEnd || $suffix || $type === 'password') ? 'input-icon-end' : '';

/*
 * ── Final class for the <input>/<textarea>/<select> element ─────────────────
 */
$inputClass = trim(implode(' ', array_filter([
    'input',
    $type === 'select' ? 'select-input' : '',
    $stateClass,
    $iconStartClass,
    $iconEndClass,
])));

/*
 * ── autocomplete default ─────────────────────────────────────────────────────
 * Sensible defaults; caller can always override via the attribute.
 */
$resolvedAutocomplete = $autocomplete ?? match($type) {
    'password' => 'current-password',
    'email'    => 'email',
    'tel'      => 'tel',
    default    => 'off',
};

/*
 * ── textarea resize style ────────────────────────────────────────────────────
 */
$resizeStyle = match($resize) {
    'none'  => 'resize:none;',
    'both'  => 'resize:both;',
    default => 'resize:vertical;',
};

/*
 * ── Error / hint resolution ─────────────────────────────────────────────────
 * $error prop takes priority; fallback to Laravel $errors bag.
 */
$showError = $error ?? (isset($errors) && $errors->has($name) ? $errors->first($name) : null);
$showHint  = $hint && !$showError ? $hint : null;
@endphp



{{-- ============================================================
     RENDER
     Three branches: textarea | select | input (all other types)
     ============================================================ --}}

@if($type === 'textarea')
{{-- ── TEXTAREA ──────────────────────────────────────────── --}}
<textarea
    @if($name) id="{{ $name }}" name="{{ $name }}" @endif
    dir="rtl"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    @if($required)  required @endif
    @if($disabled)  disabled @endif
    @if($readonly)  readonly @endif
    {{ $attributes->merge(['class' => $inputClass]) }}
    @if($sizeStyle || $resizeStyle)
        style="{{ $sizeStyle }}{{ $resizeStyle }}{{ $attributes->get('style','') }}"
    @endif
>{{ $value }}</textarea>

@if($showError)
    <p class="field-error" role="alert" aria-live="assertive" style="margin-top:4px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
             viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
             style="flex-shrink:0">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
                     10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
        {{ $showError }}
    </p>
@elseif($showHint)
    <p class="hint" style="margin-top:4px;">{{ $showHint }}</p>
@endif

@elseif($type === 'select')
{{-- ── SELECT ────────────────────────────────────────────── --}}
<select
    @if($name) id="{{ $name }}" name="{{ $name }}" @endif
    dir="rtl"
    @if($required)  required @endif
    @if($disabled)  disabled @endif
    {{ $attributes->merge(['class' => $inputClass]) }}
    @if($sizeStyle) style="{{ $sizeStyle }}{{ $attributes->get('style','') }}" @endif
>
    @if($selectEmpty !== null)
        <option value="">{{ $selectEmpty }}</option>
    @endif

    @foreach($options as $optVal => $optLabel)
        <option
            value="{{ $optVal }}"
            {{ (string)$value === (string)$optVal ? 'selected' : '' }}
        >{{ $optLabel }}</option>
    @endforeach
</select>

@if($showError)
    <p class="field-error" role="alert" aria-live="assertive" style="margin-top:4px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
             viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
             style="flex-shrink:0">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
                     10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
        {{ $showError }}
    </p>
@elseif($showHint)
    <p class="hint" style="margin-top:4px;">{{ $showHint }}</p>
@endif

@else
{{-- ── INPUT (text / email / password / number / tel / date / search / url / file / hidden …) ── --}}
<div class="sakk-input-wrap">

    {{-- Leading icon (inline-start in RTL) --}}
    @if($iconStart)
        <span
            class="sakk-input-icon sakk-input-icon--s"
            aria-hidden="{{ $iconStartLabel ? 'false' : 'true' }}"
            @if($iconStartLabel) aria-label="{{ $iconStartLabel }}" @endif
        >
            @if(str_starts_with(trim($iconStart), '<'))
                {!! $iconStart !!}
            @else
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="{{ $iconPx }}" height="{{ $iconPx }}"
                     viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="{{ $iconStart }}"/>
                </svg>
            @endif
        </span>
    @endif

    {{-- Prefix text (e.g., "$") --}}
    @if($prefix)
        <span class="input-affix input-prefix" aria-hidden="true">{{ $prefix }}</span>
    @endif

    <input
        type="{{ $type }}"
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        dir="rtl"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $resolvedAutocomplete }}"
        @if($required)  required @endif
        @if($disabled)  disabled @endif
        @if($readonly)  readonly @endif
        {{ $attributes->merge(['class' => $inputClass]) }}
        @if($sizeStyle) style="{{ $sizeStyle }}{{ $attributes->get('style','') }}" @endif
    >

    {{-- Suffix text (e.g., "USD") --}}
    @if($suffix)
        <span class="input-affix input-suffix" aria-hidden="true">{{ $suffix }}</span>
    @endif

    {{-- Trailing icon (inline-end in RTL) — for password: eye toggle button --}}
    @if($type === 'password')
        {{-- Eye toggle — wired by admin.js via data-toggle-password --}}
        <button
            type="button"
            class="sakk-pw-toggle"
            data-toggle-password="{{ $name }}"
            aria-label="إظهار/إخفاء كلمة المرور"
            tabindex="0"
        >
            {{-- Eye icon (outline stroke — no fill, no CDN) --}}
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="{{ $iconPx }}" height="{{ $iconPx }}"
                 viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    @elseif($iconEnd)
        <span
            class="sakk-input-icon sakk-input-icon--e"
            aria-hidden="{{ $iconEndLabel ? 'false' : 'true' }}"
            @if($iconEndLabel) aria-label="{{ $iconEndLabel }}" @endif
        >
            @if(str_starts_with(trim($iconEnd), '<'))
                {!! $iconEnd !!}
            @else
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="{{ $iconPx }}" height="{{ $iconPx }}"
                     viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="{{ $iconEnd }}"/>
                </svg>
            @endif
        </span>
    @endif

</div>

{{-- Error message --}}
@if($showError)
    <p class="field-error" role="alert" aria-live="assertive" style="margin-top:4px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
             viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
             style="flex-shrink:0">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
                     10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
        {{ $showError }}
    </p>
@elseif($showHint)
    <p class="hint" style="margin-top:4px;">{{ $showHint }}</p>
@endif
@endif
