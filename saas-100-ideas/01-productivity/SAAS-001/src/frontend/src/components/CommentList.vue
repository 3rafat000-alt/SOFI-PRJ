<script setup>
import { ref, onMounted } from 'vue';
import { useTaskStore } from '@/stores/taskStore';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import MemberAvatar from './MemberAvatar.vue';

const props = defineProps({
  taskId: { type: String, required: true },
});

const taskStore = useTaskStore();
const authStore = useAuthStore();
const uiStore = useUiStore();

const comments = ref([]);
const loading = ref(false);
const newComment = ref('');
const saving = ref(false);

onMounted(async () => {
  await loadComments();
});

const loadComments = async () => {
  loading.value = true;
  comments.value = await taskStore.fetchComments(props.taskId);
  loading.value = false;
};

const submitComment = async () => {
  if (!newComment.value.trim()) return;
  saving.value = true;
  const result = await taskStore.addComment(props.taskId, newComment.value);
  if (result) {
    comments.value.push(result);
    newComment.value = '';
  }
  saving.value = false;
};

const deleteComment = async (commentId) => {
  const ok = await taskStore.deleteComment(commentId);
  if (ok) {
    comments.value = comments.value.filter((c) => c.id !== commentId);
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString(uiStore.locale === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<template>
  <div class="space-y-4">
    <h3 class="text-sm font-semibold text-neutral-900">
      {{ uiStore.locale === 'ar' ? 'التعليقات' : 'Comments' }} ({{ comments.length }})
    </h3>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-4">
      <LoadingSpinner size="sm" :label="uiStore.locale === 'ar' ? 'جاري تحميل التعليقات...' : 'Loading comments...'" />
    </div>

    <!-- Comments list -->
    <div v-else-if="comments.length === 0" class="text-center py-6 text-sm text-neutral-400">
      {{ uiStore.locale === 'ar' ? 'لا توجد تعليقات بعد' : 'No comments yet' }}
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="comment in comments"
        :key="comment.id"
        class="flex gap-3 p-3 bg-neutral-50 rounded-8"
      >
        <MemberAvatar :user="comment.user" size="sm" />
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <span class="text-sm font-medium text-neutral-900">{{ comment.user?.name }}</span>
            <div class="flex items-center gap-2">
              <span class="text-xs text-neutral-400">{{ formatDate(comment.created_at) }}</span>
              <button
                v-if="comment.can_delete || authStore.userId === comment.user?.id"
                @click="deleteComment(comment.id)"
                class="text-xs text-neutral-400 hover:text-error-500 transition-colors"
                :aria-label="uiStore.locale === 'ar' ? 'حذف التعليق' : 'Delete comment'"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5">
                  <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
            </div>
          </div>
          <p class="text-sm text-neutral-700 mt-1 whitespace-pre-wrap">{{ comment.body }}</p>
        </div>
      </div>
    </div>

    <!-- New comment form -->
    <div class="flex gap-3 pt-3 border-t border-neutral-200">
      <MemberAvatar :user="authStore.user" size="sm" />
      <div class="flex-1">
        <textarea
          v-model="newComment"
          class="input min-h-[60px] resize-none text-sm"
          rows="2"
          :placeholder="uiStore.locale === 'ar' ? 'أضف تعليقاً...' : 'Add a comment...'"
          @keydown.ctrl.enter="submitComment"
          @keydown.meta.enter="submitComment"
        />
        <div class="flex items-center justify-between mt-1">
          <span class="text-xs text-neutral-400">Ctrl+Enter {{ uiStore.locale === 'ar' ? 'لإرسال' : 'to send' }}</span>
          <button
            @click="submitComment"
            class="btn-primary btn-sm"
            :disabled="!newComment.trim() || saving"
          >
            <svg v-if="saving" class="w-3 h-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
              <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
            </svg>
            <span>{{ uiStore.locale === 'ar' ? 'إرسال' : 'Send' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
