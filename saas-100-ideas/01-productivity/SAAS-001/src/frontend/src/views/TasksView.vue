<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useTaskStore } from '@/stores/taskStore';
import { useUiStore } from '@/stores/uiStore';
import { useProjectStore } from '@/stores/projectStore';
import TaskCard from '@/components/TaskCard.vue';
import TaskForm from '@/components/TaskForm.vue';
import SearchInput from '@/components/SearchInput.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';

const router = useRouter();
const route = useRoute();
const taskStore = useTaskStore();
const uiStore = useUiStore();
const projectStore = useProjectStore();

const showForm = ref(false);

onMounted(async () => {
  await projectStore.fetchProjects();
  if (route.query.search) {
    taskStore.setSearch(route.query.search);
  } else {
    taskStore.fetchTasks();
  }
});

const priorityFilter = computed({
  get: () => taskStore.filters.priority,
  set: (val) => taskStore.setFilters({ priority: val }),
});

const statusFilter = computed({
  get: () => taskStore.filters.status,
  set: (val) => taskStore.setFilters({ status: val }),
});

const projectFilter = computed({
  get: () => taskStore.filters.projectId,
  set: (val) => taskStore.setFilters({ projectId: val || null }),
});

const handleSearch = (query) => {
  taskStore.setSearch(query);
};

const handleTaskClick = (task) => {
  router.push(`/tasks/${task.id}`);
};

const handleSaveTask = async (data) => {
  const task = await taskStore.createTask(data);
  showForm.value = false;
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
      <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'جميع المهام' : 'All Tasks' }}</h1>
      <div class="flex items-center gap-3">
        <div class="hidden sm:block w-64">
          <SearchInput
            :placeholder="uiStore.locale === 'ar' ? 'ابحث عن مهمة...' : 'Search tasks...'"
            @search="handleSearch"
          />
        </div>
        <button @click="showForm = true" class="btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          {{ uiStore.locale === 'ar' ? 'مهمة جديدة' : 'New Task' }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
      <select v-model="statusFilter" class="input w-auto text-sm py-1.5">
        <option value="all">{{ uiStore.locale === 'ar' ? 'جميع الحالات' : 'All Status' }}</option>
        <option value="todo">{{ uiStore.locale === 'ar' ? 'للتنفيذ' : 'To Do' }}</option>
        <option value="in_progress">{{ uiStore.locale === 'ar' ? 'قيد التنفيذ' : 'In Progress' }}</option>
        <option value="done">{{ uiStore.locale === 'ar' ? 'مكتمل' : 'Done' }}</option>
      </select>
      <select v-model="priorityFilter" class="input w-auto text-sm py-1.5">
        <option value="all">{{ uiStore.locale === 'ar' ? 'جميع الأولويات' : 'All Priority' }}</option>
        <option value="urgent">{{ uiStore.locale === 'ar' ? 'عاجل' : 'Urgent' }}</option>
        <option value="high">{{ uiStore.locale === 'ar' ? 'عالية' : 'High' }}</option>
        <option value="medium">{{ uiStore.locale === 'ar' ? 'متوسطة' : 'Medium' }}</option>
        <option value="low">{{ uiStore.locale === 'ar' ? 'منخفضة' : 'Low' }}</option>
      </select>
      <select v-model="projectFilter" class="input w-auto text-sm py-1.5">
        <option :value="null">{{ uiStore.locale === 'ar' ? 'جميع المشاريع' : 'All Projects' }}</option>
        <option
          v-for="p in projectStore.projects"
          :key="p.id"
          :value="p.id"
        >{{ p.name }}</option>
      </select>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="taskStore.loading" />

    <!-- Error -->
    <div v-else-if="taskStore.error" class="card text-center py-8">
      <p class="text-error-600 mb-3">{{ taskStore.error }}</p>
      <button @click="taskStore.fetchTasks()" class="btn-secondary">{{ uiStore.locale === 'ar' ? 'إعادة المحاولة' : 'Retry' }}</button>
    </div>

    <!-- Empty -->
    <EmptyState
      v-else-if="taskStore.filteredTasks.length === 0"
      :title="uiStore.locale === 'ar' ? 'لا توجد مهام' : 'No tasks'"
      :description="uiStore.locale === 'ar' ? 'أنشئ أول مهمة لبدء العمل' : 'Create your first task to get started'"
      :action="{ label: uiStore.locale === 'ar' ? 'إنشاء مهمة' : 'Create Task', onClick: () => showForm = true }"
    />

    <!-- Task list -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <TaskCard
        v-for="task in taskStore.filteredTasks"
        :key="task.id"
        :task="task"
        :draggable="false"
        @click="handleTaskClick"
      />
    </div>

    <!-- Pagination -->
    <Pagination
      :current-page="taskStore.pagination.currentPage"
      :last-page="taskStore.pagination.lastPage"
      :total="taskStore.pagination.total"
      @page-change="taskStore.setPage"
    />

    <!-- Task form -->
    <TaskForm
      :visible="showForm"
      :saving="taskStore.saving"
      @save="handleSaveTask"
      @close="showForm = false"
      @update:visible="showForm = $event"
    />
  </div>
</template>
