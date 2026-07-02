@php
    $fltFilters = $fltFilters ?? [];
    $fltSearchPlaceholder = $fltSearchPlaceholder ?? 'بحث…';
    $fltSearchValue = $fltSearchValue ?? '';
    $fltHasFilters = $fltHasFilters ?? false;
    $fltRoute = $fltRoute ?? '#';
    $fltId = $fltId ?? 'flt';
@endphp
@once @endonce

<form method="GET" action="{{ $fltRoute }}" style="margin-bottom:var(--space-md);">
    <div class="flt-inline-g">
        {{-- Search --}}
        <div class="flt-inline-s">
            <label for="{{ $fltId }}-search" class="label">بحث</label>
            <div class="input-group">
                <span class="input-group-text"><x-heroicon name="search" class="text-sm" aria-hidden="true" /></span>
                <input type="text" id="{{ $fltId }}-search" name="search"
                       value="{{ $fltSearchValue }}"
                       placeholder="{{ $fltSearchPlaceholder }}"
                       class="input" autocomplete="off" aria-label="بحث">
            </div>
        </div>

        {{-- Dynamic filter dropdowns --}}
        @foreach($fltFilters as $f)
        <div>
            <label for="{{ $fltId }}-{{ $f['name'] }}" class="label">{{ $f['label'] }}</label>
            <select id="{{ $fltId }}-{{ $f['name'] }}" name="{{ $f['name'] }}" class="input">
                <option value="">{{ $f['allLabel'] ?? 'الكل' }}</option>
                @foreach($f['options'] as $val => $lab)
                <option value="{{ $val }}" {{ ($f['selected'] ?? '') === $val ? 'selected' : '' }}>{{ $lab }}</option>
                @endforeach
            </select>
        </div>
        @endforeach

        {{-- Action buttons --}}
        <div class="flt-inline-a">
            <button type="submit" class="btn btn-sukk-primary btn-sm" aria-label="تطبيق الفلاتر">
                <x-heroicon name="search" class="text-sm" aria-hidden="true" />
                بحث
            </button>
            @if($fltHasFilters)
            <a href="{{ $fltRoute }}" class="btn btn-sukk-secondary btn-sm" aria-label="تصفير الفلاتر">
                <x-heroicon name="clear" class="text-sm" aria-hidden="true" />
                تصفير
            </a>
            @endif
        </div>
    </div>
</form>
