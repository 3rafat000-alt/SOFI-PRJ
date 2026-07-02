<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useProjectStore } from '@/stores/projectStore';
import { useUiStore } from '@/stores/uiStore';
import ProjectForm from '@/components/ProjectForm.vue';
import EmptyState from '@/components/EmptyState.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import Pagination from '@/components/Pagination.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import SearchInput from '@/components/SearchInput.vue';

const router = useRouter();
const projectStore = useProjectStore();
const uiStore = useUiStore();

const showForm = ref(false);
const editingProject = ref(null);
const showDelete = ref(false);
const deletingId = ref(null);

onMounted(() => {
  projectStore.fetchProjects();
});

const openCreate = () => {
  editingProject.value = null;
  showForm.value = true;
};

const openEdit = (project, e) => {
  e.stopPropagation();
  editingProject.value = project;
  showForm.value = true;
};

const confirmDelete = (project, e) => {
  e.stopPropagation();
  deletingId.value = project.id;
  showDelete.value = true;
};

const handleDelete = async () => {
  if (!deletingId.value) return;
  await projectStore.deleteProject(deletingId.value);
  showDelete.value = false;
  deletingId.value = null;
  uiStore.addToast(
    uiStore.locale === 'ar' ? 'تم حذف المشروع' : 'Project deleted',
    'success',
  );
};

const handleSave = async (formData) => {
  if (editingProject.value) {
    await projectStore.updateProject(editingProject.value.id, formData);
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم تحديث المشروع' : 'Project updated',
      'success',
    );
  } else {
    await projectStore.createProject(formData);
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم إنشاء المشروع' : 'Project created',
      'success',
    );
  }
  showForm.value = false;
  editingProject.value = null;
};

const handleSearch = (query) => {
  projectStore.setSearch(query);
};
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'المشاريع' : 'Projects' }}</h1>
      <div class="flex items-center gap-3">
        <div class="w-64">
          <SearchInput
            :placeholder="uiStore.locale === 'ar' ? 'ابحث عن مشروع...' : 'Search projects...'"
            @search="handleSearch"
          />
        </div>
        <button @click="openCreate" class="btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          {{ uiStore.locale === 'ar' ? 'مشروع جديد' : 'New Project' }}
        </button>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="flex items-center gap-1 mb-6">
      <button
        @click="projectStore.setFilter('all')"
        :class="['btn-sm', projectStore.filter === 'all' ? 'btn-primary' : 'btn-ghost']"
      >
        {{ uiStore.locale === 'ar' ? 'الكل' : 'All' }}
      </button>
      <button
        @click="projectStore.setFilter('active')"
        :class="['btn-sm', projectStore.filter === 'active' ? 'btn-primary' : 'btn-ghost']"
      >
        {{ uiStore.locale === 'ar' ? 'النشطة' : 'Active' }}
      </button>
      <button
        @click="projectStore.setFilter('archived')"
        :class="['btn-sm', projectStore.filter === 'archived' ? 'btn-primary' : 'btn-ghost']"
      >
        {{ uiStore.locale === 'ar' ? 'المؤرشفة' : 'Archived' }}
      </button>
    </div>

    <!-- Loading -->
    <LoadingSpinner v-if="projectStore.loading" :label="uiStore.locale === 'ar' ? 'جاري تحميل المشاريع...' : 'Loading projects...'" />

    <!-- Error -->
    <div v-else-if="projectStore.error" class="card text-center py-8">
      <p class="text-error-600 mb-3">{{ projectStore.error }}</p>
      <button @click="projectStore.fetchProjects()" class="btn-secondary">{{ uiStore.locale === 'ar' ? 'إعادة المحاولة' : 'Retry' }}</button>
    </div>

    <!-- Empty -->
    <EmptyState
      v-else-if="projectStore.filteredProjects.length === 0"
      :title="uiStore.locale === 'ar' ? 'لا توجد مشاريع' : 'No projects'"
      :description="uiStore.locale === 'ar' ? 'أنشئ أول مشروع لبدء إدارة المهام' : 'Create your first project to start managing tasks'"
      :action="{ label: uiStore.locale === 'ar' ? 'إنشاء مشروع' : 'Create Project', onClick: openCreate }"
    />

    <!-- Project grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="project in projectStore.filteredProjects"
        :key="project.id"
        @click="router.push(`/projects/${project.id}`)"
        class="card-hover cursor-pointer"
        role="button"
        :tabindex="0"
        @keydown.enter="router.push(`/projects/${project.id}`)"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-8 flex items-center justify-center text-white font-bold text-sm"
              :style="{ backgroundColor: project.color || '#4F46E5' }"
            >
              {{ project.name.charAt(0) }}
            </div>
            <div>
              <h3 class="text-sm font-semibold text-neutral-900">{{ project.name }}</h3>
              <p class="text-xs text-neutral-500">{{ project.member_count || 0 }} {{ uiStore.locale === 'ar' ? 'أعضاء' : 'members' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <button
              @click="openEdit(project, $event)"
              class="btn-icon btn-ghost btn-sm"
              :aria-label="uiStore.locale === 'ar' ? 'تعديل' : 'Edit'"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
              </svg>
            </button>
            <button
              @click="confirmDelete(project, $event)"
              class="btn-icon btn-ghost btn-sm text-error-500"
              :aria-label="uiStore.locale === 'ar' ? 'حذف' : 'Delete'"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
              </svg>
            </button>
          </div>
        </div>

        <p v-if="project.description" class="text-xs text-neutral-500 mb-3 line-clamp-2">{{ project.description }}</p>

        <!-- Task counts -->
        <div class="flex items-center gap-3 text-xs text-neutral-500">
          <span v-if="project.task_count">
            {{ project.task_count.todo || 0 }} {{ uiStore.locale === 'ar' ? 'للتنفيذ' : 'todo' }}
          </span>
          <span v-if="project.task_count" class="text-primary-500">
            {{ project.task_count.in_progress || 0 }} {{ uiStore.locale === 'ar' ? 'قيد التنفيذ' : 'in-progress' }}
          </span>
          <span v-if="project.task_count" class="text-success-500">
            {{ project.task_count.done || 0 }} {{ uiStore.locale === 'ar' ? 'مكتمل' : 'done' }}
          </span>
        </div>

        <!-- Progress bar -->
        <div v-if="project.task_count?.total" class="mt-3 w-full h-1.5 bg-neutral-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-primary-500 to-success-500 rounded-full transition-all"
            :style="{ width: `${(project.task_count.done / project.task_count.total) * 100}%` }"
          />
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <Pagination
      :current-page="projectStore.pagination.currentPage"
      :last-page="projectStore.pagination.lastPage"
      :total="projectStore.pagination.total"
      @page-change="projectStore.setPage"
    />

    <!-- Forms -->
    <ProjectForm
      :visible="showForm"
      :project="editingProject"
      :saving="projectStore.saving"
      @save="handleSave"
      @close="showForm = false; editingProject = null"
      @update:visible="showForm = $event"
    />

    <ConfirmDialog
      :visible="showDelete"
      :title="uiStore.locale === 'ar' ? 'حذف المشروع' : 'Delete Project'"
      :message="uiStore.locale === 'ar' ? 'هل أنت متأكد من حذف هذا المشروع؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this project? This action cannot be undone.'"
      :loading="projectStore.saving"
      @confirm="handleDelete"
      @cancel="showDelete = false; deletingId = null"
      @update:visible="showDelete = $event"
    />
  </div>
</template>
