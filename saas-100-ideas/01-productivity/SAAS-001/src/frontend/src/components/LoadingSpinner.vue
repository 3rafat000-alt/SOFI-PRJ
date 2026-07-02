<script setup>
defineProps({
  size: { type: String, default: 'md' }, // sm, md, lg
  label: { type: String, default: 'جاري التحميل...' },
});

const sizeMap = { sm: 'w-5 h-5', md: 'w-8 h-8', lg: 'w-12 h-12' };
</script>

<template>
  <div class="flex flex-col items-center justify-center py-12 gap-3" role="status" aria-live="polite">
    <div
      :class="['border-3 border-neutral-200 border-t-primary-600 rounded-full animate-spin', sizeMap[size]]"
      aria-hidden="true"
    />
    <p v-if="label" class="text-sm text-neutral-500">{{ label }}</p>
  </div>
</template>
