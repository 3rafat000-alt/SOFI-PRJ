<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useTaskStore } from '@/stores/taskStore';
import { useUiStore } from '@/stores/uiStore';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import TimerWidget from '@/components/TimerWidget.vue';
import CommentList from '@/components/CommentList.vue';
import AttachmentList from '@/components/AttachmentList.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

const route = useRoute();
const router = useRouter();
const taskStore = useTaskStore();
const uiStore = useUiStore();

const task = ref(null);
const loading = ref(true);
const showDelete = ref(false);
const editingField = ref(null);
const editValue = ref('');

onMounted(async () => {
  const result = await taskStore.fetchTask(route.params.id);
  if (!result) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'المهمة غير موجودة' : 'Task not found',
      'error',
    );
    router.push('/tasks');
    return;
  }
  task.value = result;
  loading.value = false;
});

const handleBack = () => {
  if (window.history.length > 1) {
    router.back();
  } else {
    router.push('/tasks');
  }
};

const handleDelete = async () => {
  const ok = await taskStore.deleteTask(task.value.id);
  if (ok) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم حذف المهمة' : 'Task deleted',
      'success',
    );
    router.push('/tasks');
  }
  showDelete.value = false;
};

const quickStatusChange = async (newStatus) => {
  if (!task.value) return;
  const result = await taskStore.quickStatusChange(task.value.id, newStatus);
  if (result) {
    task.value.status = newStatus;
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم تغيير الحالة' : 'Status updated',
      'success',
    );
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString(uiStore.locale === 'ar' ? 'ar-SA' : 'en-US', {
    year: 'numeric', month: 'long', day: 'numeric',
  });
};
</script>

