<script setup>
import { computed, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/uiStore';
import { useAuthStore } from '@/stores/authStore';

const router = useRouter();
const route = useRoute();
const uiStore = useUiStore();
const authStore = useAuthStore();
const innerWidth = ref(window.innerWidth);

window.addEventListener('resize', () => {
  innerWidth.value = window.innerWidth;
});

const navItems = computed(() => [
  { name: 'Dashboard', path: '/dashboard', icon: 'LayoutDashboard', label: { ar: 'لوحة التحكم', en: 'Dashboard' } },
  { name: 'Projects', path: '/projects', icon: 'FolderKanban', label: { ar: 'المشاريع', en: 'Projects' } },
  { name: 'Tasks', path: '/tasks', icon: 'CheckSquare', label: { ar: 'المهام', en: 'Tasks' } },
  { name: 'TimeTracking', path: '/time', icon: 'Clock', label: { ar: 'تتبع الوقت', en: 'Time' } },
  { name: 'Reports', path: '/reports', icon: 'BarChart3', label: { ar: 'التقارير', en: 'Reports' } },
  { name: 'Settings', path: '/settings', icon: 'Settings', label: { ar: 'الإعدادات', en: 'Settings' } },
]);

const bottomNavItems = computed(() => [
  { name: 'Members', path: '/settings/members', icon: 'Users', label: { ar: 'الفريق', en: 'Team' } },
  { name: 'Webhooks', path: '/settings/webhooks', icon: 'Webhook', label: { ar: 'الويبهوك', en: 'Webhooks' } },
]);

const isActive = (path) => {
  if (path === '/dashboard') return route.path === '/dashboard';
  return route.path.startsWith(path);
};

const navigate = (path) => {
  router.push(path);
  if (innerWidth.value < 768) {
    uiStore.setSidebarOpen(false);
  }
};

const label = (item) => item.label[uiStore.locale] || item.label.en;
</script>

<template>
  <aside
    :class="[
      'fixed md:relative z-30 h-full bg-white border-l border-neutral-200 flex flex-col transition-all duration-200',
      uiStore.sidebarOpen ? 'translate-x-0' : '-translate-x-full md:-translate-x-full',
      uiStore.sidebarCollapsed ? 'w-16' : 'w-64',
    ]"
    role="navigation"
    aria-label="Main navigation"
  >
    <!-- Logo -->
    <div class="flex items-center gap-3 h-16 px-4 border-b border-neutral-200 flex-shrink-0">
      <div class="w-8 h-8 rounded-8 bg-gradient-to-br from-primary-600 to-secondary-600 flex items-center justify-center flex-shrink-0">
        <span class="text-white font-bold text-sm">T</span>
      </div>
      <transition name="fade">
        <span v-if="!uiStore.sidebarCollapsed" class="text-lg font-bold text-neutral-900 whitespace-nowrap">
          TaskSync Pro
        </span>
      </transition>
    </div>

    <!-- Nav items -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
      <button
        v-for="item in navItems"
        :key="item.name"
        @click="navigate(item.path)"
        :class="[
          'w-full flex items-center gap-3 px-3 py-2.5 rounded-8 text-sm font-medium transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-primary-500',
          isActive(item.path)
            ? 'bg-primary-50 text-primary-700'
            : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-800',
        ]"
        :aria-current="isActive(item.path) ? 'page' : undefined"
      >
        <span class="flex-shrink-0 w-5 h-5" aria-hidden="true">
          <svg v-if="item.icon === 'LayoutDashboard'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <rect x="3" y="3" width="7" height="9" /><rect x="14" y="3" width="7" height="5" /><rect x="14" y="12" width="7" height="9" /><rect x="3" y="16" width="7" height="5" />
          </svg>
          <svg v-else-if="item.icon === 'FolderKanban'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" />
          </svg>
          <svg v-else-if="item.icon === 'CheckSquare'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <polyline points="9 11 12 14 22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
          </svg>
          <svg v-else-if="item.icon === 'Clock'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
          </svg>
          <svg v-else-if="item.icon === 'BarChart3'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <line x1="12" y1="20" x2="12" y2="10" /><line x1="18" y1="20" x2="18" y2="4" /><line x1="6" y1="20" x2="6" y2="16" />
          </svg>
          <svg v-else-if="item.icon === 'Settings'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" /><circle cx="12" cy="12" r="3" />
          </svg>
          <svg v-else-if="item.icon === 'Users'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
          <svg v-else-if="item.icon === 'Webhook'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <path d="M18 16.98h-5.99c-1.1 0-1.95.94-2.48 1.9A4 4 0 0 1 2 17c.01-.7.2-1.4.57-2" /><path d="m6 17 3.13-5.78c.53-.97.1-2.18-.5-3.1a4 4 0 1 1 6.89-4.06" /><path d="m12 6 3.13 5.73C15.66 12.7 16.9 13 18 13a4 4 0 0 1 0 8" />
          </svg>
        </span>
        <transition name="fade">
          <span v-if="!uiStore.sidebarCollapsed">{{ label(item) }}</span>
        </transition>
      </button>
    </nav>

    <!-- Bottom nav -->
    <div class="border-t border-neutral-200 px-3 py-3 space-y-1">
      <button
        v-for="item in bottomNavItems"
        :key="item.name"
        @click="navigate(item.path)"
        :class="[
          'w-full flex items-center gap-3 px-3 py-2.5 rounded-8 text-sm font-medium transition-colors duration-150',
          isActive(item.path)
            ? 'bg-primary-50 text-primary-700'
            : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-800',
        ]"
      >
        <span class="flex-shrink-0 w-5 h-5" aria-hidden="true">
          <svg v-if="item.icon === 'Users'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
          <svg v-else-if="item.icon === 'Webhook'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <path d="M18 16.98h-5.99c-1.1 0-1.95.94-2.48 1.9A4 4 0 0 1 2 17c.01-.7.2-1.4.57-2" /><path d="m6 17 3.13-5.78c.53-.97.1-2.18-.5-3.1a4 4 0 1 1 6.89-4.06" /><path d="m12 6 3.13 5.73C15.66 12.7 16.9 13 18 13a4 4 0 0 1 0 8" />
          </svg>
        </span>
        <transition name="fade">
          <span v-if="!uiStore.sidebarCollapsed">{{ label(item) }}</span>
        </transition>
      </button>

      <!-- Collapse toggle -->
      <button
        @click="uiStore.toggleCollapse()"
        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-8 text-sm text-neutral-400 hover:text-neutral-600 hover:bg-neutral-100 transition-colors"
        :aria-label="uiStore.sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" :class="{ 'rtl-mirror': !uiStore.sidebarCollapsed }">
          <polyline points="15 18 9 12 15 6" />
        </svg>
        <transition name="fade">
          <span v-if="!uiStore.sidebarCollapsed" class="text-xs">{{ uiStore.locale === 'ar' ? 'طي' : 'Collapse' }}</span>
        </transition>
      </button>
    </div>
  </aside>

  <!-- Mobile overlay -->
  <transition name="fade">
    <div
      v-if="uiStore.sidebarOpen && innerWidth < 768"
      class="fixed inset-0 bg-black/30 z-20 md:hidden"
      @click="uiStore.setSidebarOpen(false)"
      aria-hidden="true"
    />
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
