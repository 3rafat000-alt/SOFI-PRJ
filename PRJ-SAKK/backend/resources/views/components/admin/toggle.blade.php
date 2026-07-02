{{--
  Component: <x-admin.toggle>

  A standalone toggle (switch) component, consistent with the shared SAKK design vocab.
  Uses the `.toggle` CSS class defined in /sakk-admin/admin.css — appearance:none
  checkbox rendered as a pill switch with animated thumb. Fully RTL (inset-inline-start).
  No external CDN. No JavaScript required for basic on/off; JS can listen to `change`.

  Props:
    name      (string)       — HTML input name attribute. Required for form submission.
    id        (string|null)  — HTML id. Defaults to "toggle-{name}".
    label     (string|null)  — Toggle label text (Arabic). Shown inline, end of switch.
    hint      (string|null)  — Sub-label helper text shown below the toggle row (Arabic).
    value     (bool)         — Whether the toggle is currently ON. Default: false.
    checked   (bool|null)    — Alias for value (either accepted; value takes precedence if both set).
    required  (bool)         — Adds required attribute. Default: false.
    disabled  (bool)         — Disables the toggle. Default: false.
    size      (string)       — sm | md (default) | lg — scales the pill + thumb.
    labelEnd  (bool)         — false = label on end (left in RTL). Default: true (end = after).
    hiddenFallback (bool)    — Emit <input type="hidden" name="{name}" value="0"> before
                              the checkbox so unchecked posts 0. Default: true.

  Named slots:
    $slot — optional slot; if provided, used as label text (overrides $label prop).

  Usage — minimal:
    <x-admin.toggle name="is_active" label="تفعيل الحساب" :value="$user->is_active"/>

  Usage — inside a form, with hint and disabled:
    <x-admin.toggle
        name="send_notifications"
        label="إشعارات البريد الإلكتروني"
        hint="سيتلقى المستخدم رسائل بريد إلكتروني عند كل معاملة"
        :value="old('send_notifications', $user->send_notifications)"
        :disabled="! $user->email_verified_at"
    />

  Usage — large size:
    <x-admin.toggle name="maintenance_mode" label="وضع الصيانة" size="lg" :value="false"/>

  Usage — small, no label:
    <x-admin.toggle name="show_balance" size="sm" :value="true"/>
--}}
@props([
    'name'            => '',
    'id'              => null,
    'label'           => null,
    'hint'            => null,
    'value'           => false,
    'checked'         => null,
    'required'        => false,
    'disabled'        => false,
    'size'            => 'md',
    'labelEnd'        => true,
    'hiddenFallback'  => true,
])

@php
/*
 * Resolve the checked state.
 * - $value is the canonical prop (mirrors how <x-admin.form> passes it).
 * - $checked is an alias; $value wins if both are supplied.
 */
$isChecked = ($checked !== null && $value === false) ? (bool) $checked : (bool) $value;

/*
 * Input id: fall back to "toggle-{name}" so the label's `for` always links.
 */
$resolvedId = $id ?? ('toggle-' . $name);

/*
 * Determine display label from slot or prop.
 * The $slot variable is available inside @props components.
 */
$displayLabel = $label;

/*
 * Size geometry.
 * The base .toggle in admin.css is 42×24 px (md).
 * sm / lg are supplementary, delivered via inline style here so we don't need
 * a separate CSS file edit (no file other than this one may be touched).
 *
 * Thumb travels: width - height - 2×offset(2px) → 42-24=18px at md.
 * sm: 34×20 → travel 34-20=14px, offset=2px
 * lg: 52×30 → travel 52-30=22px, offset=2px
 */
$sizeStyle  = match($size) {
    'sm' => 'width:34px;height:20px;',
    'lg' => 'width:52px;height:30px;',
    default => '',  // md: CSS .toggle handles it
};

/*
 * Thumb override for non-md sizes (pseudo-element not easily overridable inline;
 * we compensate by emitting a scoped @once <style> block).
 */
$thumbStyle = match($size) {
    'sm' => 'sm',
    'lg' => 'lg',
    default => 'md',
};

/*
 * Disabled opacity.
 */
$wrapOpacity = $disabled ? 'opacity:.5;pointer-events:none;' : '';

/*
 * Merged input attrs (merge everything except class, which we own).
 */
$extraAttrs = $attributes->except(['class', 'style', 'id', 'name', 'checked', 'disabled', 'required']);
@endphp

{{--
  ── Inline style overrides for sm/lg thumb geometry ──────────────────
  Emitted once per page via @once so multiple toggles of the same size
  do not bloat the HTML. No external dependency.
--}}
{{-- CSS moved to base.css (Component: Toggle) --}}

{{--
  ── Hidden fallback (unchecked sends 0) ─────────────────────────────
  Standard Laravel checkbox convention. Only emitted when hiddenFallback=true.
--}}
@if($hiddenFallback && $name)
<input type="hidden" name="{{ $name }}" value="0">
@endif

{{--
  ── Toggle row ──────────────────────────────────────────────────────
  dir="rtl" ensures the pill appears on the inline-end (right) side and
  the label appears on the inline-start (left) side in RTL layouts.
  The label's `for` links it to the checkbox for full click-area accessibility.
--}}
<label
    for="{{ $resolvedId }}"
    dir="rtl"
    class="sakk-toggle-wrap {{ $disabled ? 'is-disabled' : '' }}"
    style="{{ $wrapOpacity }}"
>
    {{-- Toggle pill input --}}
    <input
        type="checkbox"
        id="{{ $resolvedId }}"
        name="{{ $name }}"
        value="1"
        class="toggle {{ $size !== 'md' ? 'toggle--' . $size : '' }}"
        @if($isChecked) checked @endif
        @if($disabled)  disabled aria-disabled="true" @endif
        @if($required)  required aria-required="true" @endif
        {{ $extraAttrs }}
    >

    {{-- Text block: label + optional hint --}}
    @if($displayLabel || !$slot->isEmpty() || $hint)
    <span class="sakk-toggle-text">
        @if(!$slot->isEmpty())
            <span class="sakk-toggle-label">{{ $slot }}</span>
        @elseif($displayLabel)
            <span class="sakk-toggle-label">{{ $displayLabel }}</span>
        @endif

        @if($hint)
            <span class="sakk-toggle-hint">{{ $hint }}</span>
        @endif
    </span>
    @endif
</label>
