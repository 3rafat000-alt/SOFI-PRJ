@props(['commands' => []])

<div x-data="commandPalette"
     x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     role="dialog" aria-modal="true" aria-label="البحث السريع">
    {{-- Backdrop --}}
    <div class="cmdk-overlay" @click="close()">
        {{-- Palette --}}
        <div class="cmdk-palette" @click.stop
             x-trap.noreturn="show"
             @keydown.home.prevent="selectedIndex = 0"
             @keydown.end.prevent="selectedIndex = filtered.length - 1">
            {{-- Search header --}}
            <div class="cmdk-header">
                <x-heroicon name="search" />
                <input type="text" class="cmdk-input" x-ref="cmdkInput"
                       placeholder="ابحث عن صفحة أو أمر…"
                       aria-label="ابحث عن صفحة أو أمر"
                       x-model="query"
                       @keydown.arrow-down.prevent="next()"
                       @keydown.arrow-up.prevent="prev()"
                       @keydown.enter.prevent="exec(selectedIndex)" />
                <kbd class="cmdk-item-kbd" style="margin-inline-start:0">⌘K</kbd>
            </div>

            {{-- Results --}}
            <div class="cmdk-section" x-ref="cmdkList" style="max-height:320px;overflow-y:auto">
                <template x-if="filtered.length === 0">
                    <div class="cmdk-empty">
                        <span x-text="query ? 'لا توجد نتائج لـ \u201C' + query + '\u201D' : 'ابدأ الكتابة للبحث…'"></span>
                    </div>
                </template>
                <template x-for="(item, i) in filtered" :key="item.id">
                    <a :href="item.route"
                       class="cmdk-item" :class="{ 'cmdk-item--hl': i === selectedIndex }"
                       @mouseenter="selectedIndex = i"
                       @click="exec(i)">
                         <span x-html="item.svg" class="shrink-0 w-5 h-5 flex items-center justify-center"></span>
                        <span x-text="item.label"></span>
                        <kbd class="cmdk-item-kbd" x-show="item.shortcut" x-text="'⌘' + item.shortcut"></kbd>
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>
