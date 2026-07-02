import { defineStore } from 'pinia';
import api from '@/services/api';

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    error: null,
  }),

  getters: {
    hasUnread: (state) => state.unreadCount > 0,
    recentNotifications: (state) => state.notifications.slice(0, 5),
  },

  actions: {
    async fetchNotifications(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.get('/notifications', {
          params: { page: 1, per_page: 20, ...params },
        });
        this.notifications = data.data;
        this.unreadCount = data.meta?.unread_count || 0;
      } catch (err) {
        this.error = err.message;
      } finally {
        this.loading = false;
      }
    },

    async markRead(id) {
      try {
        await api.put(`/notifications/${id}/read`);
        const notif = this.notifications.find((n) => n.id === id);
        if (notif && !notif.read_at) {
          notif.read_at = new Date().toISOString();
          this.unreadCount = Math.max(0, this.unreadCount - 1);
        }
      } catch (err) {
        this.error = err.message;
      }
    },

    async markAllRead() {
      try {
        await api.put('/notifications/read-all');
        this.notifications.forEach((n) => {
          if (!n.read_at) n.read_at = new Date().toISOString();
        });
        this.unreadCount = 0;
      } catch (err) {
        this.error = err.message;
      }
    },

    addNotification(notification) {
      this.notifications.unshift(notification);
      if (!notification.read_at) {
        this.unreadCount++;
      }
    },

    clearError() {
      this.error = null;
    },
  },
});
