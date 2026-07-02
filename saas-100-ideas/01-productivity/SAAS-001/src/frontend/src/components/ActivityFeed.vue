<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import MemberAvatar from './MemberAvatar.vue';

const authStore = useAuthStore();
const uiStore = useUiStore();

const activities = ref([]);
const loading = ref(false);

onMounted(async () => {
  await fetchActivity();
});

const fetchActivity = async () => {
  if (!authStore.currentWorkspaceId) return;
  loading.value = true;
  try {
    const { data } = await api.get('/dashboard/activity', {
      params: { workspace_id: authStore.currentWorkspaceId, limit: 20 },
    });
    activities.value = data.data;
  } catch {
    // silently fail
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const now = new Date();
  const diff = now - d;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return uiStore.locale === 'ar' ? 'الآن' : 'Just now';
  if (minutes < 60) return uiStore.locale === 'ar' ? `منذ ${minutes} دقيقة` : `${minutes}m ago`;
  if (hours < 24) return uiStore.locale === 'ar' ? `منذ ${hours} ساعة` : `${hours}h ago`;
  if (days < 7) return uiStore.locale === 'ar' ? `منذ ${days} يوم` : `${days}d ago`;
  return d.toLocaleDateString(uiStore.locale === 'ar' ? 'ar-SA' : 'en-US', { month: 'short', day: 'numeric' });
};

const getActivityIcon = (type) => {
  const icons = {
    task_created: 'check-circle',
    task_updated: 'edit',
    task_completed: 'check-square',
    comment_added: 'message-circle',
    member_joined: 'user-plus',
    task_moved: 'arrow-right',
  };
  return icons[type] || 'circle';
};
</script>

<template>
  <div class="space-y-1">
    <h3 class="text-sm font-medium text-neutral-500 mb-3">
      {{ uiStore.locale === 'ar' ? 'النشاط الأخير' : 'Recent Activity' }}
    </h3>

    <LoadingSpinner v-if="loading" size="sm" :label="uiStore.locale === 'ar' ? '' : ''" />

    <div v-else-if="activities.length === 0" class="text-center py-8 text-sm text-neutral-400">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-12 h-12 mx-auto mb-2 text-neutral-200" aria-hidden="true">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
      </svg>
      {{ uiStore.locale === 'ar' ? 'لا يوجد نشاط حديث' : 'No recent activity' }}
    </div>

    <div v-else class="space-y-0">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="flex items-start gap-3 py-3 border-b border-neutral-100 last:border-0"
      >
        <MemberAvatar :user="activity.user" size="sm" />
        <div class="flex-1 min-w-0">
          <p class="text-sm text-neutral-700">
            <span class="font-medium text-neutral-900">{{ activity.user?.name }}</span>
            <span class="mx-1">{{ activity.description }}</span>
          </p>
          <div class="flex items-center gap-2 mt-0.5">
            <span v-if="activity.project_name" class="text-xs text-primary-500">{{ activity.project_name }}</span>
            <span class="text-xs text-neutral-400">{{ formatDate(activity.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
