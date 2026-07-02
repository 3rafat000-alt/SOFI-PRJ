<script setup>
import { computed } from 'vue';
import PriorityBadge from './PriorityBadge.vue';
import StatusBadge from './StatusBadge.vue';
import MemberAvatar from './MemberAvatar.vue';

const props = defineProps({
  task: { type: Object, required: true },
  draggable: { type: Boolean, default: true },
});

const emit = defineEmits(['click', 'dragstart', 'dragend']);

const isOverdue = computed(() => {
  if (!props.task.due_date || props.task.status === 'done') return false;
  return new Date(props.task.due_date) < new Date(new Date().toDateString());
});

const dueToday = computed(() => {
  if (!props.task.due_date) return false;
  const today = new Date().toISOString().split('T')[0];
  return props.task.due_date === today;
});

const dueDateFormatted = computed(() => {
  if (!props.task.due_date) return null;
  const date = new Date(props.task.due_date);
  return date.toLocaleDateString('ar-SA', { month: 'short', day: 'numeric' });
});

const onDragStart = (e) => {
  if (!props.draggable) return;
  e.dataTransfer.setData('text/plain', props.task.id);
  e.dataTransfer.effectAllowed = 'move';
  emit('dragstart', props.task);
};

const onDragEnd = () => {
  emit('dragend', props.task);
};

const handleClick = () => {
  emit('click', props.task);
};
</script>

<template>
  <div
    :class="[
      'card p-3 cursor-pointer transition-all duration-150',
      isOverdue ? 'border-r-4 border-error-500' : '',
      'hover:shadow-elevated',
    ]"
    :draggable="draggable"
    @dragstart="onDragStart"
    @dragend="onDragEnd"
    @click="handleClick"
    role="button"
    :aria-label="task.title"
    :tabindex="0"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <!-- Priority and status -->
    <div class="flex items-center justify-between mb-2">
      <PriorityBadge :priority="task.priority" />
      <StatusBadge :status="task.status" />
    </div>

    <!-- Title -->
    <h4 class="text-sm font-medium text-neutral-900 mb-2 line-clamp-2" :title="task.title">
      {{ task.title }}
    </h4>

    <!-- Tags -->
    <div v-if="task.tags?.length" class="flex flex-wrap gap-1 mb-2">
      <span
        v-for="tag in task.tags"
        :key="tag.id"
        class="text-2xs px-1.5 py-0.5 rounded-4 font-medium"
        :style="{ backgroundColor: tag.color + '20', color: tag.color }"
      >
        {{ tag.name }}
      </span>
    </div>

    <!-- Footer: assignee + due date -->
    <div class="flex items-center justify-between mt-2 pt-2 border-t border-neutral-100">
      <div class="flex items-center gap-2">
        <MemberAvatar :user="task.assignee || {}" :size="'sm'" />
        <span v-if="task.assignee?.name" class="text-xs text-neutral-500 truncate max-w-[80px]">
          {{ task.assignee.name }}
        </span>
      </div>

      <div class="flex items-center gap-2 text-xs text-neutral-400">
        <!-- Due date -->
        <span v-if="task.due_date" :class="[isOverdue ? 'text-error-500 font-medium' : dueToday ? 'text-warning-600 font-medium' : '']">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 inline ml-1" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          {{ dueDateFormatted }}
        </span>

        <!-- Comments count -->
        <span v-if="task.comments_count > 0" class="flex items-center gap-0.5">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
          </svg>
          {{ task.comments_count }}
        </span>

        <!-- Attachments count -->
        <span v-if="task.attachments_count > 0" class="flex items-center gap-0.5">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3" aria-hidden="true">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
          </svg>
          {{ task.attachments_count }}
        </span>
      </div>
    </div>

    <!-- Time info -->
    <div v-if="task.estimated_minutes || task.logged_minutes" class="flex items-center gap-3 mt-1.5 text-2xs text-neutral-400">
      <span v-if="task.estimated_minutes">
        {{ Math.round(task.estimated_minutes / 60) }}h {{ task.estimated_minutes % 60 }}m
      </span>
      <span v-if="task.logged_minutes" class="text-primary-500">
        {{ Math.round(task.logged_minutes / 60) }}h {{ task.logged_minutes % 60 }}m
      </span>
    </div>
  </div>
</template>
