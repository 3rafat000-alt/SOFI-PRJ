<script setup>
import { ref, onMounted, computed } from 'vue';
import { useTimeEntryStore } from '@/stores/timeEntryStore';
import { useTaskStore } from '@/stores/taskStore';
import { useUiStore } from '@/stores/uiStore';
import TimerWidget from '@/components/TimerWidget.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';

const timeStore = useTimeEntryStore();
const taskStore = useTaskStore();
const uiStore = useUiStore();

const showManualEntry = ref(false);
const manualForm = ref({
  task_id: '',
  started_at: '',
  ended_at: '',
  note: '',
});
const selectedTaskId = ref('');
const timerNote = ref('');

onMounted(async () => {
  await Promise.all([
    timeStore.fetchEntries(),
    taskStore.fetchTasks({ limit: 100 }),
  ]);
});

const timeOptions = [
  { label: { ar: 'اليوم', en: 'Today' }, days: 0 },
  { label: { ar: 'هذا الأسبوع', en: 'This Week' }, days: 7 },
  { label: { ar: 'هذا الشهر', en: 'This Month' }, days: 30 },
];

const setDateFilter = (days) => {
  if (days === 0) {
    const today = new Date().toISOString().split('T')[0];
    timeStore.setDateRange(today, today);
  } else {
    const to = new Date().toISOString().split('T')[0];
    const from = new Date(Date.now() - days * 86400000).toISOString().split('T')[0];
    timeStore.setDateRange(from, to);
  }
};

const formatDuration = (minutes) => {
  if (!minutes && minutes !== 0) return '-';
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return `${h}h ${m}m`;
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString(uiStore.locale === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  });
};

const submitManual = async () => {
  const result = await timeStore.createManualEntry(manualForm.value);
  if (result) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم حفظ إدخال الوقت' : 'Time entry saved',
      'success',
    );
    showManualEntry.value = false;
    manualForm.value = { task_id: '', started_at: '', ended_at: '', note: '' };
  }
};

const deleteEntry = async (id) => {
  const ok = await timeStore.deleteEntry(id);
  if (ok) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم حذف إدخال الوقت' : 'Time entry deleted',
      'success',
    );
  }
};

