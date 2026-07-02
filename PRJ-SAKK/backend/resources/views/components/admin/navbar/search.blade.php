@props(['placeholder' => 'بحث في كل شيء…'])

<div class="nv-search" role="search" @click="$dispatch('toggle-cmdk')"
     @keydown.enter="$dispatch('toggle-cmdk')"
     tabindex="0" aria-label="فتح البحث السريع">
    <x-heroicon name="search" class="nv-search-icon" />
    <span class="nv-search-input nv-search-input--placeholder">{{ $placeholder }}</span>
    <kbd class="nv-search-kbd">⌘K</kbd>
</div>
