import { defineStore } from 'pinia';
import api from '@/services/api';
import { useAuthStore } from './authStore';

export const useProjectStore = defineStore('project', {
  state: () => ({
    projects: [],
    currentProject: null,
    loading: false,
    error: null,
    saving: false,
    searchQuery: '',
    filter: 'all', // all | active | archived
    pagination: {
      currentPage: 1,
      lastPage: 1,
      perPage: 20,
      total: 0,
    },
  }),

  getters: {
    filteredProjects: (state) => {
      let list = state.projects;
      if (state.filter !== 'all') {
        list = list.filter((p) => p.status === state.filter);
      }
      if (state.searchQuery) {
        const q = state.searchQuery.toLowerCase();
        list = list.filter(
          (p) =>
            p.name.toLowerCase().includes(q) ||
            (p.description && p.description.toLowerCase().includes(q)),
        );
      }
      return list;
    },
    activeProjects: (state) => state.projects.filter((p) => p.status === 'active'),
    archivedProjects: (state) => state.projects.filter((p) => p.status === 'archived'),
    projectTaskCount: (state) => (id) => {
      const p = state.projects.find((pr) => pr.id === id);
      return p?.task_count || { total: 0, todo: 0, in_progress: 0, done: 0 };
    },
  },

  actions: {
    async fetchProjects() {
      const authStore = useAuthStore();
      const workspaceId = authStore.currentWorkspaceId;
      if (!workspaceId) return;

      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.get('/projects', {
          params: {
            workspace_id: workspaceId,
            status: this.filter === 'all' ? undefined : this.filter,
            page: this.pagination.currentPage,
            per_page: this.pagination.perPage,
            search: this.searchQuery || undefined,
          },
        });
        this.projects = data.data;
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

    async fetchProject(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.get(`/projects/${id}`);
        this.currentProject = data.data;
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.loading = false;
      }
    },

    async createProject(formData) {
      const authStore = useAuthStore();
      this.saving = true;
      this.error = null;
      try {
        const { data } = await api.post('/projects', {
          ...formData,
          workspace_id: authStore.currentWorkspaceId,
        });
        this.projects.unshift(data.data);
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.saving = false;
      }
    },

    async updateProject(id, formData) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await api.put(`/projects/${id}`, formData);
        const idx = this.projects.findIndex((p) => p.id === id);
        if (idx !== -1) this.projects[idx] = { ...this.projects[idx], ...data.data };
        if (this.currentProject?.id === id) {
          this.currentProject = { ...this.currentProject, ...data.data };
        }
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.saving = false;
      }
    },

    async deleteProject(id) {
      this.saving = true;
      this.error = null;
      try {
        await api.delete(`/projects/${id}`);
        this.projects = this.projects.filter((p) => p.id !== id);
        if (this.currentProject?.id === id) this.currentProject = null;
        return true;
      } catch (err) {
        this.error = err.message;
        return false;
      } finally {
        this.saving = false;
      }
    },

    setSearch(query) {
      this.searchQuery = query;
      this.pagination.currentPage = 1;
      this.fetchProjects();
    },

    setFilter(status) {
      this.filter = status;
      this.pagination.currentPage = 1;
      this.fetchProjects();
    },

    setPage(page) {
      this.pagination.currentPage = page;
      this.fetchProjects();
    },

    clearError() {
      this.error = null;
    },
  },
});
