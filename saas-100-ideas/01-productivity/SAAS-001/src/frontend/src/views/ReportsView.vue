<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useTimeEntryStore } from '@/stores/timeEntryStore';
import { useTaskStore } from '@/stores/taskStore';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Bar, Pie } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ArcElement } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ArcElement);

const timeStore = useTimeEntryStore();
const taskStore = useTaskStore();
const authStore = useAuthStore();
const uiStore = useUiStore();

const period = ref('30');
const reportData = ref(null);
const loadingReport = ref(false);

const periodOptions = [
  { value: '7', label: { ar: 'آخر 7 أيام', en: 'Last 7 Days' } },
  { value: '30', label: { ar: 'آخر 30 يوماً', en: 'Last 30 Days' } },
  { value: '90', label: { ar: 'آخر 90 يوماً', en: 'Last 90 Days' } },
];

onMounted(async () => {
  await loadReport();
  await taskStore.fetchTasks({ workspace_id: authStore.currentWorkspaceId, limit: 100 });
});

watch(period, () => { loadReport(); });

const loadReport = async () => {
  loadingReport.value = true;
  const to = new Date().toISOString().split('T')[0];
  const from = new Date(Date.now() - Number(period.value) * 86400000).toISOString().split('T')[0];
  const result = await timeStore.fetchReport({
    workspace_id: authStore.currentWorkspaceId,
    from,
    to,
    group_by: 'day',
  });
  if (result) reportData.value = result;
  loadingReport.value = false;
};

// Chart data
const barChartData = computed(() => {
  if (!reportData.value?.entries) return null;
  const labels = reportData.value.entries.map((e) => e.date);
  const data = reportData.value.entries.map((e) => Math.round(e.minutes / 60));
  return {
    labels,
    datasets: [{
      label: uiStore.locale === 'ar' ? 'ساعات' : 'Hours',
      data,
      backgroundColor: '#2563EB',
      borderRadius: 4,
    }],
  };
});

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1 },
    },
  },
};

const pieChartData = computed(() => {
  if (!reportData.value?.entries?.[0]?.projects) return null;
  const projects = reportData.value.entries[0].projects || [];
  if (!projects.length) {
    // Aggregate across all entries
    const projectMap = {};
    reportData.value.entries.forEach((e) => {
      e.projects?.forEach((p) => {
        projectMap[p.project_name] = (projectMap[p.project_name] || 0) + p.minutes;
      });
    });
    const names = Object.keys(projectMap);
    if (!names.length) return null;
    return {
      labels: names,
      datasets: [{
        data: Object.values(projectMap).map((m) => Math.round(m / 60)),
        backgroundColor: ['#2563EB', '#7C3AED', '#10B981', '#F59E0B', '#EF4444', '#4F46E5', '#EC4899', '#06B6D4'],
      }],
    };
  }
  return {
    labels: projects.map((p) => p.project_name),
    datasets: [{
      data: projects.map((p) => Math.round(p.minutes / 60)),
      backgroundColor: ['#2563EB', '#7C3AED', '#10B981', '#F59E0B', '#EF4444'],
    }],
  };
});

const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { font: { size: 11 } },
    },
  },
};

// Task completion stats
const taskStats = computed(() => {
  const tasks = taskStore.tasks;
  if (!tasks.length) return null;
  return {
    total: tasks.length,
    todo: tasks.filter((t) => t.status === 'todo').length,
    inProgress: tasks.filter((t) => t.status === 'in_progress').length,
    done: tasks.filter((t) => t.status === 'done').length,
    completionRate: Math.round((tasks.filter((t) => t.status === 'done').length / tasks.length) * 100),
  };
});

