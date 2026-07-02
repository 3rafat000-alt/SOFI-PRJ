{{--
  Component: <x-admin.form>
  -----------------------------------------------------------------------
  Anonymous Blade component — wraps an HTML <form> with SAKK design system
  styling plus an inline field group (label + input/select/textarea/toggle
  + error message + hint). Two usage modes:

  MODE A — Form wrapper (renders a <form> tag)
    Set $action and/or $method. The slot holds fields + @csrf.
    Named slot $actions = submit/cancel button row (rendered in form-footer).

    <x-admin.form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <x-admin.input name="name" label="الاسم الكامل" :required="true"/>
        <x-slot:actions>
            <x-admin.button type="submit" icon="save">حفظ</x-admin.button>
        </x-slot:actions>
    </x-admin.form>

  MODE B — Field group only (no <form> tag, $action is null)
    Renders one labeled field + error + hint. The slot is NOT rendered;
    use $name/$label/$type etc. props to describe the single field.

    <x-admin.form name="email" label="البريد الإلكتروني" type="email"
                  :required="true" placeholder="you@example.com"
                  :value="old('email')"/>

    <x-admin.form name="status" label="الحالة" type="select"
                  :options="['active'=>'نشط','inactive'=>'غير نشط']"
                  :value="old('status', $user->status ?? '')"/>

    <x-admin.form name="active" label="تفعيل الحساب" type="toggle"
                  :value="$user->is_active ?? false"/>

  -----------------------------------------------------------------------
  Props (form wrapper):
    $action    (string|null)  — form action URL.
    $method    (string)       — HTTP verb: GET|POST|PUT|PATCH|DELETE. Default: POST.
                               Non-GET/POST verbs auto-add hidden _method input.
    $enctype   (string|null)  — e.g. "multipart/form-data" for file uploads.
    $hasFiles  (bool)         — Auto-sets enctype="multipart/form-data". Default: false.
    $validate  (bool)         — true = add novalidate (backend validation). Default: true.
    $id        (string|null)  — id on the <form> element.
    $gap       (string)       — CSS gap between field rows. Default: "1.25rem".
    $cols      (int)          — Grid columns (1–3). Default: 1.
    $noCard    (bool)         — true = skip the card wrapper, render just the form.

  Props (field — used in MODE B or inside <x-admin.input> / sibling component):
    $name         (string)    — field name / id suffix. Required in MODE B.
    $label        (string)    — field label text (Arabic).
    $type         (string)    — text|email|password|number|tel|date|time|datetime-local
                                |file|search|url|color|select|textarea|toggle. Default: text.
    $value        (mixed)     — default / old() value.
    $required     (bool)      — adds required attr + red asterisk.
    $disabled     (bool)      — disables the field.
    $readonly     (bool)      — makes the field read-only.
    $placeholder  (string)    — placeholder text.
    $hint         (string)    — helper text rendered below the field.
    $icon         (string)    — inline-start icon (Material Icons name) for text inputs.
    $iconEnd      (string)    — inline-end icon name (decorative).
    $options      (array)     — for type=select: ['value' => 'label'] map.
    $rows         (int)       — for type=textarea. Default: 3.
    $autocomplete (string)    — HTML autocomplete attribute. Default: 'off'.
    $maxlength    (int|null)  — maxlength attr for text/textarea inputs.
--}}
@props([
    {{-- Form wrapper --}}
    'action'       => null,
    'method'       => 'POST',
    'enctype'      => null,
    'id'           => null,
    'gap'          => '1.25rem',
    'cols'         => 1,
    'noCard'       => false,
    'hasFiles'     => false,
    'validate'     => true,

    {{-- Field group --}}
    'name'         => '',
    'label'        => '',
    'type'         => 'text',
    'value'        => null,
    'required'     => false,
    'disabled'     => false,
    'readonly'     => false,
    'placeholder'  => '',
    'hint'         => null,
    'icon'         => null,
    'iconEnd'      => null,
    'options'      => [],
    'rows'         => 3,
    'autocomplete' => 'off',
    'maxlength'    => null,
])

