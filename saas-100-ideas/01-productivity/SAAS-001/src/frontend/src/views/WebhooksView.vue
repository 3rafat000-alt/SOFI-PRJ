<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import EmptyState from '@/components/EmptyState.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

const authStore = useAuthStore();
const uiStore = useUiStore();

const webhooks = ref([]);
const loading = ref(false);
const showForm = ref(false);
const testingId = ref(null);

const form = ref({
  url: '',
  events: [],
  secret: '',
});
const saving = ref(false);
const showDelete = ref(false);
const deletingId = ref(null);

const eventOptions = [
  { value: 'task.created', label: { ar: 'إنشاء مهمة', en: 'Task Created' } },
  { value: 'task.updated', label: { ar: 'تحديث مهمة', en: 'Task Updated' } },
  { value: 'task.deleted', label: { ar: 'حذف مهمة', en: 'Task Deleted' } },
  { value: 'time_entry.created', label: { ar: 'بدء مؤقت', en: 'Time Entry Created' } },
  { value: 'time_entry.updated', label: { ar: 'إيقاف مؤقت', en: 'Time Entry Updated' } },
  { value: 'project.created', label: { ar: 'إنشاء مشروع', en: 'Project Created' } },
  { value: 'project.updated', label: { ar: 'تحديث مشروع', en: 'Project Updated' } },
  { value: 'project.deleted', label: { ar: 'حذف مشروع', en: 'Project Deleted' } },
  { value: 'comment.created', label: { ar: 'إضافة تعليق', en: 'Comment Created' } },
  { value: 'member.joined', label: { ar: 'انضمام عضو', en: 'Member Joined' } },
];

onMounted(async () => {
  await fetchWebhooks();
});

const fetchWebhooks = async () => {
  loading.value = true;
  try {
    const { data } = await api.get('/webhooks', {
      params: { workspace_id: authStore.currentWorkspaceId },
    });
    webhooks.value = data.data || [];
  } catch {
    // silent
  } finally {
    loading.value = false;
  }
};

const toggleEvent = (event) => {
  const idx = form.value.events.indexOf(event);
  if (idx === -1) {
    form.value.events.push(event);
  } else {
    form.value.events.splice(idx, 1);
  }
};

const createWebhook = async () => {
  if (!form.value.url.trim()) return;
  saving.value = true;
  try {
    const { data } = await api.post('/webhooks', {
      workspace_id: authStore.currentWorkspaceId,
      url: form.value.url,
      events: form.value.events,
      secret: form.value.secret || undefined,
    });
    webhooks.value.push(data.data);
    showForm.value = false;
    form.value = { url: '', events: [], secret: '' };
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم إنشاء الويبهوك' : 'Webhook created',
      'success',
    );
  } catch (err) {
    uiStore.addToast(err.message || 'Failed to create webhook', 'error');
  } finally {
    saving.value = false;
  }
};

const confirmDelete = (id) => {
  deletingId.value = id;
  showDelete.value = true;
};

const deleteWebhook = async () => {
  if (!deletingId.value) return;
  try {
    await api.delete(`/webhooks/${deletingId.value}`);
    webhooks.value = webhooks.value.filter((w) => w.id !== deletingId.value);
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم حذف الويبهوك' : 'Webhook deleted',
      'success',
    );
  } catch {
    // silent
  }
  showDelete.value = false;
  deletingId.value = null;
};

