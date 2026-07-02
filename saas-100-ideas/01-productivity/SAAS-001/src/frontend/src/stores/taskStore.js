import { defineStore } from 'pinia';
import api from '@/services/api';

export const useTaskStore = defineStore('task', {
  state: () => ({
    tasks: [],
    currentTask: null,
    loading: false,
    error: null,
    saving: false,
    searchQuery: '',
    filters: {
      status: 'all',
      priority: 'all',
      assigneeId: null,
      projectId: null,
      tags: [],
    },
    pagination: {
      currentPage: 1,
      lastPage: 1,
      perPage: 50,
      total: 0,
    },
    // Kanban state
    kanbanColumns: {
      todo: [],
      in_progress: [],
      done: [],
    },
    dragging: false,
  }),

  getters: {
    filteredTasks: (state) => {
      let list = state.tasks;
      if (state.filters.status !== 'all' && state.filters.status) {
        const statuses = state.filters.status.split(',');
        list = list.filter((t) => statuses.includes(t.status));
      }
      if (state.filters.priority !== 'all') {
        list = list.filter((t) => t.priority === state.filters.priority);
      }
      if (state.filters.assigneeId) {
        list = list.filter((t) => t.assignee?.id === state.filters.assigneeId);
      }
      if (state.searchQuery) {
        const q = state.searchQuery.toLowerCase();
        list = list.filter(
          (t) =>
            t.title.toLowerCase().includes(q) ||
            (t.description && t.description.toLowerCase().includes(q)),
        );
      }
      return list;
    },
    todoTasks: (state) => state.kanbanColumns.todo || [],
    inProgressTasks: (state) => state.kanbanColumns.in_progress || [],
    doneTasks: (state) => state.kanbanColumns.done || [],
    overdueTasks: (state) => state.tasks.filter((t) => t.is_overdue),
  },

  actions: {
    async fetchTasks(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const queryParams = {
          page: this.pagination.currentPage,
          limit: this.pagination.perPage,
          ...params,
        };
        if (this.searchQuery) queryParams.search = this.searchQuery;
        if (this.filters.status !== 'all') queryParams.status = this.filters.status;
        if (this.filters.priority !== 'all') queryParams.priority = this.filters.priority;
        if (this.filters.assigneeId) queryParams.assignee_id = this.filters.assigneeId;
        if (this.filters.projectId) queryParams.project_id = this.filters.projectId;

        const { data } = await api.get('/tasks', { params: queryParams });
        this.tasks = data.data;
        if (data.meta) {
          this.pagination = {
            currentPage: data.meta.current_page || 1,
            lastPage: data.meta.last_page || 1,
            perPage: data.meta.per_page || 50,
            total: data.meta.total || 0,
          };
        }
        return data.data;
      } catch (err) {
        this.error = err.message;
        return [];
      } finally {
        this.loading = false;
      }
    },

    async fetchKanbanTasks(projectId) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.get(`/projects/${projectId}/tasks`, {
          params: { limit: 100 },
        });
        const tasks = data.data;
        this.kanbanColumns = {
          todo: tasks.filter((t) => t.status === 'todo').sort((a, b) => a.position - b.position),
          in_progress: tasks.filter((t) => t.status === 'in_progress').sort((a, b) => a.position - b.position),
          done: tasks.filter((t) => t.status === 'done').sort((a, b) => a.position - b.position),
        };
        return tasks;
      } catch (err) {
        this.error = err.message;
        return [];
      } finally {
        this.loading = false;
      }
    },

    async fetchTask(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await api.get(`/tasks/${id}`);
        this.currentTask = data.data;
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.loading = false;
      }
    },

    async createTask(formData) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await api.post('/tasks', formData);
        this.tasks.unshift(data.data);
        // Add to kanban if project matches
        if (formData.project_id) {
          const status = data.data.status || 'todo';
          if (!this.kanbanColumns[status]) this.kanbanColumns[status] = [];
          this.kanbanColumns[status].push(data.data);
        }
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.saving = false;
      }
    },

    async updateTask(id, formData) {
      this.saving = true;
      this.error = null;
      try {
        const { data } = await api.put(`/tasks/${id}`, formData);
        const idx = this.tasks.findIndex((t) => t.id === id);
        if (idx !== -1) this.tasks[idx] = { ...this.tasks[idx], ...data.data };
        if (this.currentTask?.id === id) {
          this.currentTask = { ...this.currentTask, ...data.data };
        }
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      } finally {
        this.saving = false;
      }
    },

    async deleteTask(id) {
      this.saving = true;
      this.error = null;
      try {
        await api.delete(`/tasks/${id}`);
        this.tasks = this.tasks.filter((t) => t.id !== id);
        // Remove from kanban
        Object.keys(this.kanbanColumns).forEach((key) => {
          this.kanbanColumns[key] = this.kanbanColumns[key].filter((t) => t.id !== id);
        });
        if (this.currentTask?.id === id) this.currentTask = null;
        return true;
      } catch (err) {
        this.error = err.message;
        return false;
      } finally {
        this.saving = false;
      }
    },

    // Kanban drag-and-drop with optimistic update
    async moveTask(taskId, newStatus, newPosition) {
      // Find task in current columns
      let task = null;
      let oldStatus = null;
      Object.keys(this.kanbanColumns).forEach((key) => {
        const found = this.kanbanColumns[key].find((t) => t.id === taskId);
        if (found) {
          task = found;
          oldStatus = key;
        }
      });

      if (!task) return false;

      // Optimistic update
      const oldColumns = JSON.parse(JSON.stringify(this.kanbanColumns));

      // Remove from old column
      this.kanbanColumns[oldStatus] = this.kanbanColumns[oldStatus].filter(
        (t) => t.id !== taskId,
      );

      // Add to new column
      task.status = newStatus;
      task.position = newPosition;
      if (!this.kanbanColumns[newStatus]) this.kanbanColumns[newStatus] = [];
      this.kanbanColumns[newStatus].splice(newPosition - 1, 0, task);

      // Reindex positions
      this.kanbanColumns[newStatus].forEach((t, i) => {
        t.position = i + 1;
      });

      this.dragging = true;

      try {
        // Build orders for all columns
        const orders = [];
        Object.entries(this.kanbanColumns).forEach(([status, tasks]) => {
          tasks.forEach((t, idx) => {
            orders.push({ id: t.id, status, position: idx + 1 });
          });
        });

        const projectId = task.project_id;
        await api.put('/tasks/reorder', {
          project_id: projectId,
          orders,
        });
        return true;
      } catch (err) {
        // Rollback
        this.kanbanColumns = oldColumns;
        this.error = err.message;
        return false;
      } finally {
        this.dragging = false;
      }
    },

    async quickStatusChange(taskId, status) {
      try {
        const { data } = await api.patch(`/tasks/${taskId}/status`, { status });
        const task = this.tasks.find((t) => t.id === taskId);
        if (task) task.status = status;
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      }
    },

    // Comments
    async fetchComments(taskId) {
      try {
        const { data } = await api.get(`/tasks/${taskId}/comments`);
        return data.data;
      } catch {
        return [];
      }
    },

    async addComment(taskId, body) {
      try {
        const { data } = await api.post(`/tasks/${taskId}/comments`, { body });
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      }
    },

    async deleteComment(commentId) {
      try {
        await api.delete(`/comments/${commentId}`);
        return true;
      } catch {
        return false;
      }
    },

    // Attachments
    async uploadAttachment(taskId, file) {
      try {
        const formData = new FormData();
        formData.append('file', file);
        const { data } = await api.post(`/tasks/${taskId}/attachments`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data.data;
      } catch (err) {
        this.error = err.message;
        return null;
      }
    },

    async deleteAttachment(attachmentId) {
      try {
        await api.delete(`/attachments/${attachmentId}`);
        return true;
      } catch {
        return false;
      }
    },

    setSearch(query) {
      this.searchQuery = query;
      this.pagination.currentPage = 1;
      this.fetchTasks();
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters };
      this.pagination.currentPage = 1;
      this.fetchTasks();
    },

    clearFilters() {
      this.filters = { status: 'all', priority: 'all', assigneeId: null, projectId: null, tags: [] };
      this.searchQuery = '';
      this.pagination.currentPage = 1;
      this.fetchTasks();
    },

    setPage(page) {
      this.pagination.currentPage = page;
      this.fetchTasks();
    },

    clearError() {
      this.error = null;
    },
  },
});
