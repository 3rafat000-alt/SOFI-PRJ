<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useTaskStore } from '@/stores/taskStore';
import { useProjectStore } from '@/stores/projectStore';
import { useUiStore } from '@/stores/uiStore';
import TaskCard from '@/components/TaskCard.vue';
import TaskForm from '@/components/TaskForm.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import EmptyState from '@/components/EmptyState.vue';

const route = useRoute();
const router = useRouter();
const taskStore = useTaskStore();
const projectStore = useProjectStore();
const uiStore = useUiStore();

const showTaskForm = ref(false);
const dragOverColumn = ref(null);
const projectName = ref('');
const touchDragTaskId = ref(null);

const columns = computed(() => [
  {
    id: 'todo',
    title: { ar: 'للتنفيذ', en: 'To Do' },
    tasks: taskStore.todoTasks,
    color: 'border-t-neutral-400',
  },
  {
    id: 'in_progress',
    title: { ar: 'قيد التنفيذ', en: 'In Progress' },
    tasks: taskStore.inProgressTasks,
    color: 'border-t-primary-500',
  },
  {
    id: 'done',
    title: { ar: 'مكتمل', en: 'Done' },
    tasks: taskStore.doneTasks,
    color: 'border-t-success-500',
  },
]);

onMounted(async () => {
  const projectId = route.params.id;
  await taskStore.fetchKanbanTasks(projectId);
  const project = await projectStore.fetchProject(projectId);
  if (project) projectName.value = project.name;
});

// Drag and drop handlers
const onDragOver = (e, columnId) => {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
  dragOverColumn.value = columnId;
};

const onDragLeave = () => {
  dragOverColumn.value = null;
};

const onDrop = async (e, columnId) => {
  e.preventDefault();
  dragOverColumn.value = null;
  const taskId = e.dataTransfer.getData('text/plain');
  if (!taskId) return;

  const targetTasks = taskStore.kanbanColumns[columnId] || [];
  const newPosition = targetTasks.length + 1;
  await taskStore.moveTask(taskId, columnId, newPosition);
};

const onTaskTouchStart = (e, task) => {
  touchDragTaskId.value = task.id;
};

const onBoardTouchMove = (e) => {
  e.preventDefault();
  const touch = e.touches[0];
  const el = document.elementFromPoint(touch.clientX, touch.clientY);
  if (!el) return;
  const column = el.closest('[data-column-id]');
  dragOverColumn.value = column?.getAttribute('data-column-id') || null;
};

const onBoardTouchEnd = async (e) => {
  const taskId = touchDragTaskId.value;
  touchDragTaskId.value = null;
  dragOverColumn.value = null;
  if (!taskId) return;

  const touch = e.changedTouches[0];
  const el = document.elementFromPoint(touch.clientX, touch.clientY);
  if (!el) return;
  const column = el.closest('[data-column-id]');
  if (!column) return;
  const columnId = column.getAttribute('data-column-id');
  if (!columnId) return;

  const targetTasks = taskStore.kanbanColumns[columnId] || [];
  const newPosition = targetTasks.length + 1;
  await taskStore.moveTask(taskId, columnId, newPosition);
};

const handleTaskClick = (task) => {
  router.push(`/tasks/${task.id}`);
};

const handleTaskCreated = (task) => {
  showTaskForm.value = false;
  if (task) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم إنشاء المهمة' : 'Task created',
      'success',
    );
  }
};
</script>

<template>
  <div>
    <div class="page-header">
      <div class="flex items-center gap-3">
        <button @click="router.push(`/projects/${route.params.id}`)" class="btn-icon btn-ghost" :aria-label="uiStore.locale === 'ar' ? 'رجوع' : 'Back'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 rtl-mirror">
            <polyline points="15 18 9 12 15 6" />
          </svg>
        </button>
        <div>
          <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'لوحة كانبان' : 'Kanban Board' }}</h1>
          <p v-if="projectName" class="text-sm text-neutral-500">{{ projectName }}</p>
        </div>
      </div>
      <button @click="showTaskForm = true" class="btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
          <line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        {{ uiStore.locale === 'ar' ? 'مهمة جديدة' : 'New Task' }}
      </button>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="taskStore.loading && !taskStore.todoTasks.length" />

    <!-- Empty state -->
    <EmptyState
      v-else-if="!taskStore.todoTasks.length && !taskStore.inProgressTasks.length && !taskStore.doneTasks.length"
      :title="uiStore.locale === 'ar' ? 'لا توجد مهام' : 'No tasks'"
      :description="uiStore.locale === 'ar' ? 'أنشئ أول مهمة لبدء العمل على اللوحة' : 'Create your first task to get started on the board'"
      :action="{ label: uiStore.locale === 'ar' ? 'إنشاء مهمة' : 'Create Task', onClick: () => showTaskForm = true }"
    />

    <!-- Kanban board -->
    <div
      v-else
      class="grid grid-cols-1 md:grid-cols-3 gap-4 min-h-[600px]"
      @touchmove="onBoardTouchMove"
      @touchend="onBoardTouchEnd"
    >
      <div
        v-for="column in columns"
        :key="column.id"
        :data-column-id="column.id"
        :class="[
          'kanban-column border-t-4',
          column.color,
          dragOverColumn === column.id ? 'bg-neutral-200 border-dashed border-primary-400' : '',
        ]"
        @dragover="(e) => onDragOver(e, column.id)"
        @dragleave="onDragLeave"
        @drop="(e) => onDrop(e, column.id)"
        role="region"
        :aria-label="column.title[uiStore.locale] || column.title.en"
      >
        <div class="kanban-column-header">
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-semibold text-neutral-700">
              {{ column.title[uiStore.locale] || column.title.en }}
            </h3>
            <span class="badge-neutral text-xs">{{ column.tasks.length }}</span>
          </div>
        </div>

        <div class="flex flex-col gap-2 min-h-[200px]">
          <TaskCard
            v-for="task in column.tasks"
            :key="task.id"
            :task="task"
            :draggable="true"
            @click="handleTaskClick"
            @touchstart.prevent="(e) => onTaskTouchStart(e, task)"
          />

          <!-- Empty column hint -->
          <div
            v-if="column.tasks.length === 0"
            class="flex-1 flex items-center justify-center border-2 border-dashed border-neutral-300 rounded-8 p-4"
          >
            <p class="text-xs text-neutral-400 text-center">
              {{ uiStore.locale === 'ar' ? 'اسحب المهمة هنا' : 'Drop tasks here' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Task form modal -->
    <TaskForm
      :visible="showTaskForm"
      :project-id="route.params.id"
      :members="projectStore.currentProject?.members || []"
      :saving="taskStore.saving"
      @save="async (data) => { const t = await taskStore.createTask(data); handleTaskCreated(t); }"
      @close="showTaskForm = false"
      @update:visible="showTaskForm = $event"
    />
  </div>
</template>
