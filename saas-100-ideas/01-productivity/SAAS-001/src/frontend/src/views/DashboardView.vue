<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import { useTaskStore } from '@/stores/taskStore';
import { useTimeEntryStore } from '@/stores/timeEntryStore';
import ActivityFeed from '@/components/ActivityFeed.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import EmptyState from '@/components/EmptyState.vue';

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();
const taskStore = useTaskStore();
const timeStore = useTimeEntryStore();

const stats = ref(null);
const loadingStats = ref(true);
const statsError = ref(null);

onMounted(async () => {
  await Promise.all([
    fetchStats(),
    taskStore.fetchTasks({ workspace_id: authStore.currentWorkspaceId }),
  ]);
});

const fetchStats = async () => {
  loadingStats.value = true;
  statsError.value = null;
  try {
    const { data } = await api.get('/dashboard/stats', {
      params: { workspace_id: authStore.currentWorkspaceId },
    });
    stats.value = data.data;
  } catch (err) {
    statsError.value = err.message || 'Failed to load stats';
  } finally {
    loadingStats.value = false;
  }
};

const upcomingTasks = computed(() => taskStore.tasks.filter((t) => t.status !== 'done' && t.due_date).slice(0, 5));

const kpiCards = computed(() => [
  {
    icon: 'tasks',
    label: { ar: 'إجمالي المهام', en: 'Total Tasks' },
    value: stats.value?.tasks?.total ?? '-',
    color: 'bg-primary-100 text-primary-600',
    trend: stats.value?.tasks?.overdue ? { label: { ar: `${stats.value.tasks.overdue} متأخرة`, en: `${stats.value.tasks.overdue} overdue` }, dir: 'down' } : null,
  },
  {
    icon: 'clock',
    label: { ar: 'الوقت اليوم', en: 'Time Today' },
    value: stats.value?.time?.today_minutes ? `${Math.round(stats.value.time.today_minutes / 60)}h` : '-',
    color: 'bg-secondary-100 text-secondary-600',
    trend: stats.value?.time?.week_minutes ? { label: { ar: `${Math.round(stats.value.time.week_minutes / 60)}h هذا الأسبوع`, en: `${Math.round(stats.value.time.week_minutes / 60)}h this week` }, dir: 'up' } : null,
  },
  {
    icon: 'folder',
    label: { ar: 'المشاريع النشطة', en: 'Active Projects' },
    value: stats.value?.projects?.active ?? '-',
    color: 'bg-success-100 text-success-600',
  },
  {
    icon: 'users',
    label: { ar: 'أعضاء الفريق', en: 'Team Members' },
    value: stats.value?.members?.total ?? '-',
    color: 'bg-warning-100 text-warning-600',
    trend: stats.value?.members?.active_today ? { label: { ar: `${stats.value.members.active_today} نشط اليوم`, en: `${stats.value.members.active_today} active today` }, dir: 'up' } : null,
  },
]);
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'لوحة التحكم' : 'Dashboard' }}</h1>
        <p class="text-sm text-neutral-500 mt-1">
          {{ uiStore.locale === 'ar' ? `مرحباً، ${authStore.userName}` : `Welcome, ${authStore.userName}` }}
        </p>
      </div>
      <button @click="fetchStats" class="btn-secondary btn-sm" :disabled="loadingStats">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" :class="{ 'animate-spin': loadingStats }" aria-hidden="true">
          <polyline points="23 4 23 10 17 10" /><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
        </svg>
        {{ uiStore.locale === 'ar' ? 'تحديث' : 'Refresh' }}
      </button>
    </div>

    <!-- Error state -->
    <div v-if="statsError" class="mb-6 p-4 rounded-8 bg-error-50 border border-error-200 text-sm text-error-700 flex items-center justify-between" role="alert">
      <span>{{ statsError }}</span>
      <button @click="fetchStats" class="btn-sm btn-secondary">{{ uiStore.locale === 'ar' ? 'إعادة المحاولة' : 'Retry' }}</button>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="loadingStats" :label="uiStore.locale === 'ar' ? 'جاري تحميل الإحصائيات...' : 'Loading stats...'" />

    <!-- KPI Cards -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div v-for="card in kpiCards" :key="card.label.ar" class="stat-card">
        <div :class="['stat-icon', card.color]" aria-hidden="true">
          <svg v-if="card.icon === 'tasks'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
            <polyline points="9 11 12 14 22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
          </svg>
          <svg v-else-if="card.icon === 'clock'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
            <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
          </svg>
          <svg v-else-if="card.icon === 'folder'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
            <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" />
          </svg>
          <svg v-else-if="card.icon === 'users'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </div>
        <div class="flex-1">
          <p class="stat-value">{{ card.value }}</p>
          <p class="stat-label">{{ card.label[uiStore.locale] || card.label.en }}</p>
          <p v-if="card.trend" :class="['stat-trend', card.trend.dir === 'up' ? 'text-success-600' : 'text-error-600']">
            {{ card.trend.label[uiStore.locale] || card.trend.label.en }}
          </p>
        </div>
      </div>
    </div>

    <!-- Bottom: Upcoming + Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Upcoming tasks -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-neutral-900">
            {{ uiStore.locale === 'ar' ? 'المهام القادمة' : 'Upcoming Tasks' }}
          </h2>
          <router-link to="/tasks" class="text-xs text-primary-600 hover:text-primary-700 font-medium">
            {{ uiStore.locale === 'ar' ? 'عرض الكل' : 'View all' }}
          </router-link>
        </div>

        <div v-if="upcomingTasks.length === 0" class="text-center py-8 text-sm text-neutral-400">
          {{ uiStore.locale === 'ar' ? 'لا توجد مهام قادمة' : 'No upcoming tasks' }}
        </div>

        <div v-else class="space-y-2">
          <router-link
            v-for="task in upcomingTasks"
            :key="task.id"
            :to="`/tasks/${task.id}`"
            class="flex items-center gap-3 p-2.5 rounded-8 hover:bg-neutral-50 transition-colors group"
          >
            <div
              :class="[
                'w-2 h-2 rounded-full flex-shrink-0',
                task.priority === 'urgent' ? 'bg-error-500' :
                task.priority === 'high' ? 'bg-warning-500' :
                task.priority === 'medium' ? 'bg-primary-500' : 'bg-neutral-300',
              ]"
              aria-hidden="true"
            />
            <span class="flex-1 text-sm text-neutral-700 truncate group-hover:text-primary-600">{{ task.title }}</span>
            <span class="text-xs text-neutral-400" :class="{ 'text-error-500 font-medium': task.is_overdue }">
              <template v-if="task.is_overdue">{{ uiStore.locale === 'ar' ? 'متأخرة' : 'Overdue' }}</template>
              <template v-else>{{ new Date(task.due_date).toLocaleDateString(uiStore.locale === 'ar' ? 'ar-SA' : 'en-US', { month: 'short', day: 'numeric' }) }}</template>
            </span>
          </router-link>
        </div>
      </div>

      <!-- Activity feed -->
      <div class="card">
        <ActivityFeed />
      </div>
    </div>
  </div>
</template>
