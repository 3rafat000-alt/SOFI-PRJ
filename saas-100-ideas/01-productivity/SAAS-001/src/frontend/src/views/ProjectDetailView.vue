<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useProjectStore } from '@/stores/projectStore';
import { useTaskStore } from '@/stores/taskStore';
import { useUiStore } from '@/stores/uiStore';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import TaskCard from '@/components/TaskCard.vue';

const route = useRoute();
const router = useRouter();
const projectStore = useProjectStore();
const taskStore = useTaskStore();
const uiStore = useUiStore();

const activeTab = ref('board');
const projectTasks = ref([]);

onMounted(async () => {
  const project = await projectStore.fetchProject(route.params.id);
  if (!project) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'المشروع غير موجود' : 'Project not found',
      'error',
    );
    router.push('/projects');
    return;
  }
  const tasks = await taskStore.fetchKanbanTasks(route.params.id);
  if (tasks) projectTasks.value = tasks;
});

const project = computed(() => projectStore.currentProject);

const tabs = computed(() => [
  { id: 'board', label: { ar: 'لوحة المهام', en: 'Board' } },
  { id: 'tasks', label: { ar: 'جميع المهام', en: 'All Tasks' } },
  { id: 'members', label: { ar: 'الأعضاء', en: 'Members' } },
  { id: 'settings', label: { ar: 'الإعدادات', en: 'Settings' } },
]);

const statusSummary = computed(() => {
  const tasks = projectTasks.value;
  if (!tasks.length) return null;
  return {
    todo: tasks.filter((t) => t.status === 'todo').length,
    inProgress: tasks.filter((t) => t.status === 'in_progress').length,
    done: tasks.filter((t) => t.status === 'done').length,
  };
});
</script>

