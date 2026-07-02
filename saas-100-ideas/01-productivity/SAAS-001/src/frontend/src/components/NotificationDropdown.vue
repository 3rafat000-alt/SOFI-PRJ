<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from '@/stores/notificationStore';
import { useUiStore } from '@/stores/uiStore';
import MemberAvatar from './MemberAvatar.vue';

const emit = defineEmits(['close']);
const router = useRouter();
const notificationStore = useNotificationStore();
const uiStore = useUiStore();

const notifications = computed(() => notificationStore.notifications);
const unreadCount = computed(() => notificationStore.unreadCount);

const handleClick = (notif) => {
  if (!notif.read_at) {
    notificationStore.markRead(notif.id);
  }
  // Navigate based on notification type
  if (notif.data?.task_id) {
    router.push(`/tasks/${notif.data.task_id}`);
  } else if (notif.data?.project_id) {
    router.push(`/projects/${notif.data.project_id}`);
  }
  emit('close');
};

const markAllRead = () => {
  notificationStore.markAllRead();
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const now = new Date();
  const diff = now - d;
  const hours = Math.floor(diff / 3600000);
  if (hours < 1) return uiStore.locale === 'ar' ? 'الآن' : 'Just now';
  if (hours < 24) return uiStore.locale === 'ar' ? `منذ ${hours} ساعة` : `${hours}h ago`;
  return d.toLocaleDateString();
};
</script>

<template>
  <div class="dropdown-content left-0 right-auto ltr:left-auto ltr:right-0 w-80" role="menu" aria-label="Notifications">
    <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100">
      <h3 class="text-sm font-semibold text-neutral-900">
        {{ uiStore.locale === 'ar' ? 'الإشعارات' : 'Notifications' }}
      </h3>
      <button
        v-if="unreadCount > 0"
        @click="markAllRead"
        class="text-xs text-primary-600 hover:text-primary-700 font-medium"
      >
        {{ uiStore.locale === 'ar' ? 'تحديد الكل كمقروء' : 'Mark all read' }}
      </button>
    </div>

    <div class="max-h-80 overflow-y-auto">
      <div v-if="notifications.length === 0" class="py-8 text-center text-sm text-neutral-400">
        {{ uiStore.locale === 'ar' ? 'لا توجد إشعارات' : 'No notifications' }}
      </div>

      <button
        v-for="notif in notifications.slice(0, 10)"
        :key="notif.id"
        @click="handleClick(notif)"
        :class="[
          'w-full text-right px-4 py-3 hover:bg-neutral-50 border-b border-neutral-100 last:border-0 transition-colors',
          !notif.read_at ? 'bg-primary-50/50' : '',
        ]"
        role="menuitem"
      >
        <div class="flex gap-3">
          <div class="flex-shrink-0 mt-0.5">
            <svg v-if="notif.type === 'task_assigned'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-primary-500" aria-hidden="true">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><line x1="19" y1="8" x2="19" y2="14" /><line x1="22" y1="11" x2="16" y2="11" />
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-neutral-400" aria-hidden="true">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
          </div>
          <div class="flex-1 min-w-0 text-right">
            <p class="text-sm text-neutral-900 font-medium">{{ notif.title }}</p>
            <p class="text-xs text-neutral-600 mt-0.5 line-clamp-2">{{ notif.body }}</p>
            <p class="text-2xs text-neutral-400 mt-1">{{ formatDate(notif.created_at) }}</p>
          </div>
          <div v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-primary-500 flex-shrink-0 mt-2" aria-hidden="true" />
        </div>
      </button>
    </div>

    <button
      @click="router.push('/settings'); emit('close')"
      class="w-full text-center py-2.5 text-xs text-primary-600 hover:text-primary-700 font-medium border-t border-neutral-100"
    >
      {{ uiStore.locale === 'ar' ? 'عرض الكل' : 'View All' }}
    </button>
  </div>
</template>