const totalMinutes = computed(() =>
  timeStore.entries.reduce((sum, e) => sum + (e.duration_minutes || 0), 0)
);
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'تتبع الوقت' : 'Time Tracking' }}</h1>
      <div class="flex items-center gap-2">
        <button
          @click="showManualEntry = !showManualEntry"
          class="btn-secondary"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
            <path d="M12 5v14" /><path d="M5 12h14" />
          </svg>
          {{ uiStore.locale === 'ar' ? 'إدخال يدوي' : 'Manual Entry' }}
        </button>
      </div>
    </div>

    <!-- Timer widget -->
    <div class="mb-6">
      <div class="flex items-end gap-3">
        <div class="flex-1">
          <label class="input-label" for="timer-task-select">{{ uiStore.locale === 'ar' ? 'اختر مهمة' : 'Select Task' }}</label>
          <select id="timer-task-select" v-model="selectedTaskId" class="input">
            <option value="">{{ uiStore.locale === 'ar' ? 'اختر مهمة...' : 'Select a task...' }}</option>
            <option v-for="task in taskStore.tasks.slice(0, 50)" :key="task.id" :value="task.id">
              {{ task.title }}
            </option>
          </select>
        </div>
        <div v-if="timeStore.isTimerRunning" class="flex-1">
          <label class="input-label" for="timer-note">{{ uiStore.locale === 'ar' ? 'ملاحظة' : 'Note' }}</label>
          <input id="timer-note" v-model="timerNote" class="input" :placeholder="uiStore.locale === 'ar' ? 'أضف ملاحظة...' : 'Add a note...'" />
        </div>
      </div>
      <div class="mt-3">
        <TimerWidget
          :task-id="selectedTaskId || timeStore.activeTimer?.task_id"
          :task-title="taskStore.tasks.find(t => t.id === (selectedTaskId || timeStore.activeTimer?.task_id))?.title || ''"
        />
      </div>
    </div>

    <!-- Manual entry form -->
    <div v-if="showManualEntry" class="card mb-6 animate-slide-down">
      <h3 class="text-sm font-semibold text-neutral-900 mb-4">
        {{ uiStore.locale === 'ar' ? 'إدخال وقت يدوي' : 'Manual Time Entry' }}
      </h3>
      <form @submit.prevent="submitManual" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="input-label">{{ uiStore.locale === 'ar' ? 'المهمة' : 'Task' }}</label>
          <select v-model="manualForm.task_id" class="input" required>
            <option value="">{{ uiStore.locale === 'ar' ? 'اختر...' : 'Select...' }}</option>
            <option v-for="task in taskStore.tasks.slice(0, 50)" :key="task.id" :value="task.id">
              {{ task.title }}
            </option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="input-label">{{ uiStore.locale === 'ar' ? 'البداية' : 'Start' }}</label>
            <input v-model="manualForm.started_at" type="datetime-local" class="input" required />
          </div>
          <div>
            <label class="input-label">{{ uiStore.locale === 'ar' ? 'النهاية' : 'End' }}</label>
            <input v-model="manualForm.ended_at" type="datetime-local" class="input" required />
          </div>
        </div>
        <div class="sm:col-span-2">
          <label class="input-label">{{ uiStore.locale === 'ar' ? 'ملاحظة' : 'Note' }}</label>
          <input v-model="manualForm.note" class="input" :placeholder="uiStore.locale === 'ar' ? 'ما الذي عملت عليه؟' : 'What did you work on?'" />
        </div>
        <div class="sm:col-span-2 flex items-center justify-end gap-2">
          <button type="button" @click="showManualEntry = false" class="btn-secondary btn-sm">{{ uiStore.locale === 'ar' ? 'إلغاء' : 'Cancel' }}</button>
          <button type="submit" class="btn-primary btn-sm" :disabled="timeStore.saving">
            <svg v-if="timeStore.saving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
              <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
            </svg>
            <span>{{ uiStore.locale === 'ar' ? 'حفظ' : 'Save' }}</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Date filters -->
    <div class="flex items-center gap-2 mb-4">
      <button
        v-for="opt in timeOptions"
        :key="opt.days"
        @click="setDateFilter(opt.days)"
        class="btn-sm"
        :class="timeStore.filters.from === new Date(Date.now() - opt.days * 86400000).toISOString().split('T')[0] || (opt.days === 0 && timeStore.filters.from === new Date().toISOString().split('T')[0]) ? 'btn-primary' : 'btn-ghost'"
      >
        {{ opt.label[uiStore.locale] || opt.label.en }}
      </button>
    </div>

    <!-- Summary -->
    <div v-if="timeStore.entries.length" class="card mb-4 flex items-center justify-between">
      <span class="text-sm text-neutral-600">
        {{ uiStore.locale === 'ar' ? 'إجمالي الوقت' : 'Total Time' }}:
        <strong class="text-neutral-900">{{ formatDuration(totalMinutes) }}</strong>
      </span>
      <span class="text-xs text-neutral-400">
        {{ timeStore.pagination.total }} {{ uiStore.locale === 'ar' ? 'إدخال' : 'entries' }}
      </span>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="timeStore.loading" />

    <!-- Empty -->
    <EmptyState
      v-else-if="timeStore.entries.length === 0"
      :title="uiStore.locale === 'ar' ? 'لا توجد إدخالات وقت' : 'No time entries'"
      :description="uiStore.locale === 'ar' ? 'ابدأ المؤقت أو أضف إدخالاً يدوياً' : 'Start the timer or add a manual entry'"
    />

    <!-- Entries list -->
    <div v-else class="space-y-2">
      <div
        v-for="entry in timeStore.entries"
        :key="entry.id"
        class="card flex items-center justify-between gap-4"
      >
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-neutral-900 truncate">{{ entry.task_title || entry.project_name }}</span>
            <span v-if="entry.project_name" class="text-xs text-primary-500">{{ entry.project_name }}</span>
          </div>
          <div class="flex items-center gap-3 mt-1 text-xs text-neutral-500">
            <span>{{ entry.user_name }}</span>
            <span>{{ formatDateTime(entry.started_at) }} → {{ entry.ended_at ? formatDateTime(entry.ended_at) : (uiStore.locale === 'ar' ? 'جاري...' : 'Running...') }}</span>
            <span v-if="entry.note" class="truncate max-w-[200px]">— {{ entry.note }}</span>
          </div>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
          <span class="text-sm font-mono font-medium text-neutral-700">{{ formatDuration(entry.duration_minutes) }}</span>
          <button
            @click="deleteEntry(entry.id)"
            class="btn-icon btn-ghost btn-sm text-neutral-400 hover:text-error-500"
            :aria-label="uiStore.locale === 'ar' ? 'حذف' : 'Delete'"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
              <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <Pagination
      :current-page="timeStore.pagination.currentPage"
      :last-page="timeStore.pagination.lastPage"
      :total="timeStore.pagination.total"
      @page-change="timeStore.setPage"
    />
  </div>
</template>
