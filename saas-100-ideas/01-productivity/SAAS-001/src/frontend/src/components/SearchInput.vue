<script setup>
import { ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useUiStore } from '@/stores/uiStore';

const uiStore = useUiStore();

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Search...' },
});

const emit = defineEmits(['update:modelValue', 'search']);

const query = ref(props.modelValue);

const debouncedSearch = useDebounceFn((val) => {
  emit('search', val);
}, 300);

const onInput = (e) => {
  query.value = e.target.value;
  emit('update:modelValue', query.value);
  if (query.value.length >= 2 || query.value.length === 0) {
    debouncedSearch(query.value);
  }
};

const clear = () => {
  query.value = '';
  emit('update:modelValue', '');
  emit('search', '');
};
</script>

<template>
  <div class="relative">
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      class="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400"
      :class="[uiStore?.locale === 'ar' ? 'right-3' : 'left-3']"
      aria-hidden="true"
    >
      <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>
    <input
      :value="query"
      @input="onInput"
      :placeholder="placeholder"
      class="input pl-9 pr-9 py-2 text-sm"
      type="search"
      role="searchbox"
      aria-label="Search"
      autocomplete="off"
    />
    <button
      v-if="query"
      @click="clear"
      class="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 hover:text-neutral-600"
      :class="[uiStore?.locale === 'ar' ? 'left-3' : 'right-3']"
      aria-label="Clear search"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
        <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>
  </div>
</template>
