<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'todo' },
});

const classes = computed(() => {
  const map = {
    todo: 'bg-neutral-100 text-neutral-700',
    in_progress: 'bg-primary-100 text-primary-700',
    done: 'bg-success-100 text-success-700',
  };
  return map[props.status] || map.todo;
});

const labels = {
  todo: { ar: 'للتنفيذ', en: 'To Do' },
  in_progress: { ar: 'قيد التنفيذ', en: 'In Progress' },
  done: { ar: 'مكتمل', en: 'Done' },
};
</script>

<template>
  <span :class="['badge', classes]" role="status" :aria-label="`Status: ${status}`">
    <span v-if="status === 'in_progress'" class="w-1.5 h-1.5 rounded-full bg-primary-500 ml-1.5 rtl:ml-0 rtl:mr-1.5 animate-pulse" aria-hidden="true" />
    <span v-else-if="status === 'done'" class="w-1.5 h-1.5 rounded-full bg-success-500 ml-1.5 rtl:ml-0 rtl:mr-1.5" aria-hidden="true" />
    {{ labels[status]?.ar || status }}
  </span>
</template>