<template>
  <div>
    <LoadingSpinner v-if="projectStore.loading && !project" :label="uiStore.locale === 'ar' ? 'جاري تحميل المشروع...' : 'Loading project...'" />

    <template v-if="project">
      <!-- Header -->
      <div class="page-header">
        <div class="flex items-center gap-3">
          <button @click="router.push('/projects')" class="btn-icon btn-ghost" :aria-label="uiStore.locale === 'ar' ? 'رجوع' : 'Back'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 rtl-mirror">
              <polyline points="15 18 9 12 15 6" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-8 flex items-center justify-center text-white font-bold text-xs" :style="{ backgroundColor: project.color || '#4F46E5' }">
                {{ project.name.charAt(0) }}
              </div>
              <h1 class="page-title">{{ project.name }}</h1>
            </div>
            <p v-if="project.description" class="text-sm text-neutral-500 mt-1 mr-11">{{ project.description }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <router-link :to="`/projects/${project.id}/board`" class="btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
              <rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" />
            </svg>
            {{ uiStore.locale === 'ar' ? 'لوحة كانبان' : 'Kanban Board' }}
          </router-link>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex items-center gap-1 border-b border-neutral-200 mb-6">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px',
            activeTab === tab.id
              ? 'border-primary-600 text-primary-600'
              : 'border-transparent text-neutral-500 hover:text-neutral-700',
          ]"
        >
          {{ tab.label[uiStore.locale] || tab.label.en }}
        </button>
      </div>

      <!-- Board tab -->
      <div v-if="activeTab === 'board'" class="grid grid-cols-3 gap-4">
        <div v-for="status in ['todo', 'in_progress', 'done']" :key="status" class="kanban-column">
          <div class="kanban-column-header">
            <span class="text-sm font-semibold text-neutral-700">
              {{ status === 'todo' ? (uiStore.locale === 'ar' ? 'للتنفيذ' : 'To Do') : status === 'in_progress' ? (uiStore.locale === 'ar' ? 'قيد التنفيذ' : 'In Progress') : (uiStore.locale === 'ar' ? 'مكتمل' : 'Done') }}
            </span>
            <span class="badge-neutral text-xs">{{ projectTasks.filter(t => t.status === status).length }}</span>
          </div>
          <TaskCard
            v-for="task in projectTasks.filter(t => t.status === status).sort((a, b) => a.position - b.position)"
            :key="task.id"
            :task="task"
            :draggable="false"
            @click="router.push(`/tasks/${task.id}`)"
          />
          <div v-if="projectTasks.filter(t => t.status === status).length === 0" class="text-center py-8 text-xs text-neutral-400">
            {{ uiStore.locale === 'ar' ? 'لا توجد مهام' : 'No tasks' }}
          </div>
        </div>
      </div>

      <!-- Tasks tab -->
      <div v-if="activeTab === 'tasks'" class="space-y-2">
        <div v-if="projectTasks.length === 0" class="text-center py-12 text-sm text-neutral-400">
          {{ uiStore.locale === 'ar' ? 'لا توجد مهام في هذا المشروع' : 'No tasks in this project' }}
        </div>
        <router-link
          v-for="task in projectTasks"
          :key="task.id"
          :to="`/tasks/${task.id}`"
          class="card flex items-center gap-4 p-4 hover:shadow-elevated transition-shadow"
        >
          <PriorityBadge :priority="task.priority" />
          <span class="flex-1 text-sm font-medium text-neutral-900">{{ task.title }}</span>
          <StatusBadge :status="task.status" />
          <span v-if="task.assignee" class="text-xs text-neutral-500">{{ task.assignee.name }}</span>
          <span v-if="task.due_date" class="text-xs text-neutral-400">
            {{ new Date(task.due_date).toLocaleDateString(uiStore.locale === 'ar' ? 'ar-SA' : 'en-US', { month: 'short', day: 'numeric' }) }}
          </span>
        </router-link>
      </div>

      <!-- Members tab -->
      <div v-if="activeTab === 'members'" class="card">
        <div v-if="!project.members?.length" class="text-center py-8 text-sm text-neutral-400">
          {{ uiStore.locale === 'ar' ? 'لا يوجد أعضاء في هذا المشروع' : 'No members in this project' }}
        </div>
        <div v-else class="space-y-3">
          <div v-for="member in project.members" :key="member.id" class="flex items-center gap-3">
            <MemberAvatar :user="member" size="sm" />
            <span class="text-sm font-medium text-neutral-700">{{ member.name }}</span>
            <span class="text-xs text-neutral-400">{{ member.email }}</span>
          </div>
        </div>
      </div>

      <!-- Settings tab -->
      <div v-if="activeTab === 'settings'" class="card max-w-lg">
        <h3 class="text-sm font-semibold text-neutral-900 mb-4">
          {{ uiStore.locale === 'ar' ? 'إعدادات المشروع' : 'Project Settings' }}
        </h3>
        <p class="text-sm text-neutral-500">
          {{ uiStore.locale === 'ar' ? 'قم بإدارة إعدادات المشروع من صفحة الإعدادات الرئيسية' : 'Manage project settings from the main settings page' }}
        </p>
        <router-link to="/settings" class="btn-secondary btn-sm mt-4 inline-flex">
          {{ uiStore.locale === 'ar' ? 'الإعدادات' : 'Settings' }}
        </router-link>
      </div>

      <!-- Stats summary -->
      <div v-if="statusSummary" class="mt-6 grid grid-cols-3 gap-4">
        <div class="card text-center">
          <p class="text-2xl font-bold text-neutral-900">{{ statusSummary.todo }}</p>
          <p class="text-xs text-neutral-500">{{ uiStore.locale === 'ar' ? 'للتنفيذ' : 'To Do' }}</p>
        </div>
        <div class="card text-center">
          <p class="text-2xl font-bold text-primary-600">{{ statusSummary.inProgress }}</p>
          <p class="text-xs text-neutral-500">{{ uiStore.locale === 'ar' ? 'قيد التنفيذ' : 'In Progress' }}</p>
        </div>
        <div class="card text-center">
          <p class="text-2xl font-bold text-success-600">{{ statusSummary.done }}</p>
          <p class="text-xs text-neutral-500">{{ uiStore.locale === 'ar' ? 'مكتمل' : 'Done' }}</p>
        </div>
      </div>
    </template>
  </div>
</template>
