@php
    $filters = $filters ?? [];
    $searchPlaceholder = $searchPlaceholder ?? 'بحث…';
    $searchValue = $searchValue ?? '';
    $hasFilters = $hasFilters ?? false;
    $route = $route ?? '#';
@endphp
@once @endonce

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ $route }}">
            <div class="cmp-flt-g">
                {{-- Search --}}
                <div class="cmp-flt-s">
                    <label for="cmp-flt-search" class="label">بحث</label>
                    <div class="input-group">
                        <span class="input-group-text"><x-heroicon name="search" class="text-sm" aria-hidden="true" /></span>
                        <input type="text" id="cmp-flt-search" name="search"
                               value="{{ $searchValue }}"
                               placeholder="{{ $searchPlaceholder }}"
                               class="input" autocomplete="off" aria-label="بحث">
                    </div>
                </div>

                {{-- Dynamic filter dropdowns --}}
                @foreach($filters as $f)
                <div>
                    <label for="cmp-flt-{{ $f['name'] }}" class="label">{{ $f['label'] }}</label>
                    <select id="cmp-flt-{{ $f['name'] }}" name="{{ $f['name'] }}" class="input">
                        <option value="">{{ $f['allLabel'] ?? 'الكل' }}</option>
                        @foreach($f['options'] as $val => $lab)
                        <option value="{{ $val }}" {{ ($f['selected'] ?? '') === $val ? 'selected' : '' }}>{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach

                {{-- Action buttons --}}
                <div class="cmp-flt-a">
                    <button type="submit" class="btn btn-primary btn-sm" aria-label="تطبيق الفلاتر">
                        <x-heroicon name="search" class="text-sm" aria-hidden="true" />
                        بحث
                    </button>
                    @if($hasFilters)
                    <a href="{{ $route }}" class="btn btn-secondary btn-sm" aria-label="تصفير الفلاتر">
                        <x-heroicon name="clear" class="text-sm" aria-hidden="true" />
                        تصفير
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
