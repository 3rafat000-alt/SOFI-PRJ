<script setup>
import { computed } from 'vue';

const props = defineProps({
  priority: { type: String, default: 'medium' },
});

const classes = computed(() => {
  const map = {
    urgent: 'bg-error-100 text-error-700',
    high: 'bg-warning-100 text-warning-700',
    medium: 'bg-primary-100 text-primary-700',
    low: 'bg-neutral-100 text-neutral-600',
  };
  return map[props.priority] || map.medium;
});

const labels = {
  urgent: { ar: 'عاجل', en: 'Urgent' },
  high: { ar: 'عالية', en: 'High' },
  medium: { ar: 'متوسطة', en: 'Medium' },
  low: { ar: 'منخفضة', en: 'Low' },
};
</script>

<template>
  <span :class="['badge', classes]" role="status" :aria-label="`Priority: ${priority}`">
    {{ labels[priority]?.ar || priority }}
  </span>
</template>