const testWebhook = async (id) => {
  testingId.value = id;
  try {
    const { data } = await api.post(`/webhooks/${id}/test`);
    uiStore.addToast(
      uiStore.locale === 'ar' ? `تم الاختبار: ${data.data.status_code}` : `Tested: ${data.data.status_code}`,
      data.data.status_code < 300 ? 'success' : 'error',
    );
  } catch {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'فشل اختبار الويبهوك' : 'Webhook test failed',
      'error',
    );
  } finally {
    testingId.value = null;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString(uiStore.locale === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  });
};
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'الويبهوك' : 'Webhooks' }}</h1>
      <button @click="showForm = true" class="btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
          <line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        {{ uiStore.locale === 'ar' ? 'ويبهوك جديد' : 'New Webhook' }}
      </button>
    </div>

    <!-- Settings sub-nav -->
    <div class="flex items-center gap-1 border-b border-neutral-200 mb-6">
      <router-link to="/settings" class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-700 -mb-px">
        {{ uiStore.locale === 'ar' ? 'عام' : 'General' }}
      </router-link>
      <router-link to="/settings/members" class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-700 -mb-px">
        {{ uiStore.locale === 'ar' ? 'فريق العمل' : 'Team' }}
      </router-link>
      <router-link to="/settings/webhooks" class="px-4 py-3 text-sm font-medium border-b-2 border-primary-600 text-primary-600 -mb-px">
        {{ uiStore.locale === 'ar' ? 'الويبهوك' : 'Webhooks' }}
      </router-link>
    </div>

    <div class="max-w-3xl space-y-6">
      <!-- Create form -->
      <div v-if="showForm" class="card animate-slide-down">
        <h2 class="text-sm font-semibold text-neutral-900 mb-4">
          {{ uiStore.locale === 'ar' ? 'ويبهوك جديد' : 'New Webhook' }}
        </h2>
        <form @submit.prevent="createWebhook" class="space-y-4">
          <div>
            <label class="input-label" for="webhook-url">{{ uiStore.locale === 'ar' ? 'رابط النهاية' : 'Endpoint URL' }}</label>
            <input id="webhook-url" v-model="form.url" type="url" class="input" placeholder="https://hooks.example.com/..." required />
          </div>
          <div>
            <label class="input-label">{{ uiStore.locale === 'ar' ? 'الأحداث' : 'Events' }}</label>
            <div class="grid grid-cols-2 gap-2">
              <label
                v-for="event in eventOptions"
                :key="event.value"
                class="flex items-center gap-2 p-2 rounded-8 hover:bg-neutral-50 cursor-pointer text-sm"
              >
                <input
                  type="checkbox"
                  :checked="form.events.includes(event.value)"
                  @change="toggleEvent(event.value)"
                  class="rounded-4 border-neutral-300 text-primary-600 focus:ring-primary-500"
                />
                {{ event.label[uiStore.locale] || event.label.en }}
              </label>
            </div>
          </div>
          <div>
            <label class="input-label" for="webhook-secret">{{ uiStore.locale === 'ar' ? 'المفتاح السري (اختياري)' : 'Secret Key (optional)' }}</label>
            <input id="webhook-secret" v-model="form.secret" type="text" class="input" :placeholder="uiStore.locale === 'ar' ? 'مفتاح التوقيع' : 'Signing secret'" />
          </div>
          <div class="flex items-center justify-end gap-2">
            <button type="button" @click="showForm = false" class="btn-secondary btn-sm">{{ uiStore.locale === 'ar' ? 'إلغاء' : 'Cancel' }}</button>
            <button type="submit" class="btn-primary btn-sm" :disabled="saving || !form.events.length">
              <svg v-if="saving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
              </svg>
              <span>{{ uiStore.locale === 'ar' ? 'إنشاء' : 'Create' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Loading -->
      <LoadingSpinner v-if="loading" />

      <!-- Empty -->
      <EmptyState
        v-else-if="webhooks.length === 0 && !showForm"
        :title="uiStore.locale === 'ar' ? 'لا توجد ويبهوك' : 'No webhooks'"
        :description="uiStore.locale === 'ar' ? 'أضف ويبهوك لتلقي التحديثات في الوقت الفعلي' : 'Add a webhook to receive real-time updates'"
        :action="{ label: uiStore.locale === 'ar' ? 'إضافة ويبهوك' : 'Add Webhook', onClick: () => showForm = true }"
      />

      <!-- Webhooks list -->
      <div v-else class="space-y-3">
        <div
          v-for="wh in webhooks"
          :key="wh.id"
          class="card"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span :class="wh.is_active ? 'badge-success' : 'badge-neutral'">
                  {{ wh.is_active ? (uiStore.locale === 'ar' ? 'نشط' : 'Active') : (uiStore.locale === 'ar' ? 'غير نشط' : 'Inactive') }}
                </span>
              </div>
              <p class="text-sm font-mono text-neutral-700 truncate" dir="ltr">{{ wh.url }}</p>
              <div class="flex flex-wrap gap-1 mt-2">
                <span
                  v-for="event in wh.events"
                  :key="event"
                  class="badge-primary text-2xs"
                >
                  {{ event }}
                </span>
              </div>
              <div class="flex items-center gap-4 mt-2 text-xs text-neutral-500">
                <span v-if="wh.last_sent_at">
                  {{ uiStore.locale === 'ar' ? 'آخر إرسال' : 'Last sent' }}: {{ formatDate(wh.last_sent_at) }}
                </span>
                <span v-if="wh.last_status_code" :class="wh.last_status_code < 300 ? 'text-success-600' : 'text-error-600'">
                  {{ uiStore.locale === 'ar' ? 'آخر حالة' : 'Last status' }}: {{ wh.last_status_code }}
                </span>
              </div>
            </div>
            <div class="flex items-center gap-2 mr-4">
              <button
                @click="testWebhook(wh.id)"
                class="btn-secondary btn-sm"
                :disabled="testingId === wh.id"
              >
                <svg v-if="testingId === wh.id" class="w-3 h-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                  <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
                </svg>
                <span>{{ uiStore.locale === 'ar' ? 'اختبار' : 'Test' }}</span>
              </button>
              <button
                @click="confirmDelete(wh.id)"
                class="btn-icon btn-ghost btn-sm text-neutral-400 hover:text-error-500"
                :aria-label="uiStore.locale === 'ar' ? 'حذف' : 'Delete'"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                  <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :visible="showDelete"
      :title="uiStore.locale === 'ar' ? 'حذف الويبهوك' : 'Delete Webhook'"
      :message="uiStore.locale === 'ar' ? 'هل أنت متأكد من حذف هذا الويبهوك؟' : 'Are you sure you want to delete this webhook?'"
      @confirm="deleteWebhook"
      @cancel="showDelete = false; deletingId = null"
      @update:visible="showDelete = $event"
    />
  </div>
</template>