const kpiCards = computed(() => [
  {
    label: { ar: 'إجمالي الساعات', en: 'Total Hours' },
    value: reportData.value?.summary?.total_hours ? `${Math.round(reportData.value.summary.total_hours)}h` : '-',
    color: 'bg-primary-100 text-primary-600',
  },
  {
    label: { ar: 'المهام المكتملة', en: 'Tasks Completed' },
    value: taskStats.value?.done || '-',
    color: 'bg-success-100 text-success-600',
  },
  {
    label: { ar: 'المشاريع النشطة', en: 'Active Projects' },
    value: reportData.value?.summary?.project_count || '-',
    color: 'bg-secondary-100 text-secondary-600',
  },
  {
    label: { ar: 'معدل الإنجاز', en: 'Completion Rate' },
    value: taskStats.value?.completionRate ? `${taskStats.value.completionRate}%` : '-',
    color: 'bg-warning-100 text-warning-600',
  },
]);
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'التقارير والتحليلات' : 'Reports & Analytics' }}</h1>
      <div class="flex items-center gap-2">
        <button
          v-for="opt in periodOptions"
          :key="opt.value"
          @click="period = opt.value"
          :class="['btn-sm', period === opt.value ? 'btn-primary' : 'btn-ghost']"
        >
          {{ opt.label[uiStore.locale] || opt.label.en }}
        </button>
        <button
          @click="loadReport"
          class="btn-secondary btn-sm"
          :disabled="loadingReport"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" :class="{ 'animate-spin': loadingReport }" aria-hidden="true">
            <polyline points="23 4 23 10 17 10" /><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
          </svg>
          {{ uiStore.locale === 'ar' ? 'تحديث' : 'Refresh' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="loadingReport" />

    <!-- No data -->
    <EmptyState
      v-else-if="!reportData && !taskStats"
      :title="uiStore.locale === 'ar' ? 'لا توجد بيانات كافية' : 'Not enough data'"
      :description="uiStore.locale === 'ar' ? 'أكمل بعض المهام وسجل الوقت لرؤية التقارير' : 'Complete some tasks and log time to see reports'"
    />

    <template v-else>
      <!-- KPI cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div v-for="card in kpiCards" :key="card.label.ar" class="stat-card">
          <div :class="['stat-icon', card.color]" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
              <line x1="12" y1="20" x2="12" y2="10" /><line x1="18" y1="20" x2="18" y2="4" /><line x1="6" y1="20" x2="6" y2="16" />
            </svg>
          </div>
          <div>
            <p class="stat-value">{{ card.value }}</p>
            <p class="stat-label">{{ card.label[uiStore.locale] || card.label.en }}</p>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bar chart: Hours per day -->
        <div class="card">
          <h3 class="text-sm font-semibold text-neutral-900 mb-4">
            {{ uiStore.locale === 'ar' ? 'ساعات العمل يومياً' : 'Daily Work Hours' }}
          </h3>
          <div class="h-64">
            <Bar v-if="barChartData" :data="barChartData" :options="barChartOptions" />
            <p v-else class="text-sm text-neutral-400 text-center py-8">
              {{ uiStore.locale === 'ar' ? 'لا توجد بيانات كافية' : 'Not enough data' }}
            </p>
          </div>
        </div>

        <!-- Pie chart: Time by project -->
        <div class="card">
          <h3 class="text-sm font-semibold text-neutral-900 mb-4">
            {{ uiStore.locale === 'ar' ? 'الوقت حسب المشروع' : 'Time by Project' }}
          </h3>
          <div class="h-64">
            <Pie v-if="pieChartData" :data="pieChartData" :options="pieChartOptions" />
            <p v-else class="text-sm text-neutral-400 text-center py-8">
              {{ uiStore.locale === 'ar' ? 'لا توجد بيانات كافية' : 'Not enough data' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Task completion bar -->
      <div v-if="taskStats" class="card mt-6">
        <h3 class="text-sm font-semibold text-neutral-900 mb-4">
          {{ uiStore.locale === 'ar' ? 'حالة المهام' : 'Task Status' }}
        </h3>
        <div class="flex items-center gap-4 mb-4">
          <div class="flex items-center gap-2 text-xs text-neutral-500">
            <span class="w-3 h-3 rounded-4 bg-neutral-300" aria-hidden="true" />
            {{ uiStore.locale === 'ar' ? 'للتنفيذ' : 'Todo' }}: {{ taskStats.todo }}
          </div>
          <div class="flex items-center gap-2 text-xs text-neutral-500">
            <span class="w-3 h-3 rounded-4 bg-primary-500" aria-hidden="true" />
            {{ uiStore.locale === 'ar' ? 'قيد التنفيذ' : 'In Progress' }}: {{ taskStats.inProgress }}
          </div>
          <div class="flex items-center gap-2 text-xs text-neutral-500">
            <span class="w-3 h-3 rounded-4 bg-success-500" aria-hidden="true" />
            {{ uiStore.locale === 'ar' ? 'مكتمل' : 'Done' }}: {{ taskStats.done }}
          </div>
          <div class="flex-1" />
          <span class="text-xs font-medium text-neutral-600">{{ taskStats.completionRate }}%</span>
        </div>
        <div class="w-full h-3 bg-neutral-100 rounded-full overflow-hidden flex">
          <div class="h-full bg-neutral-300 transition-all" :style="{ width: (taskStats.todo / taskStats.total) * 100 + '%' }" />
          <div class="h-full bg-primary-500 transition-all" :style="{ width: (taskStats.inProgress / taskStats.total) * 100 + '%' }" />
          <div class="h-full bg-success-500 transition-all" :style="{ width: (taskStats.done / taskStats.total) * 100 + '%' }" />
        </div>
      </div>
    </template>
  </div>
</template>