@php
    /* ── Determine rendering mode ─────────────────────────────────── */
    $isFormWrapper = $action !== null || isset($actions);
    $isSingleField = !$isFormWrapper && $name !== '';

    /* ── Form method spoofing ─────────────────────────────────────── */
    $verb        = strtoupper($method);
    $htmlMethod  = in_array($verb, ['GET', 'POST']) ? $verb : 'POST';
    $needsSpoof  = !in_array($verb, ['GET', 'POST']);

    /* ── File upload ─────────────────────────────────────────────────────── */
    if ($hasFiles && !$enctype) {
        $enctype = 'multipart/form-data';
    }

    /* ── Grid columns class ───────────────────────────────────────── */
    $colsStyle = $cols > 1
        ? "display:grid;grid-template-columns:repeat({$cols},1fr);gap:{$gap};"
        : "display:flex;flex-direction:column;gap:{$gap};";

    /* ── Field helpers ────────────────────────────────────────────── */
    $fieldId     = $name ? 'field-' . $name : null;
    $hasError    = $name ? $errors->has($name) : false;
    $oldVal      = $name ? old($name, $value) : $value;
    $inputCls    = 'input' . ($hasError ? ' is-invalid' : '');
    $hasIconS    = $icon    !== null;
    $hasIconE    = $iconEnd !== null;
    if ($hasIconS) $inputCls .= ' input-icon-start';
    if ($hasIconE) $inputCls .= ' input-icon-end';
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     WRAPPER FORM — rendered when $action is set or $actions slot exists
     ═══════════════════════════════════════════════════════════════════ --}}
@if($isFormWrapper)

    @php
        $formEl = $noCard ? '' : '';
    @endphp

    @if(!$noCard)
    <div class="card" style="overflow:visible;">
    @endif

        <form
            @if($id)    id="{{ $id }}"       @endif
            method="{{ $htmlMethod }}"
            @if($action) action="{{ $action }}" @endif
            @if($enctype) enctype="{{ $enctype }}" @endif
            dir="rtl"
            @if($validate) novalidate @endif
            {{ $attributes->except(['class']) }}
        >
            @if($needsSpoof)
                @method($verb)
            @endif

            {{-- Field grid / stack --}}
            <div class="card-body" style="{{ $colsStyle }}">
                {{ $slot }}
            </div>

            {{-- Actions footer --}}
            @if(isset($actions))
            <div class="card-footer" style="
                display:flex;
                align-items:center;
                justify-content:flex-start;
                gap:0.75rem;
                padding:1rem 1.5rem;
                background:var(--surface-hover);
                border-radius:0 0 var(--radius-main) var(--radius-main);
                box-shadow:inset 0 1px 0 var(--border-light);
            ">
                {{ $actions }}
            </div>
            @endif

        </form>

    @if(!$noCard)
    </div>{{-- /.card --}}
    @endif

{{-- ═══════════════════════════════════════════════════════════════════
     SINGLE FIELD GROUP — label + input + error + hint
     ═══════════════════════════════════════════════════════════════════ --}}
