<script setup>
import { ref, computed, onMounted } from 'vue';
import { useUiStore } from '@/stores/uiStore';

const props = defineProps({
  visible: { type: Boolean, default: false },
  task: { type: Object, default: null },
  projectId: { type: String, default: null },
  members: { type: Array, default: () => [] },
  tags: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
});

const emit = defineEmits(['save', 'close', 'update:visible']);

const uiStore = useUiStore();
const isEditing = computed(() => !!props.task);

const form = ref({
  title: '',
  description: '',
  priority: 'medium',
  assignee_id: '',
  due_date: '',
  estimated_minutes: '',
  tags: [],
  ...(props.task ? {
    title: props.task.title,
    description: props.task.description || '',
    priority: props.task.priority || 'medium',
    assignee_id: props.task.assignee?.id || '',
    due_date: props.task.due_date || '',
    estimated_minutes: props.task.estimated_minutes || '',
    tags: props.task.tags?.map((t) => t.id) || [],
  } : {}),
});

const errors = ref({});
const dialogRef = ref(null);

const validate = () => {
  errors.value = {};
  if (!form.value.title.trim()) {
    errors.value.title = uiStore.locale === 'ar' ? 'عنوان المهمة مطلوب' : 'Title is required';
  }
  return Object.keys(errors.value).length === 0;
};

const submit = () => {
  if (!validate()) return;
  const payload = {
    ...(props.task ? {} : { project_id: props.projectId }),
    ...form.value,
    estimated_minutes: form.value.estimated_minutes ? Number(form.value.estimated_minutes) : null,
  };
  emit('save', payload);
};

const close = () => {
  if (!props.saving) {
    emit('update:visible', false);
    emit('close');
  }
};

const onBackdrop = (e) => {
  if (e.target === dialogRef.value) close();
};

const priorityOptions = [
  { value: 'urgent', label: { ar: 'عاجل', en: 'Urgent' } },
  { value: 'high', label: { ar: 'عالية', en: 'High' } },
  { value: 'medium', label: { ar: 'متوسطة', en: 'Medium' } },
  { value: 'low', label: { ar: 'منخفضة', en: 'Low' } },
];
</script>

<template>
  <Teleport to="body">
    <transition name="modal">
      <div
        v-if="visible"
        ref="dialogRef"
        class="modal-backdrop"
        @click="onBackdrop"
        @keydown.escape="close"
        role="dialog"
        aria-modal="true"
        :aria-label="isEditing ? 'Edit task' : 'Create task'"
      >
        <div class="modal-content p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-neutral-900">
              {{ isEditing ? (uiStore.locale === 'ar' ? 'تعديل المهمة' : 'Edit Task') : (uiStore.locale === 'ar' ? 'مهمة جديدة' : 'New Task') }}
            </h2>
            <button @click="close" class="btn-icon btn-ghost" :aria-label="uiStore.locale === 'ar' ? 'إغلاق' : 'Close'">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <!-- Title -->
            <div>
              <label class="input-label" for="task-title">{{ uiStore.locale === 'ar' ? 'عنوان المهمة' : 'Task Title' }} <span class="text-error-500">*</span></label>
              <input
                id="task-title"
                v-model="form.title"
                :class="['input', errors.title ? 'input-error' : '']"
                :placeholder="uiStore.locale === 'ar' ? 'أدخل عنوان المهمة' : 'Enter task title'"
                :aria-invalid="!!errors.title"
                :aria-describedby="errors.title ? 'title-error' : undefined"
              />
              <p v-if="errors.title" id="title-error" class="input-error-text">{{ errors.title }}</p>
            </div>

            <!-- Description -->
            <div>
              <label class="input-label" for="task-desc">{{ uiStore.locale === 'ar' ? 'الوصف' : 'Description' }}</label>
              <textarea
                id="task-desc"
                v-model="form.description"
                class="input min-h-[80px] resize-y"
                :placeholder="uiStore.locale === 'ar' ? 'أضف وصفاً...' : 'Add description...'"
                rows="3"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Priority -->
              <div>
                <label class="input-label" for="task-priority">{{ uiStore.locale === 'ar' ? 'الأولوية' : 'Priority' }}</label>
                <select id="task-priority" v-model="form.priority" class="input">
                  <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label[uiStore.locale] || opt.label.en }}
                  </option>
                </select>
              </div>

              <!-- Assignee -->
              <div>
                <label class="input-label" for="task-assignee">{{ uiStore.locale === 'ar' ? 'المسؤول' : 'Assignee' }}</label>
                <select id="task-assignee" v-model="form.assignee_id" class="input">
                  <option value="">{{ uiStore.locale === 'ar' ? 'اختر...' : 'Select...' }}</option>
                  <option v-for="m in members" :key="m.id" :value="m.id">
                    {{ m.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Due date -->
              <div>
                <label class="input-label" for="task-due">{{ uiStore.locale === 'ar' ? 'تاريخ الاستحقاق' : 'Due Date' }}</label>
                <input id="task-due" v-model="form.due_date" type="date" class="input" />
              </div>

              <!-- Estimated time -->
              <div>
                <label class="input-label" for="task-est">{{ uiStore.locale === 'ar' ? 'الوقت المقدر (دقائق)' : 'Est. Time (min)' }}</label>
                <input id="task-est" v-model="form.estimated_minutes" type="number" min="0" step="15" class="input" :placeholder="uiStore.locale === 'ar' ? 'مثلاً: 480' : 'e.g. 480'" />
              </div>
            </div>

            <!-- Tags -->
            <div v-if="tags.length">
              <label class="input-label">{{ uiStore.locale === 'ar' ? 'الوسوم' : 'Tags' }}</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="tag in tags"
                  :key="tag.id"
                  type="button"
                  @click="form.tags.includes(tag.id) ? form.tags = form.tags.filter(t => t !== tag.id) : form.tags.push(tag.id)"
                  :class="[
                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border transition-colors',
                    form.tags.includes(tag.id) ? 'border-transparent' : 'border-neutral-200 text-neutral-600',
                  ]"
                  :style="form.tags.includes(tag.id) ? { backgroundColor: tag.color + '20', color: tag.color, borderColor: tag.color } : {}"
                >
                  {{ tag.name }}
                </button>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200">
              <button type="button" @click="close" class="btn-secondary" :disabled="saving">
                {{ uiStore.locale === 'ar' ? 'إلغاء' : 'Cancel' }}
              </button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <svg v-if="saving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                  <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
                </svg>
                <span>{{ saving ? (uiStore.locale === 'ar' ? 'جاري الحفظ...' : 'Saving...') : (uiStore.locale === 'ar' ? 'حفظ' : 'Save') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
