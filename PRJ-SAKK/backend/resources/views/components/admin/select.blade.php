{{--
  Component: <x-admin.select>

  A standalone, reusable <select> control that complements <x-admin.form> for use
  wherever a bare select element is needed without the full field-group wrapper
  (e.g., inline table filters, quick-form controls, segmented filter bars).

  Props:
    name        (string)       — Field name + id base. Required.
    label       (string|null)  — Visible label above the control. Omit for label-less usage.
    options     (array)        — Associative: ['value' => 'Arabic label']. Required.
    value       (mixed|null)   — Pre-selected value (respects old() from session).
    placeholder (string|null)  — Empty first option text, e.g. "— اختر —". Set null to omit.
    required    (bool)         — Adds required attr + red asterisk on label.
    disabled    (bool)         — Disables the control.
    hint        (string|null)  — Helper text rendered below the control.
    error       (string|null)  — Inline error message. Falls back to $errors->first($name).
    icon        (string|null)  — SVG <path d="…"> or full <svg>…</svg> prepended inside wrapper.
    groups      (array)        — Optgroup support: ['Group' => ['val'=>'Label', ...], ...].
                                 When non-empty, renders <optgroup> instead of flat options.
    size        (string)       — sm | md (default) | lg — adjusts height/padding inline.
    multiple    (bool)         — Enables multi-select (name auto-appended with []).

  Usage:
    Basic:
      <x-admin.select name="status" label="الحالة"
          :options="['active'=>'نشط','inactive'=>'غير نشط']"
          placeholder="— اختر الحالة —"/>

    Pre-selected + required:
      <x-admin.select name="role" label="الصلاحية" :required="true"
          :options="['admin'=>'مدير','user'=>'مستخدم']"
          :value="old('role', $user->role ?? '')"/>

    Inline filter (no label, sm):
      <x-admin.select name="per_page" :options="[10=>'10',25=>'25',50=>'50']"
          size="sm" :value="request('per_page',10)"/>

    Disabled:
      <x-admin.select name="currency" label="العملة" :disabled="true"
          :options="['SAR'=>'ريال سعودي']" :value="'SAR'"/>

    Multiple:
      <x-admin.select name="tags" label="الوسوم" :multiple="true"
          :options="['a'=>'أ','b'=>'ب']" :value="old('tags',[])"/>

  CSS classes used (all in /sakk-admin/admin.css):
    .input / .select-input  — base field style shared with text inputs
    .is-invalid             — red border on validation error
    .label / .label-required — field label styles
    .hint                   — muted helper text
    .field-error            — error message row

  No CDN, no Tailwind utility classes. Full RTL via dir="rtl" + CSS logical properties.
--}}

@props([
    'name'        => '',
    'label'       => null,
    'options'     => [],
    'value'       => null,
    'placeholder' => null,
    'required'    => false,
    'disabled'    => false,
    'hint'        => null,
    'error'       => null,
    'icon'        => null,
    'groups'      => [],
    'size'        => 'md',
    'multiple'    => false,
])

@php
/*
 * Field id — scoped to avoid collisions when multiple selects appear on a page.
 */
$fieldId  = 'select-' . $name;

/*
 * Error state — integrates with Laravel's $errors bag out of the box.
 */
$hasError = isset($errors) && $errors->has($name);

/*
 * Error / hint resolution — $error prop takes priority over $errors bag.
 */
$showError = $error ?? ($hasError && isset($errors) ? $errors->first($name) : null);
$showHint  = $hint && !$showError ? $hint : null;

/*
 * Respect flash input: old() takes priority over the passed :value prop.
 * For multi-select, old() returns an array; fall back to $value (array or scalar).
 */
$oldVal = old($name, $value);

/*
 * Icon sizing matches input size rhythm.
 */
$iconPx = match($size) {
    'sm'    => '14',
    'lg'    => '20',
    default => '16',
};
$hasIcon = $icon !== null;
$iconPadClass = $hasIcon ? 'input-icon-start' : '';

/*
 * CSS class string for the <select> element.
 * - .input + .select-input → base style shared with text inputs
 * - .is-invalid            → red focus ring on validation failure
 * - .input-icon-start      → padding for leading icon
 */
$selectClass = trim(implode(' ', array_filter([
    'input',
    'select-input',
    $hasError ? 'is-invalid' : '',
    $iconPadClass,
])));

