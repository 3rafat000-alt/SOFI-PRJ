import { defineStore } from 'pinia';
import api from '@/services/api';

export const useTimeEntryStore = defineStore('timeEntry', {
  state: () => ({
    entries: [],
    activeTimer: null,
    loading: false,
    error: null,
    saving: false,
    elapsedSeconds: 0,
    timerInterval: null,
    filters: {
      from: '',
      to: '',
      taskId: null,
      userId: null,
    },
    pagination: {
      currentPage: 1,
      lastPage: 1,
      perPage: 20,
      total: 0,
    },
    reportData: null,
    reportLoading: false,
  }),

  getters: {
    isTimerRunning: (state) => !!state.activeTimer && !state.activeTimer.ended_at,
    totalTodayMinutes: (state) => {
      const today = new Date().toISOString().split('T')[0];
      return state.entries
        .filter((e) => e.started_at?.startsWith(today))
        .reduce((sum, e) => sum + (e.duration_minutes || 0), 0);
    },
    formattedElapsed: (state) => {
      const h = Math.floor(state.elapsedSeconds / 3600);
      const m = Math.floor((state.elapsedSeconds % 3600) / 60);
      const s = state.elapsedSeconds % 60;
      return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    },
  },

  actions: {
    async fetchEntries(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const queryParams = {
          page: this.pagination.currentPage,
          per_page: this.pagination.perPage,
          ...params,
        };
        if (this.filters.from) queryParams.from = this.filters.from;
        if (this.filters.to) queryParams.to = this.filters.to;
        if (this.filters.taskId) queryParams.task_id = this.filters.taskId;
        if (this.filters.userId) queryParams.user_id = this.filters.userId;

        const { data } = await api.get('/time-entries', { params: queryParams });
        this.entries = data.data;
        if (data.meta) {
          this.pagination = {
            currentPage: data.meta.current_page || 1,
            lastPage: data.meta.last_page || 1,
            perPage: data.meta.per_page || 20,
            total: data.meta.total || 0,
          };
        }
      } catch (err) {
        this.error = err.message;
      } finally {
        this.loading = false;
      }
    },

    async startTimer(taskId, note = '') {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await api.post('/time-entries/start', { task_id: taskId, note });
        this.activeTimer = data.data;
        this.startLocalTimer();
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.saving = false;
      }
    },

    async stopTimer(note = '') {
      if (!this.activeTimer) return null;
      this.saving = true;
      this.error = null;
      try {
        const { data } = await api.post('/time-entries/stop', { note });
        this.activeTimer = data.data;
        this.stopLocalTimer();
        this.entries.unshift(data.data);
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.saving = false;
      }
    },

    async createManualEntry(formData) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await api.post('/time-entries', { ...formData, is_manual: true });
        this.entries.unshift(data.data);
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.saving = false;
      }
    },

    async updateEntry(id, formData) {
      try {
        const { data } = await api.put(`/time-entries/${id}`, formData);
        const idx = this.entries.findIndex((e) => e.id === id);
        if (idx !== -1) this.entries[idx] = { ...this.entries[idx], ...data.data };
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      }
    },

    async deleteEntry(id) {
      try {
        await api.delete(`/time-entries/${id}`);
        this.entries = this.entries.filter((e) => e.id !== id);
        return true;
      } catch (err) {
        this.error = err.message;
        return false;
      }
    },

    async fetchReport(params) {
      this.reportLoading = true;
      this.error = null;
      try {
        const { data } = await api.get('/time-entries/report', { params });
        this.reportData = data.data;
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.reportLoading = false;
      }
    },

    startLocalTimer() {
      this.stopLocalTimer();
      if (this.activeTimer?.started_at) {
        const start = new Date(this.activeTimer.started_at).getTime();
        this.elapsedSeconds = Math.floor((Date.now() - start) / 1000);
      }
      this.timerInterval = setInterval(() => {
        this.elapsedSeconds++;
      }, 1000);
    },

    stopLocalTimer() {
      if (this.timerInterval) {
        clearInterval(this.timerInterval);
        this.timerInterval = null;
      }
    },

    checkActiveTimer() {
      // Check if there's an active (running) timer from server
      const running = this.entries.find((e) => !e.ended_at);
      if (running) {
        this.activeTimer = running;
        this.startLocalTimer();
      }
    },

    setDateRange(from, to) {
      this.filters.from = from;
      this.filters.to = to;
      this.pagination.currentPage = 1;
      this.fetchEntries();
    },

    setPage(page) {
      this.pagination.currentPage = page;
      this.fetchEntries();
    },

    clearError() {
      this.error = null;
    },
  },
});