<template>
  <div>
    <LoadingSpinner v-if="loading" :label="uiStore.locale === 'ar' ? 'جاري تحميل المهمة...' : 'Loading task...'" />

    <template v-if="task">
      <!-- Header -->
      <div class="page-header">
        <div class="flex items-center gap-3">
          <button @click="handleBack" class="btn-icon btn-ghost" :aria-label="uiStore.locale === 'ar' ? 'رجوع' : 'Back'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 rtl-mirror">
              <polyline points="15 18 9 12 15 6" />
            </svg>
          </button>
          <h1 class="page-title text-xl">{{ task.title }}</h1>
        </div>
        <div class="flex items-center gap-2">
          <button @click="showDelete = true" class="btn-danger btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4" aria-hidden="true">
              <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            </svg>
            {{ uiStore.locale === 'ar' ? 'حذف' : 'Delete' }}
          </button>
        </div>
      </div>

      <!-- Main content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <div class="card">
            <h3 class="text-sm font-semibold text-neutral-900 mb-2">
              {{ uiStore.locale === 'ar' ? 'الوصف' : 'Description' }}
            </h3>
            <p v-if="task.description" class="text-sm text-neutral-700 whitespace-pre-wrap">{{ task.description }}</p>
            <p v-else class="text-sm text-neutral-400 italic">
              {{ uiStore.locale === 'ar' ? 'لا يوجد وصف' : 'No description' }}
            </p>
          </div>

          <!-- Comments -->
          <div class="card">
            <CommentList :task-id="task.id" />
          </div>

          <!-- Attachments -->
          <div class="card">
            <AttachmentList :task-id="task.id" :attachments="task.attachments || []" />
          </div>
        </div>

        <!-- Right: Meta -->
        <div class="space-y-4">
          <!-- Timer -->
          <div class="card p-0 overflow-hidden">
            <TimerWidget :task-id="task.id" :task-title="task.title" />
          </div>

          <!-- Status -->
          <div class="card">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase mb-3">
              {{ uiStore.locale === 'ar' ? 'الحالة' : 'Status' }}
            </h4>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="s in ['todo', 'in_progress', 'done']"
                :key="s"
                @click="quickStatusChange(s)"
                :class="[
                  'px-3 py-1.5 rounded-full text-xs font-medium border transition-colors',
                  task.status === s
                    ? s === 'todo' ? 'bg-neutral-100 border-neutral-300 text-neutral-700'
                      : s === 'in_progress' ? 'bg-primary-100 border-primary-300 text-primary-700'
                      : 'bg-success-100 border-success-300 text-success-700'
                    : 'border-neutral-200 text-neutral-500 hover:bg-neutral-50',
                ]"
              >
                {{ s === 'todo' ? (uiStore.locale === 'ar' ? 'للتنفيذ' : 'To Do')
                  : s === 'in_progress' ? (uiStore.locale === 'ar' ? 'قيد التنفيذ' : 'In Progress')
                  : (uiStore.locale === 'ar' ? 'مكتمل' : 'Done') }}
              </button>
            </div>
          </div>

          <!-- Priority -->
          <div class="card">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase mb-3">
              {{ uiStore.locale === 'ar' ? 'الأولوية' : 'Priority' }}
            </h4>
            <PriorityBadge :priority="task.priority" />
          </div>

          <!-- Assignee -->
          <div class="card">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase mb-3">
              {{ uiStore.locale === 'ar' ? 'المسؤول' : 'Assignee' }}
            </h4>
            <div v-if="task.assignee" class="flex items-center gap-2">
              <MemberAvatar :user="task.assignee" size="sm" />
              <span class="text-sm text-neutral-700">{{ task.assignee.name }}</span>
            </div>
            <p v-else class="text-sm text-neutral-400 italic">
              {{ uiStore.locale === 'ar' ? 'غير معين' : 'Unassigned' }}
            </p>
          </div>

          <!-- Due date -->
          <div class="card">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase mb-3">
              {{ uiStore.locale === 'ar' ? 'تاريخ الاستحقاق' : 'Due Date' }}
            </h4>
            <p v-if="task.due_date" :class="['text-sm', task.is_overdue ? 'text-error-600 font-medium' : 'text-neutral-700']">
              {{ formatDate(task.due_date) }}
              <span v-if="task.is_overdue" class="mr-1">({{ uiStore.locale === 'ar' ? 'متأخرة' : 'Overdue' }})</span>
            </p>
            <p v-else class="text-sm text-neutral-400 italic">
              {{ uiStore.locale === 'ar' ? 'بدون تاريخ' : 'No due date' }}
            </p>
          </div>

          <!-- Time tracking -->
          <div class="card">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase mb-3">
              {{ uiStore.locale === 'ar' ? 'الوقت' : 'Time' }}
            </h4>
            <div class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-neutral-500">{{ uiStore.locale === 'ar' ? 'الوقت المقدر' : 'Estimated' }}</span>
                <span class="text-neutral-700">
                  {{ task.estimated_minutes ? `${Math.floor(task.estimated_minutes / 60)}h ${task.estimated_minutes % 60}m` : '-' }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-neutral-500">{{ uiStore.locale === 'ar' ? 'الوقت المسجل' : 'Logged' }}</span>
                <span class="text-primary-600 font-medium">
                  {{ task.logged_minutes ? `${Math.floor(task.logged_minutes / 60)}h ${task.logged_minutes % 60}m` : '-' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="task.tags?.length" class="card">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase mb-3">
              {{ uiStore.locale === 'ar' ? 'الوسوم' : 'Tags' }}
            </h4>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="tag in task.tags"
                :key="tag.id"
                class="badge text-xs"
                :style="{ backgroundColor: tag.color + '20', color: tag.color }"
              >
                {{ tag.name }}
              </span>
            </div>
          </div>

          <!-- Creator & dates -->
          <div class="card">
            <div class="space-y-2 text-xs text-neutral-500">
              <div class="flex justify-between">
                <span>{{ uiStore.locale === 'ar' ? 'المنشئ' : 'Creator' }}</span>
                <span>{{ task.creator?.name || '-' }}</span>
              </div>
              <div class="flex justify-between">
                <span>{{ uiStore.locale === 'ar' ? 'تاريخ الإنشاء' : 'Created' }}</span>
                <span>{{ formatDate(task.created_at) }}</span>
              </div>
              <div v-if="task.updated_at" class="flex justify-between">
                <span>{{ uiStore.locale === 'ar' ? 'آخر تحديث' : 'Updated' }}</span>
                <span>{{ formatDate(task.updated_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Project link -->
          <div v-if="task.project_id" class="card">
            <router-link
              :to="`/projects/${task.project_id}`"
              class="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-2"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4" aria-hidden="true">
                <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" />
              </svg>
              {{ task.project_name || (uiStore.locale === 'ar' ? 'عرض المشروع' : 'View Project') }}
            </router-link>
          </div>
        </div>
      </div>

      <!-- Delete confirmation -->
      <ConfirmDialog
        :visible="showDelete"
        :title="uiStore.locale === 'ar' ? 'حذف المهمة' : 'Delete Task'"
        :message="uiStore.locale === 'ar' ? 'هل أنت متأكد من حذف هذه المهمة؟' : 'Are you sure you want to delete this task?'"
        :loading="taskStore.saving"
        @confirm="handleDelete"
        @cancel="showDelete = false"
        @update:visible="showDelete = $event"
      />
    </template>
  </div>
</template>
