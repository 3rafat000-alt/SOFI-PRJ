import { defineStore } from 'pinia';

export const useUiStore = defineStore('ui', {
  state: () => ({
    sidebarOpen: true,
    sidebarCollapsed: false,
    locale: localStorage.getItem('locale') || 'ar',
    theme: localStorage.getItem('theme') || 'light',
    direction: 'rtl',
    loading: false,
    toasts: [],
  }),

  getters: {
    isRtl: (state) => state.direction === 'rtl',
    sidebarWidth: (state) => (state.sidebarCollapsed ? 'w-16' : 'w-64'),
  },

  actions: {
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },

    toggleCollapse() {
      this.sidebarCollapsed = !this.sidebarCollapsed;
    },

    setSidebarOpen(open) {
      this.sidebarOpen = open;
    },

    setLocale(locale) {
      this.locale = locale;
      this.direction = locale === 'ar' ? 'rtl' : 'ltr';
      localStorage.setItem('locale', locale);
      document.documentElement.lang = locale;
      document.documentElement.dir = this.direction;
    },

    setTheme(theme) {
      this.theme = theme;
      localStorage.setItem('theme', theme);
      if (theme === 'dark') {
        document.documentElement.classList.add('dark');
      } else if (theme === 'light') {
        document.documentElement.classList.remove('dark');
      } else {
        // System preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }
      }
    },

    addToast(message, type = 'info', duration = 3000) {
      const id = Date.now();
      this.toasts.push({ id, message, type });
      setTimeout(() => {
        this.removeToast(id);
      }, duration);
    },

    removeToast(id) {
      this.toasts = this.toasts.filter((t) => t.id !== id);
    },

    setLoading(val) {
      this.loading = val;
    },
  },
});