@elseif($isSingleField)

    <div
        class="field-group"
        dir="rtl"
        {{ $attributes->except(['class','name','value','type','required','disabled','readonly','placeholder','hint','rows','options','autocomplete','maxlength','icon','iconEnd']) }}
    >

        {{-- Label (skip for toggle — it wraps its own label below) --}}
        @if($label && $type !== 'toggle')
            <label
                for="{{ $fieldId }}"
                class="label{{ $required ? ' label-required' : '' }}"
            >{{ $label }}</label>
        @endif

        {{-- ── textarea ────────────────────────────────────────────── --}}
        @if($type === 'textarea')

            <textarea
                id="{{ $fieldId }}"
                name="{{ $name }}"
                rows="{{ $rows }}"
                class="{{ $inputCls }}"
                placeholder="{{ $placeholder }}"
                @if($required)  required  @endif
                @if($disabled)  disabled  @endif
                @if($readonly)  readonly  @endif
                @if($maxlength) maxlength="{{ $maxlength }}" @endif
                autocomplete="{{ $autocomplete }}"
                style="resize:vertical;min-height:{{ $rows * 2 }}rem;"
            >{{ $oldVal }}</textarea>

        {{-- ── select ─────────────────────────────────────────────── --}}
        @elseif($type === 'select')

            <div class="input-group">
                <select
                    id="{{ $fieldId }}"
                    name="{{ $name }}"
                    class="{{ $inputCls }} select-input"
                    @if($required) required  @endif
                    @if($disabled) disabled  @endif
                    autocomplete="{{ $autocomplete }}"
                >
                    @if($placeholder)
                        <option value="" @if(!$oldVal) selected @endif disabled>{{ $placeholder }}</option>
                    @endif
                    @foreach($options as $optVal => $optLabel)
                        <option value="{{ $optVal }}"
                            {{ (string)$oldVal === (string)$optVal ? 'selected' : '' }}>
                            {{ $optLabel }}
                        </option>
                    @endforeach
                </select>

                {{-- Chevron icon inline SVG (no CDN) --}}
                <span class="input-icon input-icon-e" aria-hidden="true" style="pointer-events:none;">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6l4 4 4-4" stroke="currentColor"
                              stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </div>

        {{-- ── toggle / checkbox ──────────────────────────────────── --}}
        @elseif($type === 'toggle')

            <label
                style="display:inline-flex;align-items:center;gap:0.625rem;cursor:{{ $disabled ? 'not-allowed' : 'pointer' }};user-select:none;"
                @if($disabled) aria-disabled="true" @endif
            >
                <input
                    type="checkbox"
                    id="{{ $fieldId }}"
                    name="{{ $name }}"
                    class="toggle"
                    value="1"
                    @if($oldVal)   checked  @endif
                    @if($required) required @endif
                    @if($disabled) disabled @endif
                    role="switch"
                    aria-checked="{{ $oldVal ? 'true' : 'false' }}"
                >
                @if($label)
                    <span class="label" style="margin:0;">{{ $label }}</span>
                @endif
            </label>

        {{-- ── file ───────────────────────────────────────────────── --}}
        @elseif($type === 'file')

            <label
                for="{{ $fieldId }}"
                style="
                    display:flex;align-items:center;gap:0.75rem;
                    padding:0.75rem 0.875rem;
                    min-height:42px;
                    border-radius:var(--radius-sm);
                    background:var(--surface);
                    cursor:pointer;
                    transition:background 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
                    position:relative;
                    overflow:hidden;
                    box-shadow:inset 0 1px 2px rgba(0,0,0,0.02), 0 1px 3px rgba(0,0,0,0.03);
                "
                onmouseenter="this.style.background='var(--surface-hover)';this.style.boxShadow='0 0 0 2px rgba(107,15,36,0.15)'"
                onmouseleave="this.style.background='var(--surface)';this.style.boxShadow='inset 0 1px 2px rgba(0,0,0,0.02), 0 1px 3px rgba(0,0,0,0.03)'"
            >
                {{-- Upload icon inline SVG --}}
                <span style="color:var(--text-muted);flex-shrink:0;" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 13V7m0 0L7.5 9.5M10 7l2.5 2.5" stroke="currentColor"
                              stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3.33 13.33A4.17 4.17 0 0 1 5 5.83h.42A5 5 0 1 1 15 10"
                              stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M10 16.67h5a1.67 1.67 0 0 0 0-3.34H10"
                              stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </span>
                <span style="color:var(--text-muted);font-size:var(--font-size-sm);font-weight:500;">
                    {{ $placeholder ?: 'اختر ملفًا أو اسحبه هنا' }}
                </span>
                <input
                    type="file"
                    id="{{ $fieldId }}"
                    name="{{ $name }}"
                    @if($required) required @endif
                    @if($disabled) disabled @endif
                    style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                    oninput="
                        var lbl = this.parentElement.querySelector('span:nth-child(2)');
                        if(this.files&&this.files[0]) lbl.textContent=this.files[0].name;
                        else lbl.textContent='{{ $placeholder ?: 'اختر ملفًا أو اسحبه هنا' }}';
                    "
                >
            </label>

        {{-- ── all other text-like inputs (text, email, password, number, etc.) ── --}}
        @else

            <div class="input-group" style="position:relative;">

                {{-- Icon inline-start --}}
                @if($hasIconS)
                    <span class="input-icon input-icon-s" aria-hidden="true">
                        {{-- Render as text node for Material Icons; switch to SVG when a map is available --}}
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            {{-- Generic search/person fallback; callers supply name via $icon --}}
                            <text x="0" y="14" font-size="14" font-family="'Material Icons Round',sans-serif"
                                  fill="currentColor">{{ $icon }}</text>
                        </svg>
                    </span>
                @endif

                <input
                    type="{{ $type }}"
                    id="{{ $fieldId }}"
                    name="{{ $name }}"
                    value="{{ $oldVal }}"
                    class="{{ $inputCls }}"
                    placeholder="{{ $placeholder }}"
                    @if($required)    required                                    @endif
                    @if($disabled)    disabled                                    @endif
                    @if($readonly)    readonly                                    @endif
                    @if($maxlength)   maxlength="{{ $maxlength }}"               @endif
                    autocomplete="{{ $type === 'password' ? 'current-password' : $autocomplete }}"
                    dir="{{ in_array($type, ['email','url','tel','number','date','time','datetime-local','color']) ? 'ltr' : 'rtl' }}"
                >

                {{-- Icon inline-end (decorative) or password toggle --}}
                @if($type === 'password')
                    <button
                        type="button"
                        class="btn btn-sukk-icon"
                        style="
                            position:absolute;
                            inset-inline-end:0.75rem;
                            top:50%;transform:translateY(-50%);
                            background:none;border:none;cursor:pointer;
                            color:var(--text-muted);padding:4px;
                            display:flex;align-items:center;
                            border-radius:var(--radius-sm);
                            transition:color 140ms ease;
                        "
                        aria-label="إظهار/إخفاء كلمة المرور"
                        onclick="
                            (function(btn){
                                var inp = btn.closest('.input-group').querySelector('input');
                                var isText = inp.type === 'text';
                                inp.type = isText ? 'password' : 'text';
                                btn.querySelector('.eye-on').style.display  = isText ? 'block' : 'none';
                                btn.querySelector('.eye-off').style.display = isText ? 'none'  : 'block';
                            })(this)
                        "
                        onmouseenter="this.style.color='var(--sukk-primary)'"
                        onmouseleave="this.style.color='var(--text-muted)'"
                    >
                        {{-- Eye open — shown when password is hidden --}}
                        <svg class="eye-off" width="16" height="16" viewBox="0 0 16 16"
                             fill="none" xmlns="http://www.w3.org/2000/svg"
                             aria-hidden="true" style="display:block;">
                            <path d="M1.33 8S3.33 3.33 8 3.33 14.67 8 14.67 8 12.67 12.67 8 12.67 1.33 8 1.33 8z"
                                  stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.4"/>
                        </svg>
                        {{-- Eye closed — shown when password is visible --}}
                        <svg class="eye-on" width="16" height="16" viewBox="0 0 16 16"
                             fill="none" xmlns="http://www.w3.org/2000/svg"
                             aria-hidden="true" style="display:none;">
                            <path d="M2 2l12 12M6.94 6.94A2 2 0 0 0 9.06 9.06M9.9 9.9C9.2 10.47 8.63 10.67 8 10.67c-2.33 0-4.67-2.67-4.67-2.67s.72-1.42 2-2.34M11.33 11.33C12.61 10.41 13.33 9 13.33 9S11 3.33 8 3.33c-.9 0-1.77.25-2.55.67"
                                  stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                @elseif($hasIconE)
                    <span class="input-icon input-icon-e" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <text x="0" y="14" font-size="14" font-family="'Material Icons Round',sans-serif"
                                  fill="currentColor">{{ $iconEnd }}</text>
                        </svg>
                    </span>
                @endif

            </div>{{-- /.input-group --}}

        @endif

        {{-- ── Validation error ─────────────────────────────────────── --}}
        @if($hasError)
            <p class="field-error" role="alert" aria-live="assertive">
                {{-- Warning inline SVG --}}
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"
                     xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0;">
                    <path d="M6.5 1L12 11.5H1L6.5 1z" stroke="currentColor"
                          stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6.5 5.5v2.5M6.5 9.5v.5" stroke="currentColor"
                          stroke-width="1.3" stroke-linecap="round"/>
                </svg>
                {{ $errors->first($name) }}
            </p>
        @endif

        {{-- ── Hint ─────────────────────────────────────────────────── --}}
        @if($hint && !$hasError)
            <p class="hint" id="{{ $fieldId }}-hint">{{ $hint }}</p>
        @endif

    </div>{{-- /.field-group --}}

@else

    {{-- ── Generic passthrough slot (no $name, no $action) ────────── --}}
    <div
        class="field-group"
        dir="rtl"
        {{ $attributes->except(['class']) }}
    >
        {{ $slot }}
    </div>

@endif


