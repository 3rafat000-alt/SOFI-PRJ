<script setup>
defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  action: { type: Object, default: null }, // { label, onClick }
});
</script>

<template>
  <div class="empty-state" role="status">
    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" />
      <polyline points="13 2 13 9 20 9" />
    </svg>
    <h3 v-if="title" class="empty-state-title">{{ title }}</h3>
    <p v-if="description" class="empty-state-text">{{ description }}</p>
    <button
      v-if="action"
      @click="action.onClick"
      class="btn-primary mt-4"
    >
      {{ action.label }}
    </button>
  </div>
</template>
