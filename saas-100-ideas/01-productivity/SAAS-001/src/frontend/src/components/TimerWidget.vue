<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useTimeEntryStore } from '@/stores/timeEntryStore';
import { useUiStore } from '@/stores/uiStore';

const props = defineProps({
  taskId: { type: String, default: null },
  taskTitle: { type: String, default: '' },
  compact: { type: Boolean, default: false },
});

const emit = defineEmits(['timerStart', 'timerStop']);

const timeStore = useTimeEntryStore();
const uiStore = useUiStore();
const note = ref('');

onMounted(() => {
  timeStore.checkActiveTimer();
});

const isRunningOnTask = computed(() => {
  return timeStore.isTimerRunning && timeStore.activeTimer?.task_id === props.taskId;
});

const toggleTimer = async () => {
  if (isRunningOnTask.value) {
    await timeStore.stopTimer(note.value);
    if (!timeStore.error) {
      uiStore.addToast(
        uiStore.locale === 'ar' ? 'تم إيقاف المؤقت' : 'Timer stopped',
        'success',
      );
      emit('timerStop', timeStore.activeTimer);
    }
  } else {
    if (!props.taskId) {
      uiStore.addToast(
        uiStore.locale === 'ar' ? 'الرجاء اختيار مهمة أولاً' : 'Please select a task first',
        'error',
      );
      return;
    }
    // Stop any running timer first
    if (timeStore.isTimerRunning) {
      await timeStore.stopTimer();
    }
    const result = await timeStore.startTimer(props.taskId, '');
    if (result) {
      uiStore.addToast(
        uiStore.locale === 'ar' ? 'تم بدء المؤقت' : 'Timer started',
        'success',
      );
      emit('timerStart', result);
    }
  }
};

const formatTime = (seconds) => {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};
</script>

<template>
  <div :class="['flex items-center gap-3', compact ? '' : 'p-4 bg-white rounded-8 border border-neutral-200']">
    <!-- Timer display -->
    <div class="relative flex-shrink-0">
      <svg class="w-12 h-12 -rotate-90" viewBox="0 0 48 48" aria-hidden="true">
        <circle cx="24" cy="24" r="20" fill="none" stroke="#E5E7EB" stroke-width="4" />
        <circle
          cx="24" cy="24" r="20"
          fill="none"
          :stroke="timeStore.isTimerRunning ? '#2563EB' : '#10B981'"
          stroke-width="4"
          stroke-linecap="round"
          :stroke-dasharray="125.6"
          :stroke-dashoffset="125.6 - (125.6 * (timeStore.elapsedSeconds % 3600) / 3600)"
          :class="{ 'animate-pulse-slow': timeStore.isTimerRunning }"
        />
      </svg>
      <span
        class="absolute inset-0 flex items-center justify-center text-sm font-mono font-medium"
        :class="timeStore.isTimerRunning ? 'text-primary-600' : 'text-success-600'"
        role="timer"
        :aria-label="`Timer: ${timeStore.formattedElapsed}`"
      >
        {{ timeStore.formattedElapsed || '00:00:00' }}
      </span>
    </div>

    <!-- Info -->
    <div v-if="!compact" class="flex-1 min-w-0">
      <p class="text-sm font-medium text-neutral-900 truncate">
        {{ taskTitle || (uiStore.locale === 'ar' ? 'لم يتم اختيار مهمة' : 'No task selected') }}
      </p>
      <p class="text-xs text-neutral-500">
        {{ timeStore.isTimerRunning ? (uiStore.locale === 'ar' ? 'قيد التشغيل' : 'Running') : (uiStore.locale === 'ar' ? 'متوقف' : 'Stopped') }}
      </p>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-2">
      <button
        @click="toggleTimer"
        :class="[
          'btn rounded-full p-3 transition-all',
          isRunningOnTask.value
            ? 'bg-error-500 text-white hover:bg-error-600 timer-pulse'
            : 'bg-primary-600 text-white hover:bg-primary-700',
        ]"
        :aria-label="isRunningOnTask.value ? (uiStore.locale === 'ar' ? 'إيقاف المؤقت' : 'Stop timer') : (uiStore.locale === 'ar' ? 'بدء المؤقت' : 'Start timer')"
        :disabled="!taskId && !isRunningOnTask.value"
      >
        <svg v-if="isRunningOnTask.value" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
          <rect x="6" y="4" width="4" height="16" /><rect x="14" y="4" width="4" height="16" />
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
          <polygon points="5 3 19 12 5 21 5 3" />
        </svg>
      </button>
    </div>
  </div>
</template>
