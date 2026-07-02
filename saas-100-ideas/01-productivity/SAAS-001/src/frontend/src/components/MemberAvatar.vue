<script setup>
import { computed } from 'vue';

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  size: { type: String, default: 'md' }, // sm, md, lg
  showOnline: { type: Boolean, default: false },
  online: { type: Boolean, default: false },
});

const sizeClasses = computed(() => ({
  sm: 'w-8 h-8 text-xs',
  md: 'w-10 h-10 text-sm',
  lg: 'w-12 h-12 text-base',
}[props.size]));

const dotSize = computed(() => ({
  sm: 'w-2.5 h-2.5',
  md: 'w-3 h-3',
  lg: 'w-3.5 h-3.5',
}[props.size]));

const initials = computed(() => {
  if (!props.user?.name) return '?';
  const parts = props.user.name.split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return props.user.name.slice(0, 2).toUpperCase();
});

const bgColor = computed(() => {
  if (!props.user?.id) return 'bg-primary-100 text-primary-700';
  const colors = [
    'bg-primary-100 text-primary-700',
    'bg-secondary-100 text-secondary-700',
    'bg-success-100 text-success-700',
    'bg-warning-100 text-warning-700',
    'bg-error-100 text-error-700',
    'bg-cyan-100 text-cyan-700',
  ];
  const idx = props.user.id.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0) % colors.length;
  return colors[idx];
});
</script>

<template>
  <div class="relative inline-flex flex-shrink-0">
    <img
      v-if="user?.avatar_url"
      :src="user.avatar_url"
      :alt="user.name || 'User avatar'"
      :class="['rounded-full object-cover', sizeClasses]"
    />
    <div
      v-else
      :class="['rounded-full flex items-center justify-center font-medium', sizeClasses, bgColor]"
      role="img"
      :aria-label="user?.name || 'User'"
    >
      {{ initials }}
    </div>
    <span
      v-if="showOnline"
      :class="[
        'absolute bottom-0 right-0 rounded-full border-2 border-white',
        dotSize,
        online ? 'bg-success-500' : 'bg-neutral-300',
      ]"
      aria-hidden="true"
    />
  </div>
</template>
