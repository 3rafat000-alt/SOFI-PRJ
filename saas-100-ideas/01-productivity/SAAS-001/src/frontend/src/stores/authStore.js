import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token') || null,
    workspace: null,
    workspaces: [],
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    userId: (state) => state.user?.id || null,
    userName: (state) => state.user?.name || '',
    userLocale: (state) => state.user?.locale || 'ar',
    userTimezone: (state) => state.user?.timezone || 'Asia/Riyadh',
    currentWorkspaceId: (state) => state.workspace?.id || null,
    isWorkspaceOwner: (state) => state.workspace?.role === 'owner',
  },

  actions: {
    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.post('/auth/login', { email, password });
        const { user, workspace, workspaces, token } = data.data;
        this.user = user;
        this.workspace = workspace;
        this.workspaces = workspaces || [];
        this.token = token;
        localStorage.setItem('auth_token', token);
        return true;
      } catch (err) {
        this.error = err.message || 'Login failed';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async register(formData) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.post('/auth/register', formData);
        const { user, workspace, token } = data.data;
        this.user = user;
        this.workspace = workspace;
        this.workspaces = [workspace];
        this.token = token;
        localStorage.setItem('auth_token', token);
        return true;
      } catch (err) {
        this.error = err.message || 'Registration failed';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async fetchProfile() {
      if (!this.token) return;
      this.loading = true;
      try {
        const { data } = await api.get('/auth/me');
        this.user = data.data;
        if (data.data.workspaces?.length > 0) {
          this.workspaces = data.data.workspaces;
          const current = data.data.workspaces.find(
            (w) => w.id === data.data.current_workspace_id,
          );
          if (current) this.workspace = current;
          else this.workspace = data.data.workspaces[0];
        }
      } catch (err) {
        this.error = err.message;
        throw err;
      } finally {
        this.loading = false;
      }
    },

    logout() {
      this.user = null;
      this.token = null;
      this.workspace = null;
      this.workspaces = [];
      localStorage.removeItem('auth_token');
    },

    async refreshToken() {
      // With Sanctum SPA, token refresh happens via cookie
      // If using API tokens, re-login is needed
      try {
        const { data } = await api.get('/auth/me');
        this.user = data.data;
      } catch {
        this.logout();
        throw new Error('Session expired');
      }
    },

    setWorkspace(workspace) {
      this.workspace = workspace;
    },

    clearError() {
      this.error = null;
    },
  },
});
