<script setup>
import { ref, computed } from 'vue';
import { useUiStore } from '@/stores/uiStore';

const props = defineProps({
  visible: { type: Boolean, default: false },
  project: { type: Object, default: null },
  saving: { type: Boolean, default: false },
});

const emit = defineEmits(['save', 'close', 'update:visible']);

const uiStore = useUiStore();
const isEditing = computed(() => !!props.project);

const form = ref({
  name: '',
  description: '',
  color: '#4F46E5',
  start_date: '',
  end_date: '',
  ...(props.project ? {
    name: props.project.name,
    description: props.project.description || '',
    color: props.project.color || '#4F46E5',
    start_date: props.project.start_date || '',
    end_date: props.project.end_date || '',
  } : {}),
});

const errors = ref({});
const dialogRef = ref(null);

const colorOptions = ['#2563EB', '#7C3AED', '#10B981', '#F59E0B', '#EF4444', '#4F46E5', '#EC4899', '#06B6D4'];

const validate = () => {
  errors.value = {};
  if (!form.value.name.trim()) {
    errors.value.name = uiStore.locale === 'ar' ? 'اسم المشروع مطلوب' : 'Project name is required';
  }
  return Object.keys(errors.value).length === 0;
};

const submit = () => {
  if (!validate()) return;
  emit('save', { ...form.value });
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
        :aria-label="isEditing ? 'Edit project' : 'Create project'"
      >
        <div class="modal-content p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-neutral-900">
              {{ isEditing ? (uiStore.locale === 'ar' ? 'تعديل المشروع' : 'Edit Project') : (uiStore.locale === 'ar' ? 'مشروع جديد' : 'New Project') }}
            </h2>
            <button @click="close" class="btn-icon btn-ghost" :aria-label="uiStore.locale === 'ar' ? 'إغلاق' : 'Close'">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="input-label" for="project-name">{{ uiStore.locale === 'ar' ? 'اسم المشروع' : 'Project Name' }} <span class="text-error-500">*</span></label>
              <input
                id="project-name"
                v-model="form.name"
                :class="['input', errors.name ? 'input-error' : '']"
                :placeholder="uiStore.locale === 'ar' ? 'أدخل اسم المشروع' : 'Enter project name'"
                :aria-invalid="!!errors.name"
                :aria-describedby="errors.name ? 'name-error' : undefined"
              />
              <p v-if="errors.name" id="name-error" class="input-error-text">{{ errors.name }}</p>
            </div>

            <div>
              <label class="input-label" for="project-desc">{{ uiStore.locale === 'ar' ? 'الوصف' : 'Description' }}</label>
              <textarea
                id="project-desc"
                v-model="form.description"
                class="input min-h-[80px] resize-y"
                rows="3"
                :placeholder="uiStore.locale === 'ar' ? 'أضف وصفاً للمشروع...' : 'Add project description...'"
              />
            </div>

            <!-- Color picker -->
            <div>
              <label class="input-label">{{ uiStore.locale === 'ar' ? 'لون المشروع' : 'Project Color' }}</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="color in colorOptions"
                  :key="color"
                  type="button"
                  @click="form.color = color"
                  :class="[
                    'w-8 h-8 rounded-full border-2 transition-all',
                    form.color === color ? 'border-neutral-900 scale-110' : 'border-transparent',
                  ]"
                  :style="{ backgroundColor: color }"
                  :aria-label="`Color: ${color}`"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="input-label" for="project-start">{{ uiStore.locale === 'ar' ? 'تاريخ البداية' : 'Start Date' }}</label>
                <input id="project-start" v-model="form.start_date" type="date" class="input" />
              </div>
              <div>
                <label class="input-label" for="project-end">{{ uiStore.locale === 'ar' ? 'تاريخ النهاية' : 'End Date' }}</label>
                <input id="project-end" v-model="form.end_date" type="date" class="input" />
              </div>
            </div>

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
