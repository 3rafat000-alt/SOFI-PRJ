<script setup>
import { computed } from 'vue';
import { useUiStore } from '@/stores/uiStore';

const props = defineProps({
  currentPage: { type: Number, default: 1 },
  lastPage: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
});

const emit = defineEmits(['page-change']);

const uiStore = useUiStore();

const pages = computed(() => {
  const range = [];
  const start = Math.max(1, props.currentPage - 2);
  const end = Math.min(props.lastPage, start + 4);
  for (let i = start; i <= end; i++) {
    range.push(i);
  }
  return range;
});

const hasPrev = computed(() => props.currentPage > 1);
const hasNext = computed(() => props.currentPage < props.lastPage);
</script>

<template>
  <div v-if="lastPage > 1" class="flex items-center justify-between gap-4 mt-4" role="navigation" aria-label="Pagination">
    <p class="text-sm text-neutral-500">
      {{ uiStore.locale === 'ar' ? `إجمالي ${total}` : `${total} total` }}
    </p>
    <div class="flex items-center gap-1">
      <button
        @click="emit('page-change', currentPage - 1)"
        :disabled="!hasPrev"
        class="btn-icon btn-ghost btn-sm"
        :aria-label="uiStore.locale === 'ar' ? 'السابق' : 'Previous'"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 rtl-mirror">
          <polyline points="15 18 9 12 15 6" />
        </svg>
      </button>

      <button
        v-for="page in pages"
        :key="page"
        @click="emit('page-change', page)"
        :class="[
          'btn-icon btn-sm min-w-[32px]',
          page === currentPage
            ? 'bg-primary-600 text-white'
            : 'btn-ghost text-neutral-600',
        ]"
        :aria-current="page === currentPage ? 'page' : undefined"
        :aria-label="`Page ${page}`"
      >
        {{ page }}
      </button>

      <button
        @click="emit('page-change', currentPage + 1)"
        :disabled="!hasNext"
        class="btn-icon btn-ghost btn-sm"
        :aria-label="uiStore.locale === 'ar' ? 'التالي' : 'Next'"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 rtl-mirror">
          <polyline points="9 18 15 12 9 6" />
        </svg>
      </button>
    </div>
  </div>
</template>
