import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import { useTaskStore } from '../../src/stores/taskStore';

// Stub child components
const stubs = {
    TaskCard: { template: '<div class="task-card-stub" />' },
    TaskForm: { template: '<div class="task-form-stub" />' },
    LoadingSpinner: { template: '<div class="loading-stub" />' },
    EmptyState: { template: '<div class="empty-stub" />' },
    RouterLink: { template: '<a class="router-link-stub" />' },
    RouterView: { template: '<div />' },
};

// Mock router
const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/projects/:id', name: 'ProjectDetail', component: { template: '<div />' } },
        { path: '/tasks/:id', name: 'TaskDetail', component: { template: '<div />' } },
    ],
});

describe('KanbanBoard', () => {
    let taskStore;

    beforeEach(async () => {
        setActivePinia(createPinia());
        taskStore = useTaskStore();
        vi.clearAllMocks();

        router.push('/projects/1/kanban');
        await router.isReady();
    });

    it('shows loading spinner when loading and no tasks', async () => {
        taskStore.loading = true;
        taskStore.todoTasks = [];
        taskStore.inProgressTasks = [];
        taskStore.doneTasks = [];

        const wrapper = mount(await import('../../src/views/KanbanBoard.vue'), {
            global: { plugins: [router], stubs },
        });

        // Wait for async mounted
        await new Promise(r => setTimeout(r, 50));

        expect(wrapper.findComponent(stubs.LoadingSpinner).exists()).toBe(true);
    });

    it('renders three columns', async () => {
        taskStore.todoTasks = [{ id: 1, title: 'Todo', status: 'todo' }];
        taskStore.inProgressTasks = [{ id: 2, title: 'In Progress', status: 'in_progress' }];
        taskStore.doneTasks = [{ id: 3, title: 'Done', status: 'done' }];

        const wrapper = mount(await import('../../src/views/KanbanBoard.vue'), {
            global: { plugins: [router], stubs },
        });

        await new Promise(r => setTimeout(r, 50));

        const columns = wrapper.findAll('.kanban-column');
        expect(columns.length).toBe(3);
    });

    it('drags over column sets dragOverColumn', async () => {
        taskStore.todoTasks = [{ id: 1, title: 'Todo', status: 'todo' }];

        const wrapper = mount(await import('../../src/views/KanbanBoard.vue'), {
            global: { plugins: [router], stubs },
        });

        await new Promise(r => setTimeout(r, 50));

        const firstColumn = wrapper.findAll('.kanban-column')[0];
        const dragEvent = new Event('dragover');
        Object.defineProperty(dragEvent, 'preventDefault', { value: vi.fn() });
        Object.defineProperty(dragEvent, 'dataTransfer', {
            value: { dropEffect: '' },
        });

        await firstColumn.trigger('dragover', dragEvent);
    });

    it('drops task triggers moveTask', async () => {
        taskStore.todoTasks = [{ id: 1, title: 'Todo', status: 'todo' }];
        taskStore.moveTask = vi.fn().mockResolvedValue(undefined);

        const wrapper = mount(await import('../../src/views/KanbanBoard.vue'), {
            global: { plugins: [router], stubs },
        });

        await new Promise(r => setTimeout(r, 50));

        const firstColumn = wrapper.findAll('.kanban-column')[0];
        const dropEvent = new Event('drop');
        Object.defineProperty(dropEvent, 'preventDefault', { value: vi.fn() });
        Object.defineProperty(dropEvent, 'dataTransfer', {
            value: { getData: vi.fn().mockReturnValue('1') },
        });

        await firstColumn.trigger('drop', dropEvent);
    });

    it('shows empty state when no tasks exist in any column', async () => {
        taskStore.todoTasks = [];
        taskStore.inProgressTasks = [];
        taskStore.doneTasks = [];

        const wrapper = mount(await import('../../src/views/KanbanBoard.vue'), {
            global: { plugins: [router], stubs },
        });

        await new Promise(r => setTimeout(r, 50));
    });
});
