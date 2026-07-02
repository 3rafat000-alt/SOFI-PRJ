<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import { useNotificationStore } from '@/stores/notificationStore';
import SearchInput from './SearchInput.vue';
import NotificationDropdown from './NotificationDropdown.vue';

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();
const notificationStore = useNotificationStore();
const showUserMenu = ref(false);
const showNotifications = ref(false);
const userMenuRef = ref(null);

onMounted(() => {
  notificationStore.fetchNotifications();

  document.addEventListener('click', (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
      showUserMenu.value = false;
    }
  });
});

const handleSearch = (query) => {
  if (query.trim()) {
    router.push({ path: '/tasks', query: { search: query } });
  }
};

const logout = async () => {
  showUserMenu.value = false;
  authStore.logout();
  router.push('/login');
};

const toggleTheme = () => {
  const themes = ['light', 'dark'];
  const current = themes.indexOf(uiStore.theme);
  uiStore.setTheme(themes[(current + 1) % themes.length]);
};

const toggleLocale = () => {
  const newLocale = uiStore.locale === 'ar' ? 'en' : 'ar';
  uiStore.setLocale(newLocale);
};
</script>

<template>
  <header class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-4 sm:px-6 gap-4 flex-shrink-0" role="banner">
    <!-- Left: Hamburger + Search -->
    <div class="flex items-center gap-3 flex-1">
      <button
        @click="uiStore.toggleSidebar()"
        class="btn-icon btn-ghost md:hidden"
        aria-label="Toggle sidebar"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
          <line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="18" x2="21" y2="18" />
        </svg>
      </button>
      <div class="hidden sm:block w-full max-w-sm">
        <SearchInput
          :placeholder="uiStore.locale === 'ar' ? 'بحث...' : 'Search...'"
          @search="handleSearch"
        />
      </div>
    </div>

    <!-- Right: Actions -->
    <div class="flex items-center gap-2">
      <!-- Locale toggle -->
      <button
        @click="toggleLocale"
        class="btn-icon btn-ghost text-sm font-medium"
        :aria-label="`Switch to ${uiStore.locale === 'ar' ? 'English' : 'Arabic'}`"
      >
        {{ uiStore.locale === 'ar' ? 'EN' : 'AR' }}
      </button>

      <!-- Theme toggle -->
      <button
        @click="toggleTheme"
        class="btn-icon btn-ghost"
        :aria-label="`Switch to ${uiStore.theme === 'light' ? 'dark' : 'light'} mode`"
      >
        <svg v-if="uiStore.theme === 'light'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
          <circle cx="12" cy="12" r="4" /><path d="M12 2v2" /><path d="M12 20v2" /><path d="m4.93 4.93 1.41 1.41" /><path d="m17.66 17.66 1.41 1.41" /><path d="M2 12h2" /><path d="M20 12h2" /><path d="m6.34 17.66-1.41 1.41" /><path d="m19.07 4.93-1.41 1.41" />
        </svg>
      </button>

      <!-- Notifications -->
      <div class="relative">
        <button
          @click="showNotifications = !showNotifications"
          class="btn-icon btn-ghost relative"
          aria-label="Notifications"
          :aria-expanded="showNotifications"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" /><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
          </svg>
          <span
            v-if="notificationStore.unreadCount > 0"
            class="absolute -top-1 -right-1 w-5 h-5 bg-error-500 text-white text-2xs rounded-full flex items-center justify-center font-bold"
            aria-live="polite"
          >
            {{ notificationStore.unreadCount > 99 ? '99+' : notificationStore.unreadCount }}
          </span>
        </button>
        <NotificationDropdown v-if="showNotifications" @close="showNotifications = false" />
      </div>

      <!-- User menu -->
      <div class="relative" ref="userMenuRef">
        <button
          @click="showUserMenu = !showUserMenu"
          class="flex items-center gap-2 p-1.5 rounded-8 hover:bg-neutral-100 transition-colors"
          :aria-expanded="showUserMenu"
          :aria-label="authStore.userName"
        >
          <MemberAvatar :user="authStore.user" :size="'sm'" />
          <span class="hidden sm:block text-sm font-medium text-neutral-700 max-w-[120px] truncate">
            {{ authStore.userName }}
          </span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-neutral-400">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </button>

        <!-- User dropdown -->
        <transition name="fade">
          <div v-if="showUserMenu" class="dropdown-content left-0 right-auto ltr:left-auto ltr:right-0">
            <div class="px-4 py-2 border-b border-neutral-100">
              <p class="text-sm font-medium text-neutral-900">{{ authStore.userName }}</p>
              <p class="text-xs text-neutral-500">{{ authStore.user?.email }}</p>
            </div>
            <button class="dropdown-item" @click="router.push('/settings'); showUserMenu = false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" /><circle cx="12" cy="12" r="3" />
              </svg>
              {{ uiStore.locale === 'ar' ? 'الإعدادات' : 'Settings' }}
            </button>
            <button class="dropdown-item text-error-600" @click="logout">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" />
              </svg>
              {{ uiStore.locale === 'ar' ? 'تسجيل الخروج' : 'Logout' }}
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
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
