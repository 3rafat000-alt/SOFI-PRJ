<script setup>
import { ref, onMounted } from 'vue';
import { useTaskStore } from '@/stores/taskStore';
import { useUiStore } from '@/stores/uiStore';

const props = defineProps({
  taskId: { type: String, required: true },
  attachments: { type: Array, default: () => [] },
});

const taskStore = useTaskStore();
const uiStore = useUiStore();

const uploading = ref(false);

const handleUpload = async (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'الملف كبير جداً. الحد الأقصى 10 ميجابايت' : 'File too large. Max 10MB',
      'error',
    );
    return;
  }

  uploading.value = true;
  const result = await taskStore.uploadAttachment(props.taskId, file);
  if (result) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم رفع الملف' : 'File uploaded',
      'success',
    );
  }
  uploading.value = false;
  e.target.value = '';
};

const deleteAttachment = async (attachmentId) => {
  const ok = await taskStore.deleteAttachment(attachmentId);
  if (ok) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم حذف المرفق' : 'Attachment deleted',
      'success',
    );
  }
};

const formatSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const isImage = (mimeType) => mimeType?.startsWith('image/');
</script>

<template>
  <div class="space-y-3">
    <h3 class="text-sm font-semibold text-neutral-900">
      {{ uiStore.locale === 'ar' ? 'المرفقات' : 'Attachments' }} ({{ attachments.length }})
    </h3>

    <!-- Attachment list -->
    <div v-if="attachments.length === 0" class="text-center py-4 text-sm text-neutral-400">
      {{ uiStore.locale === 'ar' ? 'لا توجد مرفقات' : 'No attachments' }}
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-2">
      <div
        v-for="file in attachments"
        :key="file.id"
        class="relative group rounded-8 border border-neutral-200 overflow-hidden bg-neutral-50"
      >
        <!-- Preview -->
        <a
          v-if="isImage(file.mime_type)"
          :href="file.url"
          target="_blank"
          rel="noopener noreferrer"
          class="block aspect-square"
        >
          <img
            :src="file.thumbnail_url || file.url"
            :alt="file.name"
            class="w-full h-full object-cover"
            loading="lazy"
          />
        </a>
        <div v-else class="aspect-square flex items-center justify-center">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-neutral-400">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2 14 8 20 8" />
          </svg>
        </div>

        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
          <a
            :href="file.url"
            target="_blank"
            rel="noopener noreferrer"
            class="btn-icon bg-white/90 rounded-full"
            :aria-label="uiStore.locale === 'ar' ? 'تحميل' : 'Download'"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="7 10 12 15 17 10" /><line x1="12" y1="15" x2="12" y2="3" />
            </svg>
          </a>
          <button
            @click="deleteAttachment(file.id)"
            class="btn-icon bg-white/90 rounded-full text-error-600"
            :aria-label="uiStore.locale === 'ar' ? 'حذف' : 'Delete'"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
              <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            </svg>
          </button>
        </div>

        <!-- Info -->
        <div class="px-2 py-1.5">
          <p class="text-xs text-neutral-700 truncate" :title="file.name">{{ file.name }}</p>
          <p class="text-2xs text-neutral-400">{{ formatSize(file.size) }}</p>
        </div>
      </div>
    </div>

    <!-- Upload button -->
    <div class="mt-2">
      <label class="btn-secondary btn-sm cursor-pointer inline-flex items-center gap-2">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" />
        </svg>
        <span>{{ uploading ? (uiStore.locale === 'ar' ? 'جاري الرفع...' : 'Uploading...') : (uiStore.locale === 'ar' ? 'إضافة ملف' : 'Upload File') }}</span>
        <input
          type="file"
          class="hidden"
          @change="handleUpload"
          :disabled="uploading"
          accept=".jpg,.jpeg,.png,.gif,.svg,.pdf,.doc,.docx,.xls,.xlsx,.zip"
        />
      </label>
    </div>
  </div>
</template>