/*
 * Size overrides — supplementary inline styles so the component does not
 * require new CSS classes for each variant. Matches the input height rhythm
 * used by text inputs (sm ≈ 32 px, md ≈ 40 px, lg ≈ 48 px).
 */
$sizeStyle = match($size) {
    'sm'    => 'padding:0.35rem 0.65rem;font-size:0.8125rem;min-height:36px;',
    'lg'    => 'padding:0.75rem 1rem;font-size:0.9375rem;min-height:48px;',
    default => '',   // md: .input in admin.css governs (42px)
};

/*
 * For multi-select the name attribute needs the [] suffix so PHP parses it
 * as an array on form submission.
 */
$fieldName = $multiple ? $name . '[]' : $name;

/*
 * Normalize $oldVal for multi-select comparisons — always cast to array.
 */
$selectedValues = $multiple
    ? (array) $oldVal
    : [(string) $oldVal];

/*
 * Groups mode — when $groups is non-empty, render <optgroup> wrappers.
 */
$hasGroups = !empty($groups);
@endphp

{{-- ======================================================================
     Field wrapper — flex column, 0-gap so label/input/hint/error are tight
     ====================================================================== --}}
<div class="field-group" dir="rtl" {{ $attributes->only(['id','class','style','data-*','x-*','wire:*']) }}>

    {{-- Label ------------------------------------------------------------ --}}
    @if($label)
    <label
        for="{{ $fieldId }}"
        class="label {{ $required ? 'label-required' : '' }}"
    >{{ $label }}</label>
    @endif

    {{-- Select control wrapper (relative for icon positioning) ---------- --}}
    <div class="select-wrap">

        {{-- Leading icon (inline-start in RTL) --}}
        @if($icon)
            <span class="sakk-select-icon" aria-hidden="true">
                @if(str_starts_with(trim($icon), '<'))
                    {!! $icon !!}
                @else
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="{{ $iconPx }}" height="{{ $iconPx }}"
                         viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         aria-hidden="true">
                        <path d="{{ $icon }}"/>
                    </svg>
                @endif
            </span>
        @endif

        <select
            id="{{ $fieldId }}"
            name="{{ $fieldName }}"
            class="{{ $selectClass }}"
            @if($sizeStyle) style="{{ $sizeStyle }}" @endif
            @if($required)  required                 @endif
            @if($disabled)  disabled                 @endif
            @if($multiple)  multiple                 @endif
            aria-required="{{ $required ? 'true' : 'false' }}"
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if($hasError)  aria-describedby="{{ $fieldId }}-error"   @endif
            @if($showHint) aria-describedby="{{ $fieldId }}-hint" @endif
            {{-- Pass through any extra HTML attrs (e.g., wire:model, x-model, data-*) --}}
            {{ $attributes->except(['id','class','style','name','required','disabled','multiple']) }}
        >
            {{-- Empty placeholder option (no value, not selectable) ---------- --}}
            @if($placeholder !== null)
                <option value="" {{ in_array('', $selectedValues, true) ? 'selected' : '' }}>
                    {{ $placeholder }}
                </option>
            @endif

            {{-- Render options (flat or grouped via <optgroup>) ------------ --}}
            @if($hasGroups)
                @foreach($groups as $groupLabel => $groupOptions)
                    <optgroup label="{{ $groupLabel }}">
                        @foreach($groupOptions as $optVal => $optLabel)
                            <option
                                value="{{ $optVal }}"
                                {{ in_array((string)$optVal, $selectedValues, true) ? 'selected' : '' }}
                            >{{ $optLabel }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            @else
                @forelse($options as $optVal => $optLabel)
                    <option
                        value="{{ $optVal }}"
                        {{ in_array((string)$optVal, $selectedValues, true) ? 'selected' : '' }}
                    >{{ $optLabel }}</option>
                @empty
                    {{-- No options: render a disabled prompt so the field still shows --}}
                    <option value="" disabled>— لا توجد خيارات —</option>
                @endforelse
            @endif
        </select>
    </div>{{-- /.select-wrap --}}

    {{-- Validation error ------------------------------------------------- --}}
    @if($showError)
        <p id="{{ $fieldId }}-error" class="field-error" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                 viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
                 style="flex-shrink:0">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
                         10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            {{ $showError }}
        </p>
    @elseif($showHint)
        <p id="{{ $fieldId }}-hint" class="hint">{{ $showHint }}</p>
    @endif

</div>
