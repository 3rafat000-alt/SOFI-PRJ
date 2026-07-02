{{-- Autosave number field. Vars: $key, $label, $unit (optional) --}}
<div>
    <label class="label flex items-center gap-1.5">
        {{ $label }}
        <x-heroicon style="font-size:1rem" class="saved-tick" x-bind:class="tick==='{{ $key }}' ? 'show':''" name="check_circle" />
    </label>
    <div class="relative">
        <input type="number" step="0.01" min="0"
               class="input {{ isset($unit) ? 'pr-8' : '' }}"
               x-model="vals.{{ $key }}"
               @change="save('{{ $key }}', vals.{{ $key }})"
               aria-label="{{ $label }}">
        @isset($unit)
        <span class="absolute right-3 top-1/2 -translate-y-1/2 font-bold" style="color: var(--text-muted);">{{ $unit }}</span>
        @endisset
    </div>
</div>
