import { defineStore } from 'pinia';
import api from '@/services/api';
import { useAuthStore } from './authStore';

export const useWorkspaceStore = defineStore('workspace', {
  state: () => ({
    members: [],
    loading: false,
    error: null,
    saving: false,
  }),

  getters: {
    memberCount: (state) => state.members.length,
    activeMembers: (state) => state.members.filter((m) => m.status !== 'pending'),
    pendingInvites: (state) => state.members.filter((m) => m.status === 'pending'),
    admins: (state) => state.members.filter((m) => m.role === 'admin' || m.role === 'owner'),
    owners: (state) => state.members.filter((m) => m.role === 'owner'),
  },

  actions: {
    async fetchMembers() {
      const authStore = useAuthStore();
      const workspaceId = authStore.currentWorkspaceId;
      if (!workspaceId) return;

      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.get(`/workspaces/${workspaceId}/members`);
        this.members = data.data;
      } catch (err) {
        this.error = err.message;
      } finally {
        this.loading = false;
      }
    },

    async inviteMember(email, role = 'member', channel = 'email') {
      const authStore = useAuthStore();
      const workspaceId = authStore.currentWorkspaceId;
      if (!workspaceId) return false;

      this.saving = true;
      this.error = null;
      try {
        const { data } = await api.post(`/workspaces/${workspaceId}/invite`, {
          email,
          role,
          channel,
          message: channel === 'whatsapp' ? 'انضم لفريقنا في TaskSync!' : undefined,
        });
        this.members.push({
          id: data.data.invitation.id,
          email,
          role,
          status: 'pending',
          ...data.data.invitation,
        });
        return true;
      } catch (err) {
        this.error = err.details?.email?.[0] || err.message;
        return false;
      } finally {
        this.saving = false;
      }
    },

    async removeMember(memberId) {
      const authStore = useAuthStore();
      const workspaceId = authStore.currentWorkspaceId;
      if (!workspaceId) return false;

      this.saving = true;
      try {
        await api.delete(`/workspaces/${workspaceId}/members/${memberId}`);
        this.members = this.members.filter((m) => m.id !== memberId);
        return true;
      } catch (err) {
        this.error = err.message;
        return false;
      } finally {
        this.saving = false;
      }
    },

    async changeRole(memberId, role) {
      const authStore = useAuthStore();
      const workspaceId = authStore.currentWorkspaceId;
      if (!workspaceId) return false;

      try {
        await api.put(`/workspaces/${workspaceId}/members/${memberId}`, { role });
        const member = this.members.find((m) => m.id === memberId);
        if (member) member.role = role;
        return true;
      } catch (err) {
        this.error = err.message;
        return false;
      }
    },

    async updateWorkspace(data) {
      const authStore = useAuthStore();
      const workspaceId = authStore.currentWorkspaceId;
      if (!workspaceId) return false;

      this.saving = true;
      this.error = null;
      try {
        const response = await api.put(`/workspaces/${workspaceId}`, data);
        authStore.workspace = { ...authStore.workspace, ...response.data.data };
        return true;
      } catch (err) {
        this.error = err.message;
        return false;
      } finally {
        this.saving = false;
      }
    },

    async deleteWorkspace() {
      const authStore = useAuthStore();
      const workspaceId = authStore.currentWorkspaceId;
      if (!workspaceId) return false;

      this.saving = true;
      this.error = null;
      try {
        await api.delete(`/workspaces/${workspaceId}`);
        authStore.logout();
        return true;
      } catch (err) {
        this.error = err.message;
        return false;
      } finally {
        this.saving = false;
      }
    },

    clearError() {
      this.error = null;
    },
  },
});
